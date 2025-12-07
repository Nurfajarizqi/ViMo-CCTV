import mysql.connector
from datetime import datetime

hari_mapping = {
    "Monday": "senin", "Tuesday": "selasa", "Wednesday": "rabu",
    "Thursday": "kamis", "Friday": "jumat", "Saturday": "sabtu", "Sunday": "minggu"
}

def get_id_jadwal_realtime(id_kelas):
    try:
        now = datetime.now()
        hari = hari_mapping[now.strftime("%A")]
        jam_sekarang = now.strftime("%H:%M:%S")

        conn = mysql.connector.connect(
            host="localhost",
            user="capstone",
            password="capstone",  # sesuaikan jika perlu
            database="capstone"
        )
        cursor = conn.cursor()

        query = """
            SELECT j.id, mp.nama_mata_pelajaran
            FROM jadwal j
            JOIN mata_pelajaran mp ON j.id_mata_pelajaran = mp.id
            WHERE j.hari = %s
              AND j.id_kelas = %s
              AND %s BETWEEN j.jam_mulai AND j.jam_selesai
            LIMIT 1
        """
        cursor.execute(query, (hari, id_kelas, jam_sekarang))
        result = cursor.fetchone()

        cursor.close()
        conn.close()

        if result:
            return result[0], result[1]  # id_jadwal, nama_mapel

    except mysql.connector.Error as err:
        print(f"[DB ERROR] {err}")
    except Exception as e:
        print(f"[ERROR] {e}")

    return None, None