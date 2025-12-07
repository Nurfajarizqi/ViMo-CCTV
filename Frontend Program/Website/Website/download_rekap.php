<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

// Get selected parameters
$kelas_id = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 1;
$mata_pelajaran_id = isset($_GET['mata_pelajaran']) ? (int)$_GET['mata_pelajaran'] : 1;

// Get class name and subject name for display
$kelas_query = $conn->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
$kelas_query->bind_param("i", $kelas_id);
$kelas_query->execute();
$kelas_result = $kelas_query->get_result();
$kelas_row = $kelas_result->fetch_assoc();
$kelas_nama = $kelas_row['nama_kelas'] ?? "Kelas $kelas_id";

$mapel_query = $conn->prepare("SELECT nama_mata_pelajaran FROM mata_pelajaran WHERE id = ?");
$mapel_query->bind_param("i", $mata_pelajaran_id);
$mapel_query->execute();
$mapel_result = $mapel_query->get_result();
$mapel_row = $mapel_result->fetch_assoc();
$mata_pelajaran_nama = $mapel_row['nama_mata_pelajaran'] ?? "Mata Pelajaran $mata_pelajaran_id";

// Get student predictions data
$query = "
    SELECT rp.*, s.nama_lengkap, s.nisn, k.nama_kelas, mp.nama_mata_pelajaran, j.hari, j.jam_mulai, j.jam_selesai
    FROM rekapitulasi_prediksi rp
    JOIN siswa s ON rp.id_siswa = s.id
    JOIN kelas k ON s.id_kelas = k.id
    JOIN jadwal j ON rp.id_jadwal = j.id
    JOIN mata_pelajaran mp ON j.id_mata_pelajaran = mp.id
    WHERE s.id_kelas = ? AND j.id_mata_pelajaran = ?
    ORDER BY rp.created_at DESC
";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $kelas_id, $mata_pelajaran_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Tidak ada data untuk $kelas_nama Mata Pelajaran $mata_pelajaran_nama");
}

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;

$spreadsheet = new Spreadsheet();
$spreadsheet->removeSheetByIndex(0);

$dataPerBulan = [];
while ($row = $result->fetch_assoc()) {
    $bulanTahun = date('Y-m', strtotime($row['created_at']));
    $dataPerBulan[$bulanTahun][] = $row;
}

