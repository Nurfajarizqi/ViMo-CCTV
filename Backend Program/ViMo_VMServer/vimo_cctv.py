import os
import sys
import time
import json
import signal
import threading
import subprocess
import argparse
from datetime import datetime
from jadwal import get_id_jadwal_realtime

# === KONFIGURASI ===
RASPI_USER = "capstone"
RASPI_HOST = "pi"
INTERVAL_MIN = 1

RASPI_COMMAND = (
    f'bash -c "cd /home/capstone/TA2/ViMo_CCTV/Object_Detection && '
    f'source /home/capstone/TA2/ViMo_CCTV/venv/bin/activate && '
    f'python3 detection.py {INTERVAL_MIN}"'
)

RECEIVED_DIR = "/home/capstone/TA2/ViMo_CCTV/Computer_Vision/received_images"
LOCAL_MAIN_COMMAND = ["python3", "main.py"]
LOCAL_MAIN_DIR = "/home/capstone/TA2/ViMo_CCTV/Computer_Vision"
TIMING_LOG_FILE = "/home/capstone/TA2/ViMo_CCTV/Computer_Vision/process_timing.json"

ID_KELAS = 1
MAX_RETRIES = 5
CHECK_INTERVAL = 5

running = False
remote_proc = None
main_thread = None
DEBUG_MODE = False
session_start_time = None

# === PARSER ARGUMEN ===
parser = argparse.ArgumentParser(description="ViMo CCTV Automation Script")
parser.add_argument("--debug", action="store_true", help="Jalankan dalam mode debug tanpa koneksi ke Raspberry Pi")
parser.add_argument("--reset-timing", action="store_true", help="Reset data timing sebelum memulai")
args = parser.parse_args()
DEBUG_MODE = args.debug

# === FUNGSI UTAMA ===
def is_in_schedule():
    """Cek apakah sedang dalam jadwal dan kembalikan (id_jadwal, mapel)"""
    return get_id_jadwal_realtime(ID_KELAS)

def get_latest_subfolder(root):
    try:
        folders = [
            os.path.join(root, d) for d in os.listdir(root)
            if os.path.isdir(os.path.join(root, d))
        ]
        if not folders:
            return None
        latest = max(folders, key=os.path.getmtime)
        subfolders = [
            os.path.join(latest, s) for s in os.listdir(latest)
            if os.path.isdir(os.path.join(latest, s))
        ]
        return max(subfolders, key=os.path.getmtime) if subfolders else None
    except Exception as e:
        print(f"⚠️ Gagal cek folder terbaru: {e}")
        return None

def reset_timing_data():
    try:
        if os.path.exists(TIMING_LOG_FILE):
            os.remove(TIMING_LOG_FILE)
        print("🔄 Data timing direset untuk session baru.")
    except Exception as e:
        print(f"⚠️ Gagal reset timing data: {e}")

def load_timing_data():
    try:
        if os.path.exists(TIMING_LOG_FILE):
            with open(TIMING_LOG_FILE, 'r') as f:
                return json.load(f)
    except Exception as e:
        print(f"⚠️ Gagal load timing data: {e}")
    return {"processes": [], "session_start": None}

def get_today_date():
    return datetime.now().strftime('%d-%m-%Y')

def calculate_average_time(processes):
    if not processes:
        return 0, "0 detik"
    total_duration = sum(p["duration_seconds"] for p in processes)
    average_duration = total_duration / len(processes)
    minutes, seconds = divmod(average_duration, 60)
    if minutes > 0:
        return average_duration, f"{int(minutes)} menit {seconds:.2f} detik"
    return average_duration, f"{seconds:.2f} detik"

def group_processes_by_date(processes):
    grouped = {}
    for process in processes:
        date = process.get("date", "unknown")
        grouped.setdefault(date, []).append(process)
    return grouped

def display_timing_summary():
    timing_data = load_timing_data()
    processes = timing_data.get("processes", [])
    if not processes:
        print("📊 Belum ada data proses yang tercatat.")
        return

    grouped = group_processes_by_date(processes)
    today = get_today_date()

    print(f"\n{'=' * 70}")
    print("📊 RINGKASAN WAKTU PROSES")
    print(f"{'=' * 70}")

    if today in grouped:
        print(f"\n📅 HARI INI ({today}) - {len(grouped[today])} proses")
        print("-" * 60)
        for process in grouped[today]:
            jam = process["start_time"].split()[1]
            print(f"🔢 Proses {process['process_number']}: {process['duration_formatted']} ({jam})")
        _, avg = calculate_average_time(grouped[today])
        print(f"\n⏱️ Rata-rata hari ini: {avg}")

    other_dates = [d for d in grouped if d != today]
    other_dates.sort(reverse=True)
    for date in other_dates[:3]:
        print(f"\n📅 {date} - {len(grouped[date])} proses")
        print("-" * 60)
        shown = grouped[date][-3:]
        if len(grouped[date]) > 3:
            print(f"... {len(grouped[date]) - 3} proses lainnya ...")
        for process in shown:
            jam = process["start_time"].split()[1]
            print(f"🔢 Proses {process['process_number']}: {process['duration_formatted']} ({jam})")
        _, avg = calculate_average_time(grouped[date])
        print(f"\n⏱️ Rata-rata: {avg}")

    if len(other_dates) > 3:
        print(f"\n... {len(other_dates) - 3} hari lainnya tidak ditampilkan ...")

    _, avg_all = calculate_average_time(processes)
    print(f"\n{'=' * 70}")
    print("📈 STATISTIK KESELURUHAN")
    print(f"📊 Total proses: {len(processes)}")
    print(f"📅 Total hari: {len(grouped)}")
    print(f"⏱️ Rata-rata keseluruhan: {avg_all}")
    print(f"{'=' * 70}")

