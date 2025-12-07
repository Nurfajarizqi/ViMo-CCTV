<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

function getHomePageByRole($role) {
    switch ($role) {
        case 'admin':
            return 'homeA.php';
        case 'guru':
            return 'homeG.php';
        case 'petinggi':
            return 'homeP.php';
        default:
            return 'login.php';
    }
}

include 'db.php';

// Get selected class and subject
$kelas_id = isset($_GET['kelas']) ? intval($_GET['kelas']) : 1;
$id_mata_pelajaran = 1; // ID for matematika

// Get class name for display
$kelas_query = $conn->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
$kelas_query->bind_param("i", $kelas_id);
$kelas_query->execute();
$kelas_result = $kelas_query->get_result();
$kelas_row = $kelas_result->fetch_assoc();
$kelas_nama = $kelas_row['nama_kelas'] ?? "Kelas $kelas_id";

// Function to get emotion data
function getEmotionData($conn, $kelas_id, $id_mata_pelajaran, $tanggal = null) {
    if ($tanggal === null) {
        $tanggal = date('Y-m-d');
    }
    
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
    $stmt->bind_param("iis", $kelas_id, $id_mata_pelajaran, $tanggal);
    $stmt->execute();
    $result = $stmt->get_result();

    // If no data, find the last date with data
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
        $lastDateStmt->bind_param("ii", $kelas_id, $id_mata_pelajaran);
        $lastDateStmt->execute();
        $lastDateResult = $lastDateStmt->get_result();

        if ($lastDateResult->num_rows > 0) {
            $dateRow = $lastDateResult->fetch_assoc();
            $tanggal = $dateRow['last_date'];
            $data['tanggal'] = $tanggal;
            $data['is_today'] = false;

            // Run query again with last date
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iis", $kelas_id, $id_mata_pelajaran, $tanggal);
            $stmt->execute();
            $result = $stmt->get_result();
        } else {
            // No data at all
            return $data;
        }
    }

    // Get dominant emotion per student
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
            // If new student, save previous student's dominant emotion (if any)
            if ($currentStudent !== null) {
                $studentEmotions[$currentStudent] = $dominantEmotion;
            }
            
            // Reset for new student
            $currentStudent = $studentId;
            $maxCount = $count;
            $dominantEmotion = $emotion;
            $lastOccurrence = $occurrence;
        } else {
            // For same student, check priority
            if ($count > $maxCount || 
                ($count == $maxCount && $occurrence > $lastOccurrence)) {
                $maxCount = $count;
                $dominantEmotion = $emotion;
                $lastOccurrence = $occurrence;
            }
        }
    }

    // Save last student's dominant emotion
    if ($currentStudent !== null) {
        $studentEmotions[$currentStudent] = $dominantEmotion;
    }

    // Count total dominant emotions
    foreach ($studentEmotions as $emotion) {
        if ($emotion === 'positif') {
            $data['positif']++;
        } elseif ($emotion === 'negatif') {
            $data['negatif']++;
        }
    }

    return $data;
}

