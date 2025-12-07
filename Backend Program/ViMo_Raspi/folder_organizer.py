import os
from datetime import datetime

OUTPUT_CROPPED = "cropped_images"
OUTPUT_FULL = "captures"

def update_folder_crop():
    try:
        now = datetime.now()
        path = os.path.join(OUTPUT_CROPPED, now.strftime("%d-%m-%Y"), now.strftime("%H.%M.%S"))
        os.makedirs(path, exist_ok=True)
        return path
    except Exception as e:
        print(f"Gagal buat folder output: {e}")
        return None
    
def update_folder_full():
    try:
        now = datetime.now()
        path = os.path.join(OUTPUT_FULL, now.strftime("%d-%m-%Y"), now.strftime("%H.%M.%S"))
        os.makedirs(path, exist_ok=True)
        return path
    except Exception as e:
        print(f"Gagal buat folder output: {e}")
        return None