def cleanup():
    global remote_proc, main_thread
    print("\n🔌 Menghentikan proses...")

    display_timing_summary()

    if not DEBUG_MODE:
        try:
            subprocess.run(["ssh", f"{RASPI_USER}@{RASPI_HOST}", "pkill -f detection.py"], check=True)
            print("✅ Remote process dihentikan.")
        except subprocess.CalledProcessError:
            print("⚠️ Gagal hentikan remote process.")

        if remote_proc:
            remote_proc.terminate()
            try:
                remote_proc.wait(timeout=10)
            except subprocess.TimeoutExpired:
                remote_proc.kill()
            remote_proc = None

    if main_thread and main_thread.is_alive():
        main_thread.join()
    main_thread = None

    print("🧹 Cleanup selesai.")

def handle_signal(signum, frame):
    global running
    print(f"\n📴 Sinyal ({signum}) diterima. Shutdown...")
    running = False
    cleanup()
    sys.exit(0)

signal.signal(signal.SIGINT, handle_signal)
signal.signal(signal.SIGTERM, handle_signal)

def run_remote_and_main(id_jadwal):
    global remote_proc, running

    if not DEBUG_MODE:
        print(f"📡 Menjalankan object detection di Raspberry Pi (interval {INTERVAL_MIN} menit)...")
        remote_proc = subprocess.Popen(["ssh", f"{RASPI_USER}@{RASPI_HOST}", RASPI_COMMAND])
    else:
        print("🐞 Mode DEBUG aktif — hanya proses lokal (main.py) dijalankan jika ada folder baru.")

    last_seen = get_latest_subfolder(RECEIVED_DIR)
    retry = 0
    process_count = 0

    while running:
        current = get_latest_subfolder(RECEIVED_DIR)
        if current and current != last_seen:
            process_count += 1
            print(f"\n📥 Folder baru: {current}")
            print(f"▶️ Menjalankan main.py (proses ke-{process_count})...")

            try:
                env = os.environ.copy()
                env["ID_JADWAL"] = str(id_jadwal)
                subprocess.run(LOCAL_MAIN_COMMAND, check=True, cwd=LOCAL_MAIN_DIR, env=env)
                print("✅ main.py selesai.")
                retry = 0
                print("\n" + "="*50)
                display_timing_summary()
                print("="*50)
            except subprocess.CalledProcessError as e:
                retry += 1
                print(f"❌ Gagal main.py (percobaan ke-{retry}): {e}")
                if retry >= MAX_RETRIES:
                    print("⛔ Terlalu banyak kegagalan.")
                    break
            last_seen = current
        time.sleep(CHECK_INTERVAL)

    cleanup()

# === MAIN LOOP ===
if __name__ == "__main__":
    if args.reset_timing:
        reset_timing_data()

    if DEBUG_MODE:
        print("🛠️  Menjalankan dalam mode DEBUG — Raspberry Pi akan diabaikan.")
    else:
        print("⏳ Menunggu waktu sesuai jadwal...")

    session_start_time = datetime.now()
    print(f"🚀 Session dimulai: {session_start_time.strftime('%d-%m-%Y %H:%M:%S')}")

    try:
        last_active_jadwal = None

        while True:
            id_jadwal, mapel = is_in_schedule()

            if id_jadwal:
                if id_jadwal != last_active_jadwal:
                    print(f"🟢 Jadwal aktif: {mapel} (ID {id_jadwal})")
                    last_active_jadwal = id_jadwal

                if not running and main_thread is None:
                    print("▶️ Memulai proses...")
                    running = True
                    main_thread = threading.Thread(target=run_remote_and_main, args=(id_jadwal,))
                    main_thread.start()

            else:
                if last_active_jadwal is not None:
                    print("🔴 Di luar jadwal.")
                    last_active_jadwal = None

                if running:
                    running = False
                    cleanup()

            time.sleep(10)

    except KeyboardInterrupt:
        print("🛑 Dihentikan.")
        running = False
        cleanup()