// Get initial emotion data
$emotionData = getEmotionData($conn, $kelas_id, $id_mata_pelajaran);
$positif = $emotionData['positif'];
$negatif = $emotionData['negatif'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDN X Jogja (List Siswa)</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; font-family: 'Glacial Indifference', sans-serif; }
        body { 
            display: flex; 
            flex-direction: column; 
            background-repeat: no-repeat; 
            background-size: cover; 
            background-position: center; 
        }
        #top_bar { 
            background-color: #ffffff; 
            display: flex; 
            align-items: center; 
            padding: 10px 20px; 
            gap: 15px; 
        }
        #top_bar img { height: 60px; }
        #top_bar .school-title { 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            letter-spacing: 3px; 
        }
        .logout-btn { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            background: #ffffff; 
            color: #7c44f5; 
            border: 3px solid #7c44f5; 
            border-radius: 20px; 
            padding: 7px 10px; 
            font-size: 15px; 
            letter-spacing: 1px; 
            cursor: pointer; 
            transition: 0.3s ease; 
            margin-left: auto; 
            width: fit-content; 
            text-decoration: none; 
        }
        .logout-btn:hover { 
            background: #7c44f5; 
            color: #ffffff; 
            border: 3px solid #ffffff; 
        }
        .icon-wrapper { 
            position: relative; 
            width: 14px; 
            height: 14px; 
        }
        .icon-wrapper img { 
            position: absolute; 
            top: 0; 
            left: 0; 
            width: 14px; 
            height: 14px; 
            transition: opacity 0.3s ease; 
        }
        .icon-hover { opacity: 0; }
        .logout-btn:hover .icon-hover { opacity: 1; }
        .logout-btn:hover .icon-default { opacity: 0; }
        #main_content { 
            flex: 1; 
            display: flex; 
            flex-direction: row; 
            padding: 20px; 
            background-image: url('image/main_background.png');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
        }
        #left { 
            width: 220px; 
            display: flex; 
            flex-direction: column; 
            padding: 20px; 
            background-color: #ffffff; 
            border-radius: 15px; 
            gap: 10px; 
        }
        .kelas-btn { 
            background-color: #f9f5ed; 
            border: 2px solid #000000; 
            padding: 15px; 
            font-weight: bold; 
            font-size: 13.5px; 
            border-radius: 10px; 
            cursor: pointer; 
            text-align: center; 
            text-decoration: none; 
            color: black; 
        }
        .kelas-btn.active { 
            background-color: #7c44f5; 
            border: 2px solid #ffffff; 
            color: white; 
        }
        #right { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            justify-content: flex-start; 
            align-items: flex-start; 
            padding: 30px; 
            background-color: white; 
            border-radius: 15px; 
            margin-left: 20px; 
            max-height: calc(100vh - 171px); 
            overflow-y: auto; 
        }
        #right::-webkit-scrollbar { width: 8px; }
        #right::-webkit-scrollbar-thumb { 
            background-color: #7c44f5; 
            border-radius: 10px; 
        }
        #right::-webkit-scrollbar-track { 
            background-color: #f1f1f1; 
            border-radius: 10px; 
        }
        #right::-webkit-scrollbar-button { display: none; }
        table { 
            border-collapse: collapse; 
            width: 100%; 
            font-size: 14px; 
            min-width: 600px; 
        }
        th, td { 
            text-align: left; 
            padding: 8px; 
        }
        th { background-color: #f1f1f1; }
        input[readonly] { 
            background-color: #f9f5ed; 
            padding: 5px; 
            border-radius: 8px; 
            border: 1px solid #ccc; 
            width: 100%; 
        }
        #bot_bar { 
            background-color: #ffffff; 
            padding: 10px; 
            text-align: center; 
            font-size: 12px; 
            color: #635f5f; 
        }

    #emotion_chart {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    #judul_kelas {
        align-items: center;
        text-align: center;
        font-size: 24px;
        font-weight: bold;
        margin-bottom: 20px;
    }

    .judul-wrapper {
        width: 100%;
        text-align: center;
        margin-bottom: 20px;
    }

    .emotion-container {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: center;
        width: 100%;
        gap: 50px;
        margin-bottom: 30px;
        flex-wrap: wrap;
    }

    .chart-container {
        width: 300px;
        height: 300px;
        flex-shrink: 0; /* Agar tidak menyusut */
    }

    .info-emotion {
        display: flex;
        flex-direction: column;
        justify-content: center;
        font-size: 12px;
        gap: 8px;
        padding: 20px;
        border-radius: 10px;
        border: 1px solid #ccc;
        background-color: #7c44f5;
        min-width: 200px;
        height: fit-content;
        color: black;
        font-weight: bold;
    }

    .info-emotion div {
        padding: 8px 12px;
        background-color: white;
        border-radius: 8px;
        border: 1px solid #ddd;
    }

    .info-emotion strong {
        display: inline-block;
        width: 100px; /* Lebar tetap untuk label */
    }

    .download-btn {
        background-color: #7c44f5;
        color: white;
        border: none;
        border-radius: 20px;
        padding: 12px 25px;
        font-size: 16px;
        cursor: pointer;
        transition: 0.3s ease;
        text-decoration: none;
        display: inline-block;
        font-weight: bold;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .download-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    .info-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 20px;
    }

    /* Tambahan class untuk loading spinner */
    .loading {
        text-align: center;
        padding: 20px;
        font-weight: bold;
        color: #7c44f5;
    }

    .logout-container{
        margin-left: auto;
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        #top_bar {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
            padding: 10px;
        }

        .logout-container{
            width: 100%;
            display: flex;
            justify-content: flex-end;
            margin-left: 0;
        }

        #top_bar .school-title {
            align-items: flex-start;
        }

        #top_bar img {
            height: 50px;
        }

        .logout-btn {
            padding: 5px 8px;
            font-size: 13px;
            border-width: 2px;
            margin-left: 0;
            margin-top: 10px;
        }

        #main_content {
            flex-direction: column;
            padding: 10px;
            gap: 15px;
        }

        #left {
            width: 100%;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
            padding: 15px;
            margin-bottom: 10px;
        }

        .kelas-btn {
            padding: 10px;
            font-size: 14px;
            min-width: 80px;
            margin: 5px;
        }

        #right {
            margin-left: 0;
            padding: 20px;
            max-height: none;
        }

        #judul_kelas {
            font-size: 18px;
            margin-bottom: 15px;
        }

        .emotion-container {
            flex-direction: column;
            gap: 30px;
        }

        .chart-container {
            width: 100%;
            height: 250px;
            max-width: 250px;
        }

        .info-emotion {
            width: 100%;
            max-width: 280px;
        }

        .download-btn {
            padding: 10px 20px;
            font-size: 14px;
            width: 100%;
            max-width: 280px;
        }

        #bot_bar {
            font-size: 11px;
            padding: 8px;
        }

        #date_info {
            font-size: 16px;
            margin-top: 5px;
            color: #555;
            font-style: italic;
            text-align: center;
        }

        @media (max-width: 768px) {
            #date_info {
                font-size: 14px;
            }
        }
    }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
