<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$subject_id = isset($_GET['subject']) ? intval($_GET['subject']) : 1;

$subject_names = [
    1 => 'Matematika',
    2 => 'Bahasa',
    3 => 'IPA',
    4 => 'IPS'
];

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDN X Jogja (List Guru)</title>
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
            justify-content: flex-start; 
            align-items: center; 
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

        .logout-container{
            margin-left: auto;
        }

        @media screen and (max-width: 768px) {
        #main_content {
            flex-direction: column;
            padding: 10px;
        }

        #left {
            width: 100%;
            flex-direction: row;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 15px;
        }

        .kelas-btn {
            flex: 1 1 45%;
            margin: 5px;
            font-size: 14px;
            padding: 12px;
        }

        #right {
            margin-left: 0;
            padding: 15px;
            max-height: none;
        }

        table {
            font-size: 12px;
            min-width: unset;
            width: 100%;
        }

        th, td {
            padding: 6px;
        }

        input[readonly] {
            font-size: 12px;
            padding: 4px;
        }

        .school-title {
            font-size: 14px;
        }

        #top_bar {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }

        #top_bar img {
            height: 50px;
        }

        .logout-btn {
            margin-left: 0;
            margin-top: 10px;
            font-size: 14px;
        }

        .logout-container{
            width: 100%;
            display: flex;
            justify-content: flex-end;
            margin-left: 0;
        }
    }

    </style>
</head>
<body>
    <div id="top_bar">
        <a href="homeP.php"><img src="image/slogan.png" alt="Logo Tut Wuri Handayani"></a>
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
            <?php foreach ($subject_names as $id => $name): ?>
                <button class="kelas-btn <?= ($subject_id === $id) ? 'active' : '' ?>" onclick="setKelas(<?= $id ?>)"><?= htmlspecialchars($name) ?></button>
            <?php endforeach; ?>
        </div>

        <div id="right">
            <div style="font-size: 24px; font-weight: bold; margin-bottom: 20px;">
                <?= $subject_id && isset($subject_names[$subject_id]) ? "Daftar Guru Mata Pelajaran " . $subject_names[$subject_id] : "Silakan Pilih Mata Pelajaran" ?>
            </div>

            <?php if ($subject_id): ?>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Lengkap</th>
                            <th>NUPTK</th>
                            <th>Tanggal Lahir</th>
                            <!-- <th>Mata Pelajaran</th> -->
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $stmt = $conn->prepare("
                        SELECT g.nama_lengkap, g.nuptk, g.tanggal_lahir, m.nama_mata_pelajaran 
                        FROM guru g
                        INNER JOIN mata_pelajaran m ON g.id_mata_pelajaran = m.id
                        WHERE m.id = ?
                    ");
                    $stmt->bind_param("i", $subject_id);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    $no = 1;
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>$no</td>";
                        echo "<td><input type='text' value='" . htmlspecialchars($row['nama_lengkap']) . "' readonly></td>";
                        echo "<td><input type='text' value='" . htmlspecialchars($row['nuptk']) . "' readonly></td>";
                        echo "<td><input type='text' value='" . htmlspecialchars($row['tanggal_lahir']) . "' readonly></td>";
                        // echo "<td><input type='text' value='" . htmlspecialchars($row['nama_mata_pelajaran']) . "' readonly></td>";
                        echo "</tr>";
                        $no++;
                    }
                    ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div id="bot_bar">
        <div>Copyright &copy; 2025</div>
        <div>SD Negeri X Yogyakarta</div>
    </div>

    <script>
        function setKelas(subjectId) {
            window.location.href = `?subject=${subjectId}`;
        }
    </script>
</body>
</html>