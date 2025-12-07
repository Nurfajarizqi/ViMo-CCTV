import os
import csv
import glob
import mysql.connector
from folder_organizer import get_latest_subfolder
from result_logger import post_processing
from jadwal import get_id_jadwal_realtime

RECEIVE_DIR = 'received_images'
PREPROCESS_DIR = 'pre_processed_images'
RECOGNITION_DIR = 'face_recognition_images'
EXPRESSION_DIR = 'facial_expression_images'
OUTPUT_BASE_DIR = 'post_processed_images'

receive_sub = get_latest_subfolder(RECEIVE_DIR)
preprocess_sub = get_latest_subfolder(PREPROCESS_DIR)
recognition_sub = get_latest_subfolder(RECOGNITION_DIR)
expression_sub = get_latest_subfolder(EXPRESSION_DIR)

if not all([receive_sub, preprocess_sub, recognition_sub, expression_sub]):
    print("Folder input tidak lengkap. Program dihentikan.")
    exit()

try:
    _, tanggal, jam = os.path.normpath(preprocess_sub).split(os.sep)[-3:]
except Exception as e:
    print(f"Gagal mengambil tanggal dan jam dari path: {e}")
    tanggal, jam = None, None

output_folder = os.path.join(OUTPUT_BASE_DIR, tanggal or "unknown_date", jam or "unknown_time")
os.makedirs(output_folder, exist_ok=True)
print(f"Folder hasil output: {output_folder}")

# ✅ Insert kehadiran
def insert_rekapitulasi_kehadiran(cursor, receive_folder, id_jadwal):
    image_files = glob.glob(os.path.join(receive_folder, '*'))
    jumlah_hadir = len([f for f in image_files if f.lower().endswith(('.png', '.jpg', '.jpeg'))])

    if jumlah_hadir == 0:
        print("Tidak ada gambar ditemukan di folder received_images.")
        return

    cursor.execute("""
        INSERT INTO rekapitulasi_kehadiran (jumlah_siswa_hadir, id_jadwal, created_at)
        VALUES (%s, %s, NOW())
    """, (jumlah_hadir, id_jadwal))
    print(f"- Jumlah hadir berhasil dimasukkan ke rekapitulasi_kehadiran.")

# ✅ Insert rekapitulasi prediksi
def insert_to_rekapitulasi(csv_path, mata_pelajaran, id_jadwal, receive_folder):
    try:
        conn = mysql.connector.connect(
            host="localhost", user="capstone", password="capstone", database="capstone"
        )
        cursor = conn.cursor()

        # Ambil data siswa
        cursor.execute("SELECT id, nama_lengkap FROM siswa")
        siswa_dict = {nama.lower(): id_siswa for id_siswa, nama in cursor.fetchall()}

        inserted_count = 0
        skipped_count = 0

        with open(csv_path, newline='', encoding='utf-8') as f:
            reader = csv.DictReader(f)
            for row in reader:
                nama = row.get("predicted_name", "").strip().lower()
                emosi = row.get("predicted_emotion", "").strip().lower()

                if nama == "unknown" or emosi == "unknown":
                    skipped_count += 1
                    continue

                id_siswa = siswa_dict.get(nama)
                if not id_siswa:
                    skipped_count += 1
                    continue

                cursor.execute("""
                    INSERT INTO rekapitulasi_prediksi (
                        predicted_name, confidence_name,
                        predicted_emotion, confidence_emotion,
                        id_siswa, id_jadwal, created_at
                    ) VALUES (%s, %s, %s, %s, %s, %s, NOW())
                """, (
                    row.get("predicted_name", ""),
                    row.get("recognition_confidence", ""),
                    row.get("predicted_emotion", ""),
                    row.get("expression_confidence", ""),
                    id_siswa,
                    id_jadwal
                ))
                inserted_count += 1

        # Insert juga ke rekapitulasi_kehadiran
        insert_rekapitulasi_kehadiran(cursor, receive_folder, id_jadwal)

        conn.commit()
        print(f"- {inserted_count} data dimasukkan ke rekapitulasi_prediksi.")
        if skipped_count > 0:
            print(f"- {skipped_count} data dilewati karena 'unknown' atau siswa tidak ditemukan.")

    except Exception as e:
        print(f"[ERROR] Gagal insert ke database: {e}")
    finally:
        if conn.is_connected():
            conn.close()

# ✅ Main
if __name__ == "__main__":
    output_csv = post_processing(preprocess_sub, recognition_sub, expression_sub, output_folder)

    # Ganti sesuai id_kelas yang sedang berjalan
    id_kelas = 1
    id_jadwal, mapel = get_id_jadwal_realtime(id_kelas)

    if not id_jadwal:
        print("ID jadwal tidak ditemukan. Program dihentikan.")
        exit()

    print(f"Mata pelajaran: {mapel} | ID Jadwal: {id_jadwal}")

    if output_csv and os.path.exists(output_csv):
        insert_to_rekapitulasi(output_csv, mapel, id_jadwal, receive_sub)
    else:
        print("File CSV hasil tidak ditemukan.")