</head>
<body>
    <div id="top_bar">
        <a href="<?= getHomePageByRole($_SESSION['role']) ?>">
            <img src="image/slogan.png" alt="Logo Tut Wuri Handayani">
        </a>
        <div class="school-title">
            <div style="font-size: 25px; font-weight: 1000;">SD Negeri X</div>
            <div style="font-size: 15px; font-weight: 700;">Yogyakarta</div>
        </div>
        <div class="logout-container">
            <a href="logout.php" class="logout-btn">
                <div class="icon-wrapper">
                    <img src="image/peopleP.png" alt="User" class="icon-default" style="width: 16px; height: 16px;">
                    <img src="image/peopleW2.png" alt="User Hover" class="icon-hover" style="width: 16px; height: 16px;">
                </div>
                <div>Logout</div>
            </a>
        </div>
    </div>

    <div id="main_content">
        <div id="left">
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <a href="matematika.php?kelas=<?= $i ?>" class="kelas-btn <?= ($kelas_id == $i) ? 'active' : '' ?>" data-kelas="<?= $i ?>">
                    Kelas <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>

        <div id="right">
            <div class="judul-wrapper">
                <div id="judul_kelas">Tampilan Emosi Siswa di <?= $kelas_nama ?> (MATEMATIKA)</div>
                <div id="date_info" style="font-size: 16px; margin-top: 5px; <?= $emotionData['is_today'] ? 'display: none;' : '' ?>">
                    Menampilkan data dari <?= date('l, d F Y', strtotime($emotionData['tanggal'])) ?>
                </div>
            </div>
            
            <div class="emotion-container">
                <div class="chart-container">
                    <canvas id="emotion_chart" width="300" height="300"></canvas>
                </div>
                
                <div class="info-container">
                    <div class="info-emotion">
                        <div><strong>Jumlah Siswa:</strong> <span id="jumlah_siswa"><?= $positif + $negatif ?></span></div>
                        <div><strong>Positif:</strong> <span id="jumlah_positif"><?= $positif ?></span></div>
                        <div><strong>Negatif:</strong> <span id="jumlah_negatif"><?= $negatif ?></span></div>
                    </div>
                    <button class="download-btn" onclick="downloadExcel()">Download Rekapitulasi Data</button>
                </div>
            </div>
        </div>
    </div>

    <div id="bot_bar">
        <div>Copyright &copy; 2025</div>
        <div>SD Negeri X Yogyakarta</div>
    </div>

    <script>
        let emotionChart = null;
        const urlParams = new URLSearchParams(window.location.search);
        let currentKelas = urlParams.get('kelas') || 1;
        const idMataPelajaran = 1; // ID untuk matematika
        let isFetching = false;
        let pollingInterval = null;
        let lastDataHash = null; // Untuk mencegah update yang tidak perlu

        // Fungsi untuk membuat hash dari data
        function createDataHash(positif, negatif) {
            return `${positif}-${negatif}`;
        }

        // Fungsi untuk membuat chart baru
        function createChart(positif, negatif) {
            const newHash = createDataHash(positif, negatif);
            
            // Jika data sama dengan sebelumnya, tidak perlu update
            if (lastDataHash === newHash && emotionChart) {
                return;
            }
            
            lastDataHash = newHash;
            
            if (emotionChart) emotionChart.destroy();

            const ctx = document.getElementById("emotion_chart").getContext("2d");
            
            emotionChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Positif', 'Negatif'],
                    datasets: [{
                        data: [positif, negatif],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.7)', // Hijau untuk positif
                            'rgba(255, 99, 132, 0.7)', // Merah untuk negatif
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 99, 132, 1)',
                        ],
                        borderWidth: 1,
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                color: '#333',
                                font: {
                                    size: 14,
                                    family: "'Glacial Indifference', sans-serif"
                                },
                                padding: 20
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const value = context.raw;
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return `${context.label}: ${value} (${percentage}%)`;
                                }
                            },
                            bodyFont: {
                                family: "'Glacial Indifference', sans-serif",
                                size: 14
                            }
                        },
                        datalabels: {
                            formatter: (value, ctx) => {
                                const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                                if (total === 0) return '';
                                const percentage = Math.round((value / total) * 100);
                                return `${value}\n(${percentage}%)`;
                            },
                            color: '#000',
                            font: {
                                weight: 'bold',
                                size: 14,
                                family: "'Glacial Indifference', sans-serif"
                            },
                            anchor: 'center',
                            align: 'center',
                            display: function(context) {
                                return context.dataset.data[context.dataIndex] > 0;
                            }
                        }
                    },
                    animation: {
                        animateScale: true,
                        animateRotate: true,
                        duration: 800 // Durasi animasi yang konsisten
                    }
                },
                plugins: [ChartDataLabels]
            });
            
            // Update informasi di sidebar (tanpa netral)
            const totalSiswa = positif + negatif;
            document.getElementById('jumlah_siswa').textContent = totalSiswa;
            document.getElementById('jumlah_positif').textContent = positif;
            document.getElementById('jumlah_negatif').textContent = negatif;
        }

        // Fungsi untuk mengambil data dari server
        async function loadEmotionData(kelas, forceUpdate = false) {
            if(isFetching && !forceUpdate) return;
            isFetching = true;
            
            try {
                const response = await fetch(
                    `get_data.php?kelas=${kelas}&mata_pelajaran=${idMataPelajaran}&_t=${Date.now()}`
                );
                
                if (!response.ok) throw new Error('Gagal memuat data');
                const data = await response.json();
                
                createChart(data.positif, data.negatif);
                
                // Update date information
                const dateInfo = document.getElementById('date_info');
                if (data.is_today) {
                    dateInfo.style.display = 'none';
                } else {
                    const date = new Date(data.tanggal);
                    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                    dateInfo.textContent = `Menampilkan data dari ${date.toLocaleDateString('id-ID', options)}`;
                    dateInfo.style.display = 'block';
                }
            } catch(error) {
                console.error('Error:', error);
                if (forceUpdate) {
                    alert('Gagal memperbarui data. Silakan coba lagi.');
                }
            } finally {
                isFetching = false;
            }
        }


        // Fungsi untuk download excel
        function downloadExcel() {
            const kelas = currentKelas;
            window.location.href = `download_rekap.php?kelas=${kelas}&mata_pelajaran=${idMataPelajaran}`;
        }

        function startDataPolling() {
            if(pollingInterval) clearInterval(pollingInterval);
            
            pollingInterval = setInterval(() => {
                if(document.visibilityState === 'visible') {
                    loadEmotionData(currentKelas);
                }
            }, 30000); // 30 detik
        }

        // Inisialisasi saat halaman dimuat
        document.addEventListener('DOMContentLoaded', () => {
            // Gunakan data PHP langsung untuk inisialisasi pertama
            createChart(<?= $positif ?>, <?= $negatif ?>);
            
            // Tunggu sebentar sebelum memulai polling untuk menghindari konflik
            setTimeout(() => {
                startDataPolling();
            }, 2000);
            
            document.addEventListener('visibilitychange', () => {
                if(document.visibilityState === 'visible') {
                    // Refresh data saat tab kembali aktif
                    setTimeout(() => {
                        loadEmotionData(currentKelas, true);
                        startDataPolling();
                    }, 500);
                } else {
                    clearInterval(pollingInterval);
                }
            });
        });
    </script>
</body>
</html>