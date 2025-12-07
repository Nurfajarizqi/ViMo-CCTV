<?php
include 'db.php';

// Ambil parameter dari URL
$kelas = $_GET['kelas'] ?? 1;
$id_mata_pelajaran = $_GET['mata_pelajaran'] ?? 1; // Default: ID 1
$tanggal = $_GET['tanggal'] ?? date('Y-m-d'); // Default: hari ini

header('Content-Type: application/json');

function getEmotionData($conn, $kelas, $id_mata_pelajaran, $tanggal) {
    $data = [
        'positif' => 0,
        'negatif' => 0,
        'tanggal' => $tanggal,
        'is_today' => ($tanggal == date('Y-m-d'))
    ];

    $query = "
        SELECT 
            rp.id_siswa,
            rp.predicted_emotion,
            COUNT(*) as jumlah,
            MAX(rp.created_at) as last_occurrence
        FROM 
            rekapitulasi_prediksi rp
        JOIN 
            jadwal j ON rp.id_jadwal = j.id
        WHERE 
            j.id_kelas = ? AND 
            j.id_mata_pelajaran = ? AND
            DATE(rp.created_at) = ?
        GROUP BY 
            rp.id_siswa, rp.predicted_emotion
        ORDER BY 
            rp.id_siswa, jumlah DESC, last_occurrence DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("iis", $kelas, $id_mata_pelajaran, $tanggal);
    $stmt->execute();
    $result = $stmt->get_result();

    // Jika tidak ada data, cari tanggal terakhir yang ada data
    if ($result->num_rows == 0) {
        $lastDateQuery = "
            SELECT DATE(rp.created_at) as last_date 
            FROM rekapitulasi_prediksi rp
            JOIN jadwal j ON rp.id_jadwal = j.id
            WHERE j.id_kelas = ? AND j.id_mata_pelajaran = ?
            GROUP BY DATE(rp.created_at)
            ORDER BY DATE(rp.created_at) DESC 
            LIMIT 1
        ";

        $lastDateStmt = $conn->prepare($lastDateQuery);
        $lastDateStmt->bind_param("ii", $kelas, $id_mata_pelajaran);
        $lastDateStmt->execute();
        $lastDateResult = $lastDateStmt->get_result();

        if ($lastDateResult->num_rows > 0) {
            $dateRow = $lastDateResult->fetch_assoc();
            $tanggal = $dateRow['last_date'];
            $data['tanggal'] = $tanggal;
            $data['is_today'] = false;

            // Jalankan query ulang dengan tanggal terakhir
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iis", $kelas, $id_mata_pelajaran, $tanggal);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            // Tidak ada data sama sekali
            return $data;
        }
    }

    // Ambil emosi dominan per siswa
    $studentEmotions = [];
    $currentStudent = null;
    $maxCount = 0;
    $dominantEmotion = null;
    $lastOccurrence = null;

    while ($row = $result->fetch_assoc()) {
        $studentId = $row['id_siswa'];
        $emotion = strtolower($row['predicted_emotion']);
        $count = $row['jumlah'];
        $occurrence = $row['last_occurrence'];
        
        if ($currentStudent !== $studentId) {
            // Jika ini siswa baru, simpan emosi dominan siswa sebelumnya (jika ada)
            if ($currentStudent !== null) {
                $studentEmotions[$currentStudent] = $dominantEmotion;
            }
            
            // Reset untuk siswa baru
            $currentStudent = $studentId;
            $maxCount = $count;
            $dominantEmotion = $emotion;
            $lastOccurrence = $occurrence;
        } else {
            // Untuk siswa yang sama, periksa prioritas
            if ($count > $maxCount || 
                ($count == $maxCount && $occurrence > $lastOccurrence)) {
                $maxCount = $count;
                $dominantEmotion = $emotion;
                $lastOccurrence = $occurrence;
            }
        }
    }

    // Simpan emosi dominan siswa terakhir
    if ($currentStudent !== null) {
        $studentEmotions[$currentStudent] = $dominantEmotion;
    }

    // Hitung total emosi dominan
    foreach ($studentEmotions as $emotion) {
        if ($emotion === 'positif') {
            $data['positif']++;
        } elseif ($emotion === 'negatif') {
            $data['negatif']++;
        }
    }

    return $data;
}

echo json_encode(getEmotionData($conn, $kelas, $id_mata_pelajaran, $tanggal));
?>