foreach ($dataPerBulan as $bulanTahun => $dataBulanan) {
    $worksheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $bulanTahun);
    $spreadsheet->addSheet($worksheet);
    $spreadsheet->setActiveSheetIndexByName($bulanTahun);
    $sheet = $spreadsheet->getActiveSheet();

    // Get unique dates
    $tanggalUnik = [];
    foreach ($dataBulanan as $row) {
        $tgl = date('Y-m-d', strtotime($row['created_at']));
        $tanggalUnik[$tgl] = true;
    }
    $tanggalList = array_keys($tanggalUnik);

    // Get attendance data for these dates - FIXED QUERY
    $jumlahHadirPerTanggal = [];
    
    if (!empty($tanggalList)) {
        // Build the query with proper parameter binding
        $placeholders = implode(',', array_fill(0, count($tanggalList), '?'));
        
        $stmtHadirQuery = "
            SELECT DATE(rk.created_at) as tanggal, rk.jumlah_siswa_hadir
            FROM rekapitulasi_kehadiran rk
            JOIN jadwal j ON rk.id_jadwal = j.id
            WHERE j.id_kelas = ? AND j.id_mata_pelajaran = ? AND DATE(rk.created_at) IN ($placeholders)
            ORDER BY rk.created_at DESC
        ";
        
        $stmtHadir = $conn->prepare($stmtHadirQuery);
        
        // Create proper parameter types string: 'ii' for kelas_id and mata_pelajaran_id, then 's' for each date
        $paramTypes = 'ii' . str_repeat('s', count($tanggalList));
        
        // Create parameters array: kelas_id, mata_pelajaran_id, then all dates
        $params = array_merge([$kelas_id, $mata_pelajaran_id], $tanggalList);
        
        // Bind parameters
        $stmtHadir->bind_param($paramTypes, ...$params);
        $stmtHadir->execute();
        $resHadir = $stmtHadir->get_result();

        while ($row = $resHadir->fetch_assoc()) {
            $tgl = $row['tanggal'];
            if (!isset($jumlahHadirPerTanggal[$tgl])) {
                $jumlahHadirPerTanggal[$tgl] = $row['jumlah_siswa_hadir'];
            }
        }
    }
    
    // Fill missing dates with 'Tidak diketahui'
    foreach ($tanggalList as $tgl) {
        if (!isset($jumlahHadirPerTanggal[$tgl])) {
            $jumlahHadirPerTanggal[$tgl] = 'Tidak diketahui';
        }
    }

    // Header rekapitulasi
    $sheet->setCellValue('A1', 'No');
    $sheet->setCellValue('B1', 'Nama Lengkap');
    $sheet->setCellValue('C1', 'NISN');
    $sheet->setCellValue('D1', 'Prediksi Nama');
    $sheet->setCellValue('E1', 'Confidence Nama');
    $sheet->setCellValue('F1', 'Emosi');
    $sheet->setCellValue('G1', 'Confidence Emosi');
    $sheet->setCellValue('H1', 'Waktu Deteksi');
    $sheet->setCellValue('I1', 'Kelas');
    $sheet->setCellValue('J1', 'Mata Pelajaran');
    $sheet->setCellValue('K1', 'Hari');
    $sheet->setCellValue('L1', 'Jam Mulai');
    $sheet->setCellValue('M1', 'Jam Selesai');

    $rowNum = 2;
    $no = 1;
    foreach ($dataBulanan as $row) {
        $sheet->setCellValue("A{$rowNum}", $no++);
        $sheet->setCellValue("B{$rowNum}", $row['nama_lengkap']);
        $sheet->setCellValue("C{$rowNum}", $row['nisn']);
        $sheet->setCellValue("D{$rowNum}", $row['predicted_name']);
        $sheet->setCellValue("E{$rowNum}", $row['confidence_name']);
        $sheet->setCellValue("F{$rowNum}", $row['predicted_emotion']);
        $sheet->setCellValue("G{$rowNum}", $row['confidence_emotion']);
        $sheet->setCellValue("H{$rowNum}", $row['created_at']);
        $sheet->setCellValue("I{$rowNum}", $row['nama_kelas']);
        $sheet->setCellValue("J{$rowNum}", $row['nama_mata_pelajaran']);
        $sheet->setCellValue("K{$rowNum}", $row['hari']);
        $sheet->setCellValue("L{$rowNum}", $row['jam_mulai']);
        $sheet->setCellValue("M{$rowNum}", $row['jam_selesai']);
        $rowNum++;
    }

    // Header jumlah hadir
    $sheet->setCellValue('O1', 'Tanggal');
    $sheet->setCellValue('P1', 'Jumlah Hadir');
    $rowJH = 2;
    foreach ($jumlahHadirPerTanggal as $tgl => $jumlah) {
        $sheet->setCellValue("O{$rowJH}", $tgl);
        $sheet->setCellValue("P{$rowJH}", $jumlah);
        $rowJH++;
    }

    // Auto-size kolom
    foreach (range('A', 'P') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Styling: bold dan border
    $headerStyle = [
        'font' => ['bold' => true],
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ];
    $dataStyle = [
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ];

    $sheet->getStyle("A1:M1")->applyFromArray($headerStyle);
    $sheet->getStyle("A2:M" . ($rowNum - 1))->applyFromArray($dataStyle);

    $sheet->getStyle("O1:P1")->applyFromArray($headerStyle);
    $sheet->getStyle("O2:P" . ($rowJH - 1))->applyFromArray($dataStyle);
}

$spreadsheet->setActiveSheetIndex(0);
$filename = "rekap_{$kelas_nama}_{$mata_pelajaran_nama}_" . date('Y-m-d') . ".xlsx";

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>