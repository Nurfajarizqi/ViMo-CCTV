import time
import cv2
import os
import sys
import threading
import json
from picamera2 import Picamera2
from ultralytics import YOLO
from datetime import datetime, timedelta
from flask import Flask, Response, jsonify
from flask_cors import CORS
from folder_organizer import update_folder_crop, update_folder_full
from upload import upload_folder
import logging

# Setup logging
logging.basicConfig(level=logging.WARNING)
logging.getLogger("ultralytics").setLevel(logging.CRITICAL)

# Flask setup
app = Flask(__name__)
CORS(app)

# Global variables
INTERVAL_MIN = int(sys.argv[1]) if len(sys.argv) > 1 and sys.argv[1].isdigit() else 1
latest_frame = None
person_count = 0
person_count_lock = threading.Lock()
inference_times = []
json_log_path = "inference_log.json"

# Folder setup
os.makedirs("cropped_images", exist_ok=True)
os.makedirs("captures", exist_ok=True)

# Camera setup
picam2 = Picamera2()
picam2.preview_configuration.main.size = (1920, 1080)
picam2.preview_configuration.main.format = "RGB888"
picam2.configure("preview")
picam2.start()

# Load YOLO model
model = YOLO("models/yolov8n_ncnn_model")

def save_json_log(data):
    if not os.path.exists(json_log_path):
        with open(json_log_path, 'w') as f:
            json.dump([], f, indent=4)

    with open(json_log_path, 'r+') as f:
        logs = json.load(f)
        logs.append(data)
        f.seek(0)
        json.dump(logs, f, indent=4)

def camera_loop():
    global latest_frame, person_count, inference_times
    next_capture = datetime.now() + timedelta(seconds=45)

    while True:
        try:
            frame = picam2.capture_array()

            # Hitung sisa waktu sebelum capture berikutnya
            seconds_to_capture = max(0, int((next_capture - datetime.now()).total_seconds()))
            print(f"[INFO] Detik menuju cropping berikutnya: {seconds_to_capture}s", end='\r')

            start = time.time()
            results = model.track(frame, persist=True, imgsz=480)[0]
            end = time.time()

            inference_time = round((end - start) * 1000, 2)
            inference_times.append(inference_time)
            avg_inference = round(sum(inference_times) / len(inference_times), 2)

            # Filter hanya person dengan confidence > 0.25
            mask = (results.boxes.cls == 0) & (results.boxes.conf > 0.25)
            results.boxes = results.boxes[mask]

            detected_count = len(results.boxes)
            with person_count_lock:
                person_count = detected_count

            # Annotated frame
            annotated = frame.copy()
            detected_classes = []
            for box, cls in zip(results.boxes.xyxy.cpu().numpy(), results.boxes.cls.cpu().numpy()):
                x1, y1, x2, y2 = map(int, box)
                label = model.names[int(cls)]
                detected_classes.append(label)
                cv2.rectangle(annotated, (x1, y1), (x2, y2), (0, 255, 0), 2)
                cv2.putText(annotated, label, (x1, y1 - 10), cv2.FONT_HERSHEY_SIMPLEX,
                            0.6, (0, 255, 0), 2)

            ret, jpeg = cv2.imencode('.jpg', annotated)
            if ret:
                latest_frame = jpeg.tobytes()

            # Capture dan crop saat waktu tiba
            if datetime.now() >= next_capture and detected_count > 0:
                print("\n🚀 Memulai cropping dan penyimpanan...")
                timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
                folder_crop = update_folder_crop()
                folder_full = update_folder_full()

                # Simpan full frame
                full_path = os.path.join(folder_full, f"frame_{timestamp}.jpg")
                cv2.imwrite(full_path, annotated)

                # Simpan crop
                n = len(os.listdir(folder_crop)) + 1
                for box in results.boxes.xyxy.cpu().numpy():
                    x1, y1, x2, y2 = map(int, box)
                    crop = frame[y1:y2, x1:x2]
                    if crop.size == 0:
                        continue
                    crop_path = os.path.join(folder_crop, f"{n}.jpg")
                    cv2.imwrite(crop_path, crop)
                    n += 1

                # Upload folder
                if n > 1:
                    upload_folder(folder_crop)

                # Simpan hasil ke JSON
                now = datetime.now()
                log_data = {
                    "timestamp": now.strftime("%Y-%m-%d %H:%M:%S"),
                    "day": now.strftime("%A"),
                    "detected_person_count": detected_count,
                    "detected_classes": detected_classes,
                    "inference_time_ms": inference_time,
                    "average_inference_time_ms": avg_inference
                }
                save_json_log(log_data)
                print(f"[INFO] JSON log disimpan: {log_data}")

                next_capture = datetime.now() + timedelta(minutes=INTERVAL_MIN)

        except Exception as e:
            print(f"[ERROR] Kamera loop: {e}")

# Jalankan camera loop sebagai thread
threading.Thread(target=camera_loop, daemon=True).start()

@app.route("/video_feed")
def video_feed():
    def generate():
        while True:
            if latest_frame:
                yield (b'--frame\r\n'
                       b'Content-Type: image/jpeg\r\n\r\n' + latest_frame + b'\r\n')
            time.sleep(0.05)
    return Response(generate(), mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route("/person_count")
def get_person_count():
    with person_count_lock:
        count = person_count
    print(f"[INFO] Person count: {count}")
    return jsonify({"person_count": count})

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)