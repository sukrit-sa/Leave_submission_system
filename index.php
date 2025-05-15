<?php
session_start();

// ตรวจสอบว่ามีเซสชัน role หรือไม่
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: ../admin/index.php');
        exit();
    } elseif ($_SESSION['role'] === 'user') {
        header('Location: ../user/index.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ระบบลาออนไลน์</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Noto Sans Thai', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .login-card {
            max-width: 400px;
            margin: 30px auto;
            padding: 35px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
        }

        .logo {
            font-size: 50px;
            text-align: center;
            margin-bottom: 20px;
            animation: fadeIn 1s ease;
        }

        .btn-dark {
            background: linear-gradient(45deg, #1a1f25, #111827);
            border: none;
            padding: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-dark:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .form-control {
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #111827;
            box-shadow: 0 0 0 3px rgba(17, 24, 39, 0.1);
        }

        .extra-links {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .extra-links a {
            color: #111827;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .extra-links a:hover {
            color: #4B5563;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h1 {
            color: #111827;
            animation: fadeIn 1s ease 0.2s;
            opacity: 0;
            animation-fill-mode: forwards;
        }

        p.text-muted {
            animation: fadeIn 1s ease 0.4s;
            opacity: 0;
            animation-fill-mode: forwards;
        }
    </style>
</head>

<body>

    <div class="container text-center mt-5">
        <div class="logo"><img src="login/img/bg.png" alt="" width="150px"></div>
        <h1 class="fw-bold mb-1">ระบบการยื่นใบลา</h1>
        <p class="text-muted">สำนักส่งเสริมวิชาการและงานทะเบียน</p>
        <p class="text-muted mb-1">มหาวิทยาลัยเทคโนโลยีราชมงคลอีสาน นครราชสีมา</p>

        <div class="login-card text-start">
            <h4 class="mb-2">เข้าสู่ระบบ</h4>
            <p class="text-muted mb-4">กรุณาเข้าสู่ระบบด้วยอีเมลและรหัสผ่านของท่าน</p>

            <?php if (isset($_SESSION['error'])) { ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php } ?>

            <form action="login/login.php" method="POST">
                <div class="mb-3">
                    <label for="email" class="form-label"><i class="fa-solid fa-user"></i> อีเมล</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="อีเมล@rmuti.ac.th">
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label"><i class="fa-solid fa-lock"></i> รหัสผ่าน</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="รหัสผ่าน">
                </div>

                <button type="submit" class="btn btn-dark w-100">เข้าสู่ระบบ</button>
            </form>
            <!-- ลืมรหัสผ่าน & สมัครสมาชิก -->
            <!-- <div class="extra-links mt-3">
                <a href="#" class="small">ลืมรหัสผ่าน?</a>
                <a href="login/register.php" class="small">สมัครสมาชิก</a>
            </div> -->

        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>