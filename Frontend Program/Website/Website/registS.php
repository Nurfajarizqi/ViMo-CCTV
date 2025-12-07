<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';
$kelas = [];
$mp_query = "SELECT id, nama_kelas FROM kelas";
$result = $conn->query($mp_query);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $kelas[] = $row;
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = $_POST['nama_lengkap'];
    $nisn = $_POST['nisn'];
    $tanggal_lahir = $_POST['tanggal_lahir'];
    $id_kelas = $_POST['kelas']; 

    $stmt = $conn->prepare("INSERT INTO siswa (nama_lengkap, nisn, tanggal_lahir, id_kelas) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $nama, $nisn, $tanggal_lahir, $id_kelas);
    $stmt->execute();
    
    // Redirect to prevent form resubmission
    header("Location: registS.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registrasi Siswa - SD Negeri X</title>
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
      padding: 20px;
      background-image: url('image/main_background.png');
      background-repeat: no-repeat;
      background-size: cover;
      background-position: center;
    }

    #left {
      width: 220px;
      border-radius: 15px;
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 10px;
      background-color: rgba(255, 255, 255, 0.9);
    }

    .menu-btn {
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

    .menu-btn.active {
      background-color: #7c44f5;
      border: 2px solid #ffffff;
      color: white;
    }

    #right {
      flex: 1;
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      margin-left: 20px;
      padding: 40px;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .form-wrapper {
      width: 100%;
      max-width: 500px;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }

    .form-column {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    form h2 {
      text-align: center;
      margin-bottom: 10px;
    }

    form label {
      font-weight: bold;
    }

    form input, form select {
      padding: 10px;
      border-radius: 8px;
      border: 1px solid #ccc;
      width: 100%;
    }

    form button {
      background-color: #7c44f5;
      color: white;
      border: none;
      padding: 12px;
      border-radius: 10px;
      font-size: 16px;
      cursor: pointer;
      width: 100px;
      height: 40px;
      align-items: center;
      text-align: center;
      justify-content: center;
    }

    form button:hover {
      background-color: #5e2ed8;
    }

    #bot_bar {
      background-color: #ffffff;
      padding: 10px;
      text-align: center;
      font-size: 12px;
      color: #635f5f;
      margin-top: 30px;
    }
    
    .success-message {
      color: green;
      text-align: center;
      margin-bottom: 15px;
    }

    @media (max-width: 768px) {
      #main_content {
        flex-direction: column;
        padding: 10px;
        padding-bottom: 50px;
      }

      #left {
        width: 100%;
        flex-direction: row;
        flex-wrap: wrap;
        justify-content: center;
        gap: 10px;
        margin-bottom: 15px;
      }

      .menu-btn {
        flex: 1 1 45%;
        text-align: center;
      }

      #right {
        margin-left: 0;
        padding: 20px;
      }

      .form-grid {
        grid-template-columns: 1fr;
      }

      form button {
        width: 100%;
      }

      #top_bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }

      #top_bar .school-title {
        font-size: 14px;
      }

      .logout-btn {
        align-self: flex-end;
        margin-left: 0;
        font-size: 13px;
        padding: 5px 8px;
      }

      .logout-btn div {
        font-size: 13px;
      }

      #bot_bar {
        font-size: 10px;
        padding: 8px;
        margin-top: 20px;
      }
    }
  </style>
</head>
<body>
  <div id="top_bar">
    <a href="homeA.php">
      <img src="image/slogan.png" alt="Logo Tut Wuri Handayani" />
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
      <a class="menu-btn" href="registG.php">Registrasi Guru</a>
      <a class="menu-btn active" href="registS.php">Registrasi Siswa</a>
    </div>

    <div id="right">
      <div class="form-wrapper">
        <?php if (isset($_GET['success'])): ?>
          <div class="success-message">Siswa berhasil Didaftarkan!</div>
        <?php endif; ?>
        
        <form method="post">
          <h2>Form Registrasi Siswa</h2>
          <div class="form-grid">
            <div class="form-column">
              <label>Nama Lengkap:</label>
              <input name="nama_lengkap" required />
              <label>NISN:</label>
              <input name="nisn" required />
            </div>
            <div class="form-column">
              <label>Tanggal Lahir:</label>
              <input type="date" name="tanggal_lahir" required />
              <label>Kelas:</label>
                      <select name="kelas" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($kelas as $mp): ?>
                            <option value="<?= $mp['id'] ?>"><?= htmlspecialchars($mp['nama_kelas']) ?></option>
                        <?php endforeach; ?>
                      </select>
            </div>
          </div>
          <div style="display: flex; justify-content: center; margin-bottom: 15px;">
            <button type="submit" style="margin-top: 20px;">Daftar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div id="bot_bar">
      <div>Copyright &copy; 2025</div>
      <div>SD Negeri X Yogyakarta</div>
  </div>
</body>
</html>