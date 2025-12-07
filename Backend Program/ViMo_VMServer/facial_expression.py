import os
import cv2
import torch
import numpy as np
import joblib
import mediapipe as mp
import math
from datetime import datetime
from tensorflow.keras.applications import EfficientNetB3
from tensorflow.keras.applications.efficientnet import preprocess_input
from tensorflow.keras.preprocessing.image import img_to_array
from facenet_pytorch import MTCNN

from folder_organizer import get_latest_subfolder, prepare_output_folder, get_image_paths
from result_logger import save_results_to_csv

# ========= KONFIGURASI =========
INPUT_DIR = 'pre_processed_images'
OUTPUT_DIR = 'facial_expression_images'
LABELS = ['positif', 'negatif']
THRESHOLD = -2.1

# ========= SETUP =========
device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
print(f"\n[INFO] Menggunakan perangkat: {device}")

# Load model dan scaler
print("[INFO] Memuat model SVM dan scaler...")
model_data = joblib.load('models/fer_model.joblib')
svm_model = model_data['svm']
scaler = model_data['scaler']

# Ekstraktor fitur dan detektor wajah
efficientnet = EfficientNetB3(weights='imagenet', include_top=False, pooling='avg', input_shape=(300, 300, 3))
mtcnn = MTCNN(keep_all=True, device=device)
face_mesh = mp.solutions.face_mesh.FaceMesh(static_image_mode=True)

# ========= FUNGSI BANTUAN =========
def calculate_high_confidence(raw_score, threshold, scale=10):
    normalized_score = raw_score - threshold
    confidence = 1 / (1 + math.exp(-scale * normalized_score))
    return 70 + (confidence * 20)  

# ========= FOLDER DAN PATH =========
latest_folder = get_latest_subfolder(INPUT_DIR)
if latest_folder is None:
    print("[ERROR] Tidak ditemukan folder input.")
    exit()

image_paths = get_image_paths(latest_folder)
output_folder = prepare_output_folder('.', OUTPUT_DIR)

csv_path = os.path.join(output_folder, "expression_results.csv")
csv_headers = ["filename", "predicted_label", "raw_score", "confidence"]
csv_rows = []

# ========= PROSES Gambar =========
print(f"\n[INFO] Jumlah gambar yang diproses: {len(image_paths)}")

for image_path in image_paths:
    filename = os.path.basename(image_path)
    print(f"\n[PROSES] {filename}")
    image = cv2.imread(image_path)
    if image is None:
        print(f"[ERROR] Gagal membaca gambar: {image_path}")
        csv_rows.append([filename, "unknown", "NA", "NA"])
        continue

    image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
    boxes, _ = mtcnn.detect(image_rgb)

    if boxes is None or len(boxes) == 0:
        print("[INFO] Tidak ada wajah terdeteksi.")
        csv_rows.append([filename, "unknown", "NA", "NA"])
        continue

    # Ambil wajah pertama
    x1, y1, x2, y2 = map(int, boxes[0])
    x1, y1 = max(0, x1), max(0, y1)
    x2, y2 = min(image.shape[1], x2), min(image.shape[0], y2)
    face_roi = image[y1:y2, x1:x2]

    # Resize dan ekstraksi fitur CNN
    face_resized = cv2.resize(face_roi, (300, 300))
    face_input = preprocess_input(img_to_array(cv2.cvtColor(face_resized, cv2.COLOR_BGR2RGB))[np.newaxis])
    cnn_feature = efficientnet.predict(face_input, verbose=0).flatten()

    # Landmark wajah
    result = face_mesh.process(cv2.cvtColor(face_roi, cv2.COLOR_BGR2RGB))
    if not result.multi_face_landmarks:
        print("[INFO] Landmark tidak ditemukan. Hanya gunakan CNN.")
        landmark_vector = np.zeros(468 * 3) 
    else:
        landmark_vector = np.array([[lm.x, lm.y, lm.z] for lm in result.multi_face_landmarks[0].landmark]).flatten()

    # Gabungkan CNN + landmark
    combined_feature = np.concatenate([cnn_feature, landmark_vector])
    combined_scaled = scaler.transform([combined_feature])

    # Prediksi dengan SVM
    raw_score = svm_model.decision_function(combined_scaled)[0]
    label = LABELS[0] if raw_score >= THRESHOLD else LABELS[1]
    confidence = calculate_high_confidence(raw_score, THRESHOLD)

    # Tambahkan ke CSV
    csv_rows.append([filename, label, round(raw_score, 4), round(confidence, 1)])

    # Visualisasi
    color = (0, 255, 0) if label == 'positif' else (0, 0, 255)
    display_text = f"{label} ({confidence:.1f}%)"
    cv2.rectangle(image, (x1, y1), (x2, y2), color, 2)
    cv2.putText(image, display_text, (x1, y1 - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.45, color, 2)

    output_filename = f"{os.path.splitext(filename)[0]}_{label}.jpg"
    output_path = os.path.join(output_folder, output_filename)
    cv2.imwrite(output_path, image)
    print(f"[HASIL] {label.upper()} | Confidence: {confidence:.1f}% | Raw Score: {raw_score:.4f}")

# ========= SIMPAN HASIL =========
save_results_to_csv(csv_path, csv_headers, csv_rows)
print(f"\n[SELESAI] Semua ekspresi berhasil diproses.")
print(f"[OUTPUT] Disimpan di: {output_folder}")
