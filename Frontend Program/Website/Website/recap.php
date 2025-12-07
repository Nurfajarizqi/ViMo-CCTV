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

// Ambil parameter kelas dari URL
$kelas = isset($_GET['kelas']) ? intval($_GET['kelas']) : 1;

// Validasi kelas
if ($kelas < 1 || $kelas > 6) {
    $kelas = 1;
}

// Ambil data mata pelajaran dari database
$queryMataPelajaran = "SELECT id, nama_mata_pelajaran FROM mata_pelajaran ORDER BY id";
$resultMataPelajaran = $conn->query($queryMataPelajaran);

$mataPelajaran = [];
while ($row = $resultMataPelajaran->fetch_assoc()) {
    $mataPelajaran[] = $row;
}

// Ambil nama kelas dari database
$queryKelas = "SELECT nama_kelas FROM kelas WHERE id = ?";
$stmtKelas = $conn->prepare($queryKelas);
$stmtKelas->bind_param("i", $kelas);
$stmtKelas->execute();
$resultKelas = $stmtKelas->get_result();
$namaKelas = ($resultKelas->num_rows > 0) ? $resultKelas->fetch_assoc()['nama_kelas'] : "Kelas $kelas";

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SDN X Jogja (Recap)</title>
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
            justify-content: center;
            align-items: center;
            padding: 30px;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            margin-left: 20px;
            text-align: center;
        }

        #judul_kelas {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        #bot_bar {
            background-color: #ffffff;
            padding: 10px;
            text-align: center;
            font-size: 12px;
            color: #635f5f;
        }

        .mata-pelajaran {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 30px 0;
            width: 100%;
            max-width: 500px;
        }

        .pelajaran-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #f9f5ed;
            border: 2px solid #7c44f5;
            padding: 25px;
            font-weight: bold;
            border-radius: 15px;
            cursor: pointer;
            color: #7c44f5;
            transition: all 0.3s ease;
            height: 150px;
            width: 100%;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .pelajaran-btn:hover {
            background-color: #7c44f5;
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }

        .pelajaran-icon {
            font-size: 40px;
            margin-bottom: 10px;
            display: block;
        }

        .pelajaran-text {
            font-size: 18px;
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
            }

            #judul_kelas {
                font-size: 18px;
                margin-bottom: 15px;
            }

            .mata-pelajaran {
                grid-template-columns: 1fr;
                gap: 15px;
                max-width: 100%;
            }

            .pelajaran-btn {
                padding: 15px;
                height: 120px;
            }

            .pelajaran-icon {
                font-size: 30px;
            }

            .pelajaran-text {
                font-size: 16px;
            }

            #bot_bar {
                font-size: 11px;
                padding: 8px;
            }
        }
    </style>
</head>

<body>
    <div id="top_bar">
        <a href="<?= getHomePageByRole($_SESSION['role']) ?>">
            <img src="image/slogan.png" alt="Logo Tut Wuri Handayani" />
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
                <a href="recap.php?kelas=<?= $i ?>" class="kelas-btn <?= ($kelas == $i) ? 'active' : '' ?>" data-kelas="<?= $i ?>">
                    Kelas <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>

        <div id="right">
            <div id="judul_kelas">Rekapitulasi Data di <?= $namaKelas ?></div>
            <div class="mata-pelajaran">
                <?php
                // FIXED: Ikon untuk setiap mata pelajaran berdasarkan ID yang benar
                $icons = [
                    1 => ['icon' => '+−×÷', 'file' => 'matematika'],  // ID 1 = Matematika
                    2 => ['icon' => '📚', 'file' => 'bahasa'],           // ID 2 = IPS  
                    3 => ['icon' => '🧪', 'file' => 'ipa'],           // ID 3 = IPA
                    4 => ['icon' => '🌍', 'file' => 'ips']         // ID 4 = Bahasa
                ];
                
                foreach ($mataPelajaran as $mp): 
                    $icon = $icons[$mp['id']] ?? ['icon' => '📝', 'file' => strtolower($mp['nama_mata_pelajaran'])];
                ?>
                    <button class="pelajaran-btn" onclick="navigateTo('<?= $icon['file'] ?>', <?= $mp['id'] ?>)">
                        <span class="pelajaran-icon"><?= $icon['icon'] ?></span>
                        <span class="pelajaran-text"><?= $mp['nama_mata_pelajaran'] ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div id="bot_bar">
        <div>Copyright &copy; 2025</div>
        <div>SD Negeri X Yogyakarta</div>
    </div>

    <script>
        let kelasTerpilih = <?= $kelas ?>;

        function navigateTo(mataPelajaran, idMataPelajaran) {
            window.location.href = `${mataPelajaran}.php?kelas=${kelasTerpilih}&mata_pelajaran=${idMataPelajaran}`;
        }
    </script>
</body>
</html>