import os
from datetime import datetime

def get_latest_subfolder(parent_folder):
    try:
        date_folders = [
            os.path.join(parent_folder, d)
            for d in os.listdir(parent_folder)
            if os.path.isdir(os.path.join(parent_folder, d))
        ]
        if not date_folders:
            raise FileNotFoundError("Tidak ada folder tanggal.")

        latest_date = max(date_folders, key=os.path.getmtime)

        time_folders = [
            os.path.join(latest_date, t)
            for t in os.listdir(latest_date)
            if os.path.isdir(os.path.join(latest_date, t))
        ]
        if not time_folders:
            raise FileNotFoundError("Tidak ada folder waktu.")

        latest_time = max(time_folders, key=os.path.getmtime)
        print(f"[INFO] Subfolder terbaru: {latest_time}")
        return latest_time

    except Exception as e:
        print(f"[ERROR] Gagal mengambil subfolder dari {parent_folder}: {e}")
        return None

def prepare_output_folder(base_dir, subfolder_name):
    now = datetime.now()
    output_path = os.path.join(
        base_dir, subfolder_name,
        now.strftime("%d-%m-%Y"), now.strftime("%H.%M.%S")
    )
    try:
        os.makedirs(output_path, exist_ok=True)
        print(f"[INFO] Folder output: {output_path}")
        return output_path
    except Exception as e:
        print(f"[ERROR] Gagal membuat folder output: {e}")
        return None

def get_image_paths(folder, extensions=('.jpg', '.jpeg', '.png')):
    try:
        paths = [
            os.path.join(root, file)
            for root, _, files in os.walk(folder)
            for file in files
            if file.lower().endswith(extensions)
        ]
        print(f"[INFO] {len(paths)} gambar ditemukan di {folder}")
        return paths
    except Exception as e:
        print(f"[ERROR] Gagal membaca gambar di {folder}: {e}")
        return []