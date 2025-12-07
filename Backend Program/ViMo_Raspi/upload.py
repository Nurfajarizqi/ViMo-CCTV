import os, time, subprocess
from datetime import datetime

REMOTE_USER = "capstone"
REMOTE_HOST = "vmserver"
REMOTE_PATH = "/home/capstone/TA2/ViMo_CCTV/Computer_Vision/received_images"
PARENT_FOLDER = "cropped_images"

def get_latest_time_folder(today_path):
    subfolders = [os.path.join(today_path, name) for name in os.listdir(today_path) if os.path.isdir(os.path.join(today_path, name))]
    return max(subfolders, key=os.path.getmtime) if subfolders else None

def upload_folder(local_path):
    folder_name = os.path.relpath(local_path, PARENT_FOLDER)
    remote_path = os.path.join(REMOTE_PATH, folder_name)
    print(f"Upload: {folder_name}")

    try:
        subprocess.run(f'ssh {REMOTE_USER}@{REMOTE_HOST} "mkdir -p \\"{remote_path}\\""', shell=True, check=True)
        print(f"Folder remote dibuat: {remote_path}")
    except subprocess.CalledProcessError as e:
        print(f"Gagal membuat folder: {e}")
        return

    for i in range(3):
        try:
            subprocess.run(f"scp -r {local_path}/* {REMOTE_USER}@{REMOTE_HOST}:{remote_path}", shell=True, check=True)
            print("Upload sukses."); return
        except subprocess.CalledProcessError as e:
            print(f"Percobaan {i+1} gagal: {e}")
            time.sleep(3)

    print(f"Gagal upload {folder_name} setelah 3 kali.")

def upload_latest_folder():
    today_path = os.path.join(PARENT_FOLDER, datetime.now().strftime("%d-%m-%Y"))
    if not os.path.exists(today_path): return print("Folder hari ini tidak ditemukan.")
    
    latest = get_latest_time_folder(today_path)
    if not latest: return print("Subfolder waktu tidak ditemukan.")

    upload_folder(latest)

if __name__ == "__main__":
    upload_latest_folder()