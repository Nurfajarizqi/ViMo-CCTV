<?php
session_start();

// Check if user is logged in
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
    <title>SDN X Jogja (Home)</title>
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
            padding: 9px 20px; 
            gap: 15px; 
        }
        #top_bar img { 
            height: 60px; 
            margin-top: 1px; 
            margin-bottom: 5px; 
        }
        #top_bar .school-title { 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            letter-spacing: 3px; 
        }
        #main_content { 
            flex: 1; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            gap: 50px; 
            padding: 20px; 
            background-image: url('image/main_background.png');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
        }
        #left, #right { 
            flex: 1; 
            display: flex; 
            flex-direction: column; 
            justify-content: center; 
            color: white;
            text-align: left; 
            padding: 20px; 
        }
        #left { margin-left: 70px; }
        #right img { 
            width: 550px; 
            margin-right: 50px; 
        }
        #left img { 
            width: 140px; 
            height: 70px; 
        }
        .tagline { 
            background: #fff6e6; 
            color: #6c5ce7; 
            padding: 10px 20px; 
            border-radius: 20px; 
            font-size: 15px; 
            font-weight: normal; 
            width: 250px; 
            text-align: center; 
            margin: 0 0 10px 30px; 
        }
        .title { 
            font-size: 35px; 
            font-weight: 700; 
            letter-spacing: 2px; 
            margin: 0 0 5px 30px; 
        }
        .subtitle { 
            font-size: 25px; 
            font-style: italic; 
            letter-spacing: 2px; 
            margin: 0 0 50px 30px; 
        }
        .card-container { 
            display: flex; 
            margin-top: 0.0005rem; 
            margin-left: 45px; 
        }
        .card { 
            background: white; 
            color: black; 
            padding: 1.2rem; 
            border-radius: 20px; 
            margin-right: 1rem; 
            width: 250px; 
            height: 150px; 
            text-align: center; 
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); 
            cursor: pointer; 
            text-decoration: none; 
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
        #bot_bar { 
            background-color: #ffffff; 
            padding: 10px; 
            text-align: center; 
            font-size: 12px; 
            color: #635f5f; 
        }
        a { text-decoration: none; }

         /* ✅ Tambahan untuk tampilan Mobile */
        @media (max-width: 768px) {
            #main_content {
                flex-direction: column;
                padding: 10px;
                gap: 30px;
            }
            #left, #right {
                margin: 0;
                padding: 10px;
                align-items: center;
                text-align: center;
                width: 100%;
            }
            #left {
                margin-left: 0;
            }
            #right img {
                width: 80%;
                margin: 0 auto;
                padding: 0;
                margin-left: 0;
            }
            .card-container {
                flex-direction: column;
                align-items: center;
                margin: 0;
                gap: 15px;
                width: 100%;
            }
            .card {
                width: 90%;
                height: auto;
                margin: 0 auto;
                padding: 1.2rem;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
            }
            .title, .subtitle, .tagline {
                margin: 10px 0;
                text-align: center;
            }
            .tagline {
                margin-left: auto;
                margin-right: auto;
            }
            .logout-btn {
                padding: 5px 8px;
                font-size: 13px;
                border-width: 2px;
            }
            #top_bar {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }
            #top_bar .school-title {
                align-items: flex-start;
            }
            #bot_bar {
                font-size: 11px;
                padding: 8px;
            }
            /* Penyesuaian khusus untuk card registrasi */
            #right .card-container {
                margin: 0 !important;
                padding: 0 15px;
                width: 100%;
            }
            #right .card img {
                margin-left: 0 !important;
                display: block;
                margin: 0 auto;
                width: 140px !important;
                height: 70px !important;
            }
            #right > img {
                margin-left: 0 !important;
                padding: 10px;
            }
            .card div {
                text-align: center !important;
                width: 100% !important;
                padding: 0 5px !important;
            }
            .card-icon-container {
                display: flex;
                justify-content: center;
                width: 100%;
            }
            
            /* Penyesuaian teks dalam card */
            .card > div:not(.card-icon-container) {
                margin-top: 10px;
            }
        }
    </style>
</head>

<body>
    <div id="top_bar">
        <img src="image/slogan.png" alt="Logo Tut Wuri Handayani">
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
            <div class="tagline">Selamat Datang di Website</div>
            <div class="title">SD Negeri X Yogyakarta</div>
            <div class="subtitle">Kota Yogyakarta, Provinsi D.I. Yogyakarta</div>

            <div class="card-container">
                <a href="live.php">
                    <div class="card">
                        <img src="image/live.png" alt="Live">
                        <div style="margin-top: 5px; font-size: 15px; font-weight: bold;">Siaran Langsung</div>
                        <div style="font-size: 15px; font-weight: bold;">di Kelas</div>
                    </div>
                </a>

                <a href="recap.php">
                    <div class="card" style="background-color: #7c44f5; color: white;">
                        <img src="image/recap.png" alt="Rekap">
                        <div style="margin-top: 5px; font-size: 15px; font-weight: bold;">Rekapitulasi</div>
                        <div style="font-size: 15px; font-weight: bold;">Data</div>
                    </div>
                </a>
            </div>
        </div>

        <div id="right">
            <img src="image/cartoon2.png" alt="Ilustrasi" style="padding: 10px; margin-left: 50px; margin-bottom: 20px;">
            <div class="card-container" style="justify-content: center; align-items: center; margin-right: 80px; margin-bottom: 60px;">
                <a href="registA.php">
                    <div class="card">
                        <img src="image/teacher.png" alt="Live" style="width: 140px; height: 70px; margin-left: 46px;">
                        <div style="margin-top: 5px; font-size: 15px; font-weight: bold;">Registrasi</div>
                        <div style="font-size: 15px; font-weight: bold;">Akun</div>
                    </div>
                </a>

                <a href="registG.php">
                    <div class="card" style="background-color: #7c44f5; color: white;">
                        <img src="image/student.png" alt="Rekap" style="width: 140px; height: 70px; margin-left: 36px;">
                        <div style="margin-top: 5px; font-size: 15px; font-weight: bold;">Registrasi Data</div>
                        <div style="font-size: 15px; font-weight: bold;">Guru dan Siswa</div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <div id="bot_bar">
        <div>Copyright &copy; 2025</div>
        <div>SD Negeri X Yogyakarta</div>
    </div>
</body>
</html>