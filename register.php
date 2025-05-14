<?php
include("./conn/conn.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username'], $_POST['password'], $_POST['email'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            $stmt = $conn->prepare("INSERT INTO tbl_users (username, password, email) VALUES (?, ?, ?)");
            $stmt->execute([$username, $password, $email]);
            
            header("Location: login.php?registered=1");
            exit();
        } catch (PDOException $e) {
            $error = "Username/email already exists!";
        }
    } else {
        $error = "Please fill all fields!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | QR Attendance</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap');

        * {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(to bottom, rgba(255,255,255,0.15) 0%, rgba(0,0,0,0.15) 100%), radial-gradient(at top center, rgba(255,255,255,0.40) 0%, rgba(0,0,0,0.40) 120%) #989898;
            background-blend-mode: multiply,multiply;
            background-attachment: fixed;
            background-repeat: no-repeat;
            background-size: cover;
            height: 100vh;
        }

        .register-container {
            max-width: 500px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
            margin: 0 auto;
        }

        .logo {
            color: #fe2c55;
            font-weight: 700;
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        .btn-primary {
            background-color: #fe2c55;
            border: none;
            height: 48px;
            font-weight: 600;
            border-radius: 8px;
        }

        .btn-primary:hover {
            background-color: #d82348;
        }

        .form-control {
            height: 48px;
            border-radius: 8px;
            border: 1px solid #e3e3e4;
            padding-left: 16px;
            transition: all 0.2s;
        }

        .login-link {
            position: relative;
            display: inline-block;
            transition: all 0.3s ease;
            color: #fe2c55;
            font-weight: 600;
            text-decoration: none;
        }

        .login-link:hover {
            color: #d82348 !important;
            transform: translateY(-2px);
        }

        .main {
            display: flex;
            justify-content: center;
            align-items: center;
            height: calc(100vh - 56px);
        }

        .transition-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(255,255,255,0.15) 0%, rgba(0,0,0,0.15) 100%), radial-gradient(at top center, rgba(255,255,255,0.40) 0%, rgba(0,0,0,0.40) 120%) #989898;
            background-blend-mode: multiply,multiply;
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.6s ease;
        }

        .transition-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        .transition-logo {
            font-size: 3rem;
            font-weight: 700;
            color: white;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(0.95); opacity: 0.7; }
            50% { transform: scale(1.05); opacity: 1; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <a class="navbar-brand ml-4" href="#">QR Code Attendance System</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item mr-3">
                    <a class="nav-link" href="login.php">Login</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="transition-overlay" id="transitionOverlay">
        <div class="transition-logo">Please Wait</div>
    </div>

    <div class="main">
        <div class="register-container animate__animated animate__fadeIn">
            <div class="logo">CREATE ACCOUNT</div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger animate__animated animate__shakeX">
                    <?= $error ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <input type="text" class="form-control" name="username" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="email" class="form-control" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="password" class="form-control" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    Sign Up
                </button>
                
                <div class="text-center mt-3">
                    <p class="mb-0">Already have an account? 
                        <a href="login.php" class="login-link" id="loginLink">Log in</a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jquery@3.5.1/dist/jquery.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>
    <script>
        document.getElementById('loginLink').addEventListener('click', function(e) {
            e.preventDefault();
            const transitionOverlay = document.getElementById('transitionOverlay');
            
            transitionOverlay.classList.add('active');
            setTimeout(() => {
                window.location.href = this.href;
            }, 600);
        });
        
        // Page load animation
        window.addEventListener('load', function() {
            const transitionOverlay = document.getElementById('transitionOverlay');
            transitionOverlay.classList.add('active');
            setTimeout(() => {
                transitionOverlay.classList.remove('active');
            }, 100);
        });
    </script>
</body>
</html>