<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDN X Jogja - Live Kehadiran</title>
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
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
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
            background-color: white;
            border-radius: 15px;
            margin-left: 20px;
        }
        .video-container {
            width: 650px;
            aspect-ratio: 16/9;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid #ccc;
            background-color: black;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #judul_kelas {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        #jumlah_hadir {
            margin-top: 15px;
            font-weight: bold;
        }
        .full-but {
            background: #8c52ff;
            color: #ffffff;
            border: 3px solid #ffffff;
            border-radius: 20px;
            padding: 7px 12px;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s ease;
            margin: 0 60px;
        }
        .full-but:hover {
            background: #5e39c8;
        }
        #bot_bar {
            background-color: #ffffff;
            padding: 10px;
            text-align: center;
            font-size: 12px;
            color: #635f5f;
        }

        @media (max-width: 768px) {
            #top_bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
                padding: 10px;
            }
            #top_bar .school-title {
                align-items: flex-start;
            }
            #top_bar img {
                height: 50px;
            }
            .logout-container {
                width: 100%;
                display: flex;
                justify-content: flex-end;
                order: 3; /* Memastikan posisi di bawah */
            }
            .logout-btn {
                margin-top: 5px;
                width: auto;
                padding: 5px 12px;
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
                width: 100%;
                max-width: 100%;
            }
            .video-container {
                width: 100%;
                max-width: 100%;
                aspect-ratio: 16/9;
            }
            #judul_kelas {
                font-size: 18px;
                text-align: center;
                margin-bottom: 15px;
            }
            #jumlah_hadir {
                font-size: 14px;
            }
            .full-but {
                margin: 10px 0;
                padding: 10px 20px;
                font-size: 14px;
                width: 100%;
                max-width: 280px;
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
        <a href="<?= $_SERVER['HTTP_REFERER'] ?? 'home.php' ?>">
            <img src="image/slogan.png" alt="Logo Tut Wuri Handayani">
        </a>
        <div class="school-title">
            <div style="font-size: 25px; font-weight: 1000;">SD Negeri X</div>
            <div style="font-size: 15px; font-weight: 700;">Yogyakarta</div>
        </div>
        <a href="logout.php" class="logout-btn">
            <div class="icon-wrapper">
                <img src="image/peopleP.png" alt="User" class="icon-default" style="width: 16px; height: 16px;">
                <img src="image/peopleW2.png" alt="User Hover" class="icon-hover" style="width: 16px; height: 16px;">
            </div>
            <div>Logout</div>
        </a>
    </div>

    <div id="main_content">
        <div id="left">
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <button class="kelas-btn" onclick="tampilkanKelas(<?= $i ?>)">Kelas <?= $i ?></button>
            <?php endfor; ?>
        </div>

        <div id="right">
            <div id="judul_kelas">Siaran Langsung di Kelas 1</div>
            <div class="video-container" id="video_container">
                <img src="http://10.110.80.238:5000/video_feed" id="live_video" style="width:100%; height:100%; object-fit:contain;">
            </div>
            <p id="jumlah_hadir">Jumlah Siswa yang Hadir: <span id="jumlah_siswa">0</span></p>
            <button onclick="toggleFullScreen()" class="full-but" style="margin-top: 10px;">Fullscreen</button>
        </div>
    </div>

    <div id="bot_bar">
        <div>Copyright &copy; 2025</div>
        <div>SD Negeri X Yogyakarta</div>
    </div>

    <script>
        const kelasData = {
            1: {
                stream_url: "http://10.110.80.238:5000/video_feed",
                jumlah_url: "http://10.110.80.238:5000/person_count"
            },
            2: {
                stream_url: "http://192.168.18.124:5000/video_feed",
                jumlah_url: "http://192.168.18.124:5000/person_count"
            },
            3: {
                stream_url: "http://192.168.18.125:5000/video_feed",
                jumlah_url: "http://192.168.18.125:5000/person_count"
            },
            4: {
                stream_url: "http://192.168.18.126:5000/video_feed",
                jumlah_url: "http://192.168.18.126:5000/person_count"
            },
            5: {
                stream_url: "http://192.168.18.127:5000/video_feed",
                jumlah_url: "http://192.168.18.127:5000/person_count"
            },
            6: {
                stream_url: "http://192.168.18.128:5000/video_feed",
                jumlah_url: "http://192.168.18.128:5000/person_count"
            }
        };

        let currentKelas = 1;

        function tampilkanKelas(nomor) {
            currentKelas = nomor;

            document.getElementById('judul_kelas').innerText = `Siaran Langsung di Kelas ${nomor}`;
            document.getElementById('live_video').src = kelasData[nomor].stream_url;
            fetchJumlahSiswa();

            document.querySelectorAll('.kelas-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.kelas-btn')[nomor - 1].classList.add('active');
        }

        function fetchJumlahSiswa() {
            const url = kelasData[currentKelas].jumlah_url;
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    document.getElementById("jumlah_siswa").innerText = data.person_count || "0";
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById("jumlah_siswa").innerText = "0";
                });
        }

        setInterval(fetchJumlahSiswa, 5000); // Update setiap 5 detik

        function toggleFullScreen() {
            const elem = document.getElementById("video_container");
            if (!document.fullscreenElement) {
                elem.requestFullscreen().catch(err => {
                    alert(`Gagal masuk fullscreen: ${err.message}`);
                });
            } else {
                document.exitFullscreen();
            }
        }

        // Set default saat pertama dibuka
        tampilkanKelas(1);
    </script>
</body>
</html>