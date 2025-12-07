<?php
require 'db.php';

$message = '';
$guru_options = [];

// Ambil data guru
$guru_query = $conn->query("SELECT id, nama_lengkap FROM guru ORDER BY nama_lengkap");
if ($guru_query) {
    while ($row = $guru_query->fetch_assoc()) {
        $guru_options[$row['id']] = $row['nama_lengkap']; 
    }
} else {
    die("Error mengambil data guru: " . $conn->error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $role = trim($_POST["role"]);
    $id_guru = isset($_POST["id_guru"]) ? intval($_POST["id_guru"]) : NULL;

    // Validasi input
    if (empty($username) || empty($password) || empty($role)) {
        $message = "Semua field wajib diisi!";
    } elseif ($role === "guru" && empty($id_guru)) {
        $message = "Untuk role 'guru', wajib memilih guru!";
    } else {
        // Cek apakah username sudah digunakan
        $check = $conn->prepare("SELECT id FROM akun WHERE username = ?");
        $check->bind_param("s", $username);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $message = "Username sudah digunakan!";
        } else {
            // Cek apakah guru valid
            $check_guru = $conn->prepare("SELECT id FROM guru WHERE id = ?");
            $check_guru->bind_param("i", $id_guru);
            $check_guru->execute();
            $check_guru->store_result();

            if ($check_guru->num_rows == 0) {
                $message = "Data guru tidak valid!";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Simpan ke tabel akun
                $stmt = $conn->prepare("INSERT INTO akun (username, password, role, id_guru) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("sssi", $username, $hashed_password, $role, $id_guru);

                if ($stmt->execute()) {
                    $message = "Registrasi berhasil! Akun untuk " . htmlspecialchars($username) . " telah dibuat.";
                } else {
                    $message = "Error: " . $stmt->error;
                }
                $stmt->close();
            }
            $check_guru->close();
        }
        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registrasi Akun - SD Negeri X</title>
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
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 10px;
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

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .required-field::after {
      content: " *";
      color: red;
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
      width: 100%;
      margin-top: 10px;
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
    }

    .message {
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 5px;
      text-align: center;
    }

    .success {
      background-color: #d4edda;
      color: #155724;
      border-left: 5px solid #28a745;
    }

    .error {
      background-color: #f8d7da;
      color: #721c24;
      border-left: 5px solid #dc3545;
    }

    #guruField {
      transition: all 0.3s ease;
    }

    @media (max-width: 768px) {
      #main_content {
        flex-direction: column;
      }

      #left {
        width: 100%;
        margin-bottom: 20px;
      }

      #right {
        margin-left: 0;
      }
    }
  </style>
</head>
<body>
  <div id="top_bar">
    <a href="homeA.php"><img src="image/slogan.png" alt="Logo Tut Wuri Handayani" /></a>
    <div class="school-title">
      <div style="font-size: 25px; font-weight: 1000;">SD Negeri X</div>
      <div style="font-size: 15px; font-weight: 700;">Yogyakarta</div>
    </div>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>

  <div id="main_content">
    <div id="left">
      <a class="menu-btn active" href="registA.php">Registrasi Akun</a>
    </div>

    <div id="right">
      <div class="form-wrapper">
        <?php if (!empty($message)): ?>
          <div class="message <?= strpos($message, 'berhasil') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
          </div>
        <?php endif; ?>
        
        <form method="post">
          <h2 style="text-align: center; margin-bottom: 20px;">Form Registrasi Akun</h2>

          <div class="form-group">
            <label class="required-field">Username:</label>
            <input type="text" name="username" required maxlength="50">
          </div>

          <div class="form-group">
            <label class="required-field">Password:</label>
            <input type="password" name="password" required>
          </div>

          <div class="form-group" id="guruField">
            <label class="required-field">Nama Guru:</label>
            <select name="id_guru" id="guruSelect">
              <option value="">-- Pilih Guru --</option>
              <?php foreach ($guru_options as $id => $nama): ?>
                <option value="<?= $id ?>"><?= htmlspecialchars($nama) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="required-field">Role:</label>
            <select name="role" id="roleSelect" required>
              <option value="">-- Pilih Role --</option>
              <option value="admin">Admin</option>
              <option value="petinggi">Petinggi</option>
              <option value="guru">Guru</option>
            </select>
          </div>

          <button type="submit">Buat Akun</button>
        </form>
      </div>
    </div>
  </div>

  <div id="bot_bar">
    <div>Copyright &copy; 2025</div>
    <div>SD Negeri X Yogyakarta</div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelector('form').addEventListener('submit', function(e) {
        const role = document.getElementById('roleSelect').value;
        const idGuru = document.getElementById('guruSelect').value;

        if (role === 'guru' && (!idGuru || idGuru === "")) {
          alert('Untuk role Guru, wajib memilih nama guru!');
          e.preventDefault();
        }
      });
    });
  </script>
</body>
</html>