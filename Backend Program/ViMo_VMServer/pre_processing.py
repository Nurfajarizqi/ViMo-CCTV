import os
import cv2
import numpy as np
from facenet_pytorch import MTCNN
from folder_organizer import get_latest_subfolder, prepare_output_folder

# Inisialisasi MTCNN
mtcnn = MTCNN(keep_all=False, post_process=False, device='cuda' if cv2.cuda.getCudaEnabledDeviceCount() > 0 else 'cpu')

def enhance_image(image, scale_factor=3):
    try:
        h, w = image.shape[:2]
        resized = cv2.resize(image, (int(w * scale_factor), int(h * scale_factor)), interpolation=cv2.INTER_CUBIC)
        denoised = cv2.fastNlMeansDenoisingColored(resized, None, 3, 3, 5, 15)
        gaussian = cv2.GaussianBlur(denoised, (0, 0), 0.5)
        unsharp = cv2.addWeighted(denoised, 1.4, gaussian, -0.4, 0)
        return cv2.bilateralFilter(unsharp, 3, 25, 25)
    except:
        return image

def align_and_crop_face(image, box, landmarks, size=320, margin=0.8):
    try:
        le, re = landmarks[0], landmarks[1]
        angle = np.degrees(np.arctan2(re[1] - le[1], re[0] - le[0]))
        center = ((le[0] + re[0]) / 2, (le[1] + re[1]) / 2)
        matrix = cv2.getRotationMatrix2D(center, angle, 1.0)
        aligned = cv2.warpAffine(image, matrix, (image.shape[1], image.shape[0]), flags=cv2.INTER_CUBIC)

        x1, y1, x2, y2 = box
        w, h = x2 - x1, y2 - y1
        cx, cy = (x1 + x2) / 2, (y1 + y2) / 2
        mw, mh = w * (1 + margin), h * (1 + margin)
        x1, y1 = int(max(cx - mw / 2, 0)), int(max(cy - mh / 2, 0))
        x2, y2 = int(min(cx + mw / 2, aligned.shape[1])), int(min(cy + mh / 2, aligned.shape[0]))
        cropped = aligned[y1:y2, x1:x2]

        if cropped.size == 0:
            return None

        resized = cv2.resize(cropped, (size, size), interpolation=cv2.INTER_LANCZOS4)
        return resized
    except:
        return None

def process_images(input_folder, output_folder, confidence_threshold=0.90):
    processed, failed = 0, 0

    for filename in os.listdir(input_folder):
        if not filename.lower().endswith(('.jpg', '.jpeg', '.png')):
            continue

        input_path = os.path.join(input_folder, filename)
        img_bgr = cv2.imread(input_path)
        if img_bgr is None:
            failed += 1
            continue

        try:
            enhanced = enhance_image(img_bgr)
            img_rgb = cv2.cvtColor(enhanced, cv2.COLOR_BGR2RGB)

            boxes, probs, landmarks = mtcnn.detect(img_rgb, landmarks=True)

            if boxes is None or probs is None or landmarks is None:
                failed += 1
                continue

            # Filter berdasarkan confidence threshold
            valid = [
                (box, lm, prob) for box, lm, prob in zip(boxes, landmarks, probs)
                if prob is not None and prob >= confidence_threshold
            ]

            if len(valid) == 0:
                failed += 1
                continue

            # Pilih wajah terbesar dari yang valid
            i = np.argmax([(b[2] - b[0]) * (b[3] - b[1]) for b, _, _ in valid])
            box, landmark, confidence = valid[i]

            face = align_and_crop_face(enhanced, box, landmark)
            if face is not None:
                save_path = os.path.join(output_folder, filename)
                cv2.imwrite(save_path, face, [cv2.IMWRITE_JPEG_QUALITY, 100])
                processed += 1
            else:
                failed += 1
        except Exception as e:
            print(f"[Error: {filename}] {e}")
            failed += 1

    print(f"Berhasil: {processed}, Gagal: {failed}")

if __name__ == "__main__":
    input_root = "received_images"
    output_root = "pre_processed_images"
    latest_input = get_latest_subfolder(input_root)
    if latest_input:
        output_folder = prepare_output_folder('.', output_root)
        if output_folder:
            process_images(latest_input, output_folder, confidence_threshold=0.92)
    else:
        print("Folder input tidak ditemukan.")