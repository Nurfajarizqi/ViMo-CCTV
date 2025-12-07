<?php
session_start();
include 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = $_POST["password"]; // Jangan escape password karena akan diverifikasi dengan password_verify()

    // Query akun berdasarkan username
    $stmt = $conn->prepare("SELECT * FROM akun WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verifikasi password hash
        if (password_verify($password, $user['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $user['role'];

            // Redirect sesuai role
            switch ($user['role']) {
                case 'admin':
                    header("Location: homeA.php");
                    exit();
                case 'guru':
                    header("Location: homeG.php");
                    exit();
                case 'petinggi':
                    header("Location: homeP.php");
                    exit();
                default:
                    header("Location: error.php?code=invalid_role");
                    exit();
            }
        } else {
            $error = "Username atau Password salah!";
        }
    } else {
        $error = "Username atau Password salah!";
    }

    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title>SDN X Jogja (Login)</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            font-family: 'Glacial Indifference', sans-serif;
        }

        body {
            display: flex;
            flex-direction: column;
            background-image: url('image/main_background.png');
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center;
            min-height: 100vh;
        }

        #top_bar {
            background-color: #ffffff;
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            gap: 0.75rem;
        }

        #top_bar img {
            height: 3rem;
        }

        #top_bar .school-title {
            display: flex;
            flex-direction: column;
            justify-content: center;
            letter-spacing: 0.15rem;
        }

        #main_content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            text-align: center;
            padding: 1rem;
            width: 100%;
        }

        .tagline {
            background: #fff6e6;
            color: #6c5ce7;
            padding: 0.5rem 1rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
            font-weight: normal;
        }

        .title {
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: 0.1rem;
            margin-bottom: 0.25rem;
            line-height: 1.2;
        }

        .subtitle {
            font-size: 1.25rem;
            font-style: italic;
            margin-bottom: 2rem;
            letter-spacing: 0.1rem;
            line-height: 1.2;
        }

        .login-box {
            background: rgba(255, 255, 255, 0);
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            width: 100%;
            max-width: 18.75rem;
            padding: 0 0.5rem;
        }

        .input-group {
            display: flex;
            align-items: center;
            background: white;
            border: 0.15rem solid #7c44f5;
            border-radius: 2rem;
            padding: 0.25rem 0.75rem;
            width: 100%;
        }

        .input-group input {
            border: none;
            outline: none;
            flex: 1;
            padding: 0.5rem;
            font-size: 0.8rem;
            border-radius: 2rem;
        }

        .input-group img {
            height: 1.5rem;
            margin-right: 0.5rem;
        }

        button {
            background: #8c52ff;
            color: #ffffff;
            border: 0.15rem solid #ffffff;
            border-radius: 1rem;
            padding: 0.5rem;
            font-size: 0.8rem;
            cursor: pointer;
            transition: 0.3s ease;
            margin: 0 auto;
            width: 50%;
            max-width: 10rem;
        }

        button:hover {
            background: #5e39c8;
        }

        #bot_bar {
            background-color: #ffffff;
            padding: 0.5rem;
            text-align: center;
            font-size: 0.6rem;
            color: #635f5f;
        }

        .error-message {
            color: #ff4d4d;
            background: #fff1f1;
            padding: 0.4rem 0.5rem;
            border-radius: 0.5rem;
            font-size: 0.65rem;
            text-align: center;
            width: 100%;
        }

        /* Media Queries untuk Responsiveness */
        @media (min-width: 768px) {
            #top_bar {
                padding: 0.5rem 1rem;
                gap: 1rem;
            }
            
            #top_bar img {
                height: 3.5rem;
            }

            #top_bar .school-title div:first-child {
                font-size: 1.5rem; 
            }
            #top_bar .school-title div:last-child {
                font-size: 1.5rem; 
            }
            
            .tagline {
                font-size: 1rem;
                padding: 0.6rem 1.2rem;
            }
            
            .title {
                font-size: 2.25rem;
            }
            
            .subtitle {
                font-size: 1.5rem;
            }
            
            .login-box {
                max-width: 22rem;
                gap: 1rem;
            }
            
            .input-group input {
                font-size: 1rem;
                padding: 0.6rem;
            }
            
            .input-group img {
                height: 1.75rem;
            }
            
            button {
                font-size: 1rem;
                padding: 0.6rem;
            }
            
            #bot_bar {
                font-size: 0.75rem;
                padding: 0.75rem;
            }
            
            .error-message {
                font-size: 0.8rem;
                padding: 0.5rem 0.6rem;
            }
        }

    </style>
</head>
<body>
    <div id="top_bar">
        <img src="image/slogan.png" alt="Logo Tut Wuri Handayani" />
        <div class="school-title">
            <div style="font-size: 1.5rem; font-weight: 1000;">SD Negeri X</div>
            <div style="font-size: 1.25rem; font-weight: 700;">Yogyakarta</div>
        </div>
    </div>

    <div id="main_content">
        <div class="tagline">Selamat Datang di Website</div>
        <div class="title">SD Negeri X Yogyakarta</div>
        <div class="subtitle">Kota Yogyakarta, Provinsi D.I. Yogyakarta</div>

        <form class="login-box" method="POST" action="">
            <?php if ($error != "") { echo "<div class='error-message'>$error</div>"; } ?>
            <div class="input-group">
                <img src="image/people.png" alt="Logo Orang" />
                <input type="text" name="username" placeholder="Username" required />
            </div>
            <div class="input-group">
                <img src="image/key.png" alt="Logo Kunci" />
                <input type="password" name="password" placeholder="Password" required />
            </div>
            <button type="submit">Masuk</button>
        </form>
    </div>

    <div id="bot_bar">
        <div>Copyright &copy; 2025</div>
        <div>SD Negeri X Yogyakarta</div>
    </div>
</body>
</html>