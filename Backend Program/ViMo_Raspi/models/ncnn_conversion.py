from ultralytics import YOLO

# Load a YOLO PyTorch model
model = YOLO("best_v8.pt")

# Export the model to NCNN format
model.export(format="ncnn", imgsz=480)