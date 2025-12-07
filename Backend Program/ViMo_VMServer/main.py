import os
import sys
import time
import json
import subprocess
from datetime import datetime
from multiprocessing import Process

# File untuk menyimpan data waktu proses
TIMING_LOG_FILE = "process_timing.json"

def load_timing_data():
    """Load data waktu proses dari file"""
    try:
        if os.path.exists(TIMING_LOG_FILE):
            with open(TIMING_LOG_FILE, 'r') as f:
                return json.load(f)
    except Exception as e:
        print(f"⚠️ Gagal load timing data: {e}")
    return {"processes": [], "session_start": None}

def save_timing_data(data):
    """Simpan data waktu proses ke file"""
    try:
        with open(TIMING_LOG_FILE, 'w') as f:
            json.dump(data, f, indent=2)
    except Exception as e:
        print(f"⚠️ Gagal save timing data: {e}")

def add_process_timing(process_number, start_time, end_time, duration):
    """Tambahkan data waktu proses baru"""
    timing_data = load_timing_data()
    
    # Ekstrak tanggal dari start_time (format: dd-mm-yyyy HH:MM:SS)
    date_part = start_time.split()[0]  # Ambil bagian tanggal saja
    
    process_info = {
        "process_number": process_number,
        "start_time": start_time,
        "end_time": end_time,
        "duration_seconds": duration,
        "duration_formatted": format_duration(duration),
        "date": date_part  # Tambahkan informasi tanggal
    }
    
    timing_data["processes"].append(process_info)
    save_timing_data(timing_data)
    
    return process_info

def format_duration(seconds):
    """Format durasi dalam menit dan detik"""
    minutes, secs = divmod(seconds, 60)
    if minutes > 0:
        return f"{int(minutes)} menit {secs:.2f} detik"
    else:
        return f"{secs:.2f} detik"

def get_process_number():
    """Dapatkan nomor proses berdasarkan jumlah proses sebelumnya"""
    timing_data = load_timing_data()
    return len(timing_data["processes"]) + 1

def calculate_average_time(processes=None):
    """Hitung rata-rata waktu proses"""
    if processes is None:
        timing_data = load_timing_data()
        processes = timing_data["processes"]
    
    if not processes:
        return 0, "0 detik"
    
    total_duration = sum(p["duration_seconds"] for p in processes)
    average_duration = total_duration / len(processes)
    
    return average_duration, format_duration(average_duration)

def get_today_date():
    """Dapatkan tanggal hari ini dalam format dd-mm-yyyy"""
    return datetime.now().strftime('%d-%m-%Y')

def run_script(script_name, description=None, extra_env=None):
    if description is None:
        description = script_name

    print(f"\n{'=' * 80}")
    print(f"🚀 Menjalankan {description}")
    print(f"{'=' * 80}")

    start_time = time.time()
    try:
        env = os.environ.copy()
        if extra_env:
            env.update(extra_env)

        process = subprocess.Popen(
            [sys.executable, script_name],
            stdout=subprocess.PIPE,
            stderr=subprocess.STDOUT,
            text=True,
            env=env
        )
        for line in process.stdout:
            print(line, end='')
        return_code = process.wait()
        duration = time.time() - start_time

        if return_code == 0:
            print(f"✅ {description} selesai ({duration:.2f} detik)")
            return True, duration
        else:
            print(f"❌ {description} gagal (kode: {return_code}, waktu: {duration:.2f} detik)")
            return False, duration
    except Exception as e:
        print(f"❌ Gagal menjalankan {script_name}: {e}")
        return False, 0

def main():
    total_start = time.time()
    process_start_time = datetime.now()
    
    print(f"\n📋 SISTEM PENGENALAN WAJAH & EKSPRESI")
    print(f"🕒 Mulai: {process_start_time.strftime('%d-%m-%Y %H:%M:%S')}")

    id_jadwal = os.getenv("ID_JADWAL")
    if not id_jadwal:
        print("❌ Environment variable ID_JADWAL tidak ditemukan. Program dihentikan.")
        return

    print(f"🧾 ID Jadwal aktif: {id_jadwal}")
    process_number = get_process_number()
    print(f"📊 Proses ke-{process_number}")

    scripts = [
        ("pre_processing.py", "Pre-processing"),
        ("face_recognition.py", "Face Recognition"),
        ("facial_expression.py", "Facial Expression"),
        ("post_processing.py", "Post-processing")
    ]
    env_vars = {"ID_JADWAL": id_jadwal}

    durations = {}  # Untuk menyimpan durasi per tahap

    # Step 1: Pre-processing
    success, duration = run_script(*scripts[0], extra_env=env_vars)
    durations["pre_processing"] = duration
    if not success:
        print("⛔ Pre-processing gagal. Program dihentikan.")
        return

    # Step 2 & 3: Parallel
    # Untuk mencatat durasi saat parallel, kita harus jalankan terpisah
    def wrapper(script, label, output_dict):
        success, dur = run_script(*script, extra_env=env_vars)
        output_dict[label] = dur

    from multiprocessing import Manager
    with Manager() as manager:
        shared_durations = manager.dict()
        p1 = Process(target=wrapper, args=(scripts[1], "face_recognition", shared_durations))
        p2 = Process(target=wrapper, args=(scripts[2], "facial_expression", shared_durations))
        p1.start(); p2.start()
        p1.join(); p2.join()
        durations["face_recognition"] = shared_durations.get("face_recognition", 0)
        durations["facial_expression"] = shared_durations.get("facial_expression", 0)

    # Step 4: Post-processing
    success, duration = run_script(*scripts[3], extra_env=env_vars)
    durations["post_processing"] = duration
    if not success:
        print("⚠️ Post-processing gagal. Data tidak tersimpan ke database.")

    total_duration = time.time() - total_start
    process_end_time = datetime.now()
    durations["total"] = total_duration

    process_info = {
        "process_number": process_number,
        "start_time": process_start_time.strftime('%d-%m-%Y %H:%M:%S'),
        "end_time": process_end_time.strftime('%d-%m-%Y %H:%M:%S'),
        "duration_seconds": total_duration,
        "duration_formatted": format_duration(total_duration),
        "date": get_today_date(),
        "durations": {
            k: {
                "seconds": v,
                "formatted": format_duration(v)
            } for k, v in durations.items()
        }
    }

    # Simpan ke file JSON
    timing_data = load_timing_data()
    timing_data["processes"].append(process_info)
    save_timing_data(timing_data)

    # Tampilkan informasi
    print(f"\n📊 INFORMASI WAKTU PROSES")
    print(f"🔢 Proses ke-{process_number}")
    print(f"🕒 Waktu mulai: {process_info['start_time']}")
    print(f"🕐 Waktu selesai: {process_info['end_time']}")
    print(f"⏱️ Durasi total: {process_info['duration_formatted']}")

    for label, data in process_info["durations"].items():
        print(f"  - {label}: {data['formatted']}")

    # Statistik
    all_processes = timing_data["processes"]
    today = get_today_date()
    today_processes = [p for p in all_processes if p["date"] == today]
    
    avg_today_seconds, avg_today_formatted = calculate_average_time(today_processes)
    avg_all_seconds, avg_all_formatted = calculate_average_time(all_processes)
    
    print(f"\n📈 STATISTIK WAKTU PROSES")
    print(f"📅 Hari ini ({today}): {len(today_processes)} proses")
    print(f"⏱️ Rata-rata hari ini: {avg_today_formatted}")
    print(f"📊 Total keseluruhan: {len(all_processes)} proses")
    print(f"⏱️ Rata-rata keseluruhan: {avg_all_formatted}")

if __name__ == "__main__":
    main()
