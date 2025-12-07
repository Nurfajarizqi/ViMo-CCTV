import os
import re
import cv2
import torch
import numpy as np
from facenet_pytorch import MTCNN, InceptionResnetV1
from sklearn.preprocessing import Normalizer
from joblib import load

from folder_organizer import get_latest_subfolder, prepare_output_folder, get_image_paths
from result_logger import save_results_to_csv

INPUT_DIR = 'pre_processed_images'
OUTPUT_DIR = 'face_recognition_images'
THRESHOLD = 1.0

device = torch.device('cuda' if torch.cuda.is_available() else 'cpu')
print(f"\n[INFO] Menggunakan perangkat: {device}")

print("[INFO] Memuat model deteksi wajah dan ekstraksi fitur...")
mtcnn = MTCNN(image_size=160, margin=20, keep_all=True, device=device)
facenet = InceptionResnetV1(pretrained='vggface2').eval().to(device)

print("[INFO] Memuat model klasifikasi dan encoder label...")
svm_model = load('models/svm_model.pkl')
in_encoder = load('models/in_encoder.pkl')

try:
    pca_model = load('models/pca_model.pkl')
    USE_PCA = True
    print("[INFO] Model PCA ditemukan dan akan digunakan.")
except FileNotFoundError:
    pca_model = None
    USE_PCA = False
    print("[PERINGATAN] Model PCA tidak ditemukan. PCA tidak akan digunakan.")

l2_normalizer = Normalizer(norm='l2')

latest_folder = get_latest_subfolder(INPUT_DIR)
if latest_folder is None:
    print("[ERROR] Tidak ditemukan folder input yang valid.")
    exit()

image_paths = get_image_paths(latest_folder)
output_folder = prepare_output_folder('.', OUTPUT_DIR)

csv_path = os.path.join(output_folder, "recognition_results.csv")
csv_headers = ["filename", "predicted_name", "confidence", "embedding_vector"]
csv_rows = []

def recognize_faces_in_image(img_path):
    filename = os.path.basename(img_path)
    basename, _ = os.path.splitext(filename)
    print(f"\n[PROSES] Memproses gambar: {filename}")

    image_bgr = cv2.imread(img_path)
    if image_bgr is None:
        print(f"[ERROR] Gagal membaca gambar: {img_path}")
        return

    image_rgb = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2RGB)
    boxes, probs = mtcnn.detect(image_rgb)
    aligned_faces = mtcnn(image_rgb)

    if boxes is None or probs is None or aligned_faces is None:
        print("[INFO] Tidak ditemukan wajah pada gambar.")
        return

    for i, (box, face_tensor, prob) in enumerate(zip(boxes, aligned_faces, probs)):
        if prob < 0.9 or face_tensor is None:
            print(f"[INFO] Wajah ke-{i+1} dilewati karena confidence rendah ({prob:.2f})")
            continue

        try:
            face_tensor = face_tensor.to(device).unsqueeze(0)
            with torch.no_grad():
                embedding = facenet(face_tensor).cpu().numpy()

            embedding = l2_normalizer.transform(embedding)
            if USE_PCA:
                embedding = pca_model.transform(embedding)

            prediction = svm_model.predict(embedding)
            probas = svm_model.predict_proba(embedding)

            class_index = prediction[0]
            class_name = in_encoder.inverse_transform([class_index])[0]
            class_prob = probas[0][svm_model.classes_ == class_index][0] * 100

            if class_prob < THRESHOLD:
                class_name = "Unknown"

            print(f"[HASIL] Wajah ke-{i+1}: {class_name} ({class_prob:.2f}%)")

            csv_rows.append([
                filename,
                class_name,
                round(class_prob, 2),
                embedding.flatten().tolist()
            ])

            x1, y1, x2, y2 = map(int, box)
            x1, y1 = max(0, x1), max(0, y1)
            x2, y2 = min(image_bgr.shape[1], x2), min(image_bgr.shape[0], y2)
            face_crop = image_bgr[y1:y2, x1:x2]

            safe_name = re.sub(r'[\\/*?:"<>|]', "_", class_name)
            output_filename = f"{basename}_{safe_name}.jpg"
            output_path = os.path.join(output_folder, output_filename)
            cv2.imwrite(output_path, face_crop)

        except Exception as e:
            print(f"[ERROR] Gagal memproses wajah ke-{i+1}: {e}")

print(f"\n[INFO] Jumlah gambar yang akan diproses: {len(image_paths)}")
for path in image_paths:
    recognize_faces_in_image(path)

save_results_to_csv(csv_path, csv_headers, csv_rows)
print("\n[SELESAI] Proses face recognition selesai.")
print(f"[OUTPUT] Hasil disimpan di: {output_folder}")