<?php
ini_set('session.cookie_lifetime', 0);
session_start();
include 'config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        header('Location: index.php');
        exit();
    } else {
        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ | Classroom System</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --brand-primary: #4f46e5;
            --brand-secondary: #6366f1;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --bg-gradient: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            --glass-card: rgba(255, 255, 255, 0.9);
        }

        body {
            min-height: 100vh;
            background: var(--bg-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', 'Sarabun', sans-serif;
            margin: 0;
            padding: 20px;
            color: var(--text-main);
        }

        /* ตกแต่งพื้นหลังด้วยวงกลม Gradient กระจายตัว */
        body::before {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.1) 0%, transparent 70%);
            top: 10%;
            left: 10%;
            z-index: -1;
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            padding: 56px 40px;
            border-radius: 32px;
            background: var(--glass-card);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.7);
            position: relative;
        }

        /* ส่วนหัวข้อความ */
        .brand-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .system-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 4px;
        }

        .system-subtitle {
            font-size: 14px;
            color: var(--text-muted);
        }

        /* Form Customization */
        .form-label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #475569;
            margin-bottom: 8px;
            display: block;
        }

        .input-group {
            background: #f1f5f9;
            border: 1.5px solid transparent;
            border-radius: 14px;
            transition: all 0.25s ease;
            overflow: hidden;
        }

        .input-group:focus-within {
            background: #fff;
            border-color: var(--brand-primary);
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
        }

        .input-group-text {
            background: transparent;
            border: none;
            color: var(--text-muted);
            padding-left: 16px;
        }

        .form-control {
            border: none;
            background: transparent;
            padding: 12px 16px 12px 8px;
            font-size: 0.95rem;
            font-weight: 500;
        }

        .form-control:focus {
            box-shadow: none;
            background: transparent;
        }

        .password-toggle {
            background: transparent;
            border: none;
            color: var(--text-muted);
            padding-right: 16px;
            transition: color 0.2s;
        }

        .password-toggle:hover {
            color: var(--brand-primary);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
            color: white;
            border: none;
            border-radius: 14px;
            padding: 14px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 20px;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 20px -5px rgba(79, 70, 229, 0.4);
            filter: brightness(1.1);
            color: white;
        }

        .alert-error {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: #dc2626;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.85rem;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Loading */
        .loading-state {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            border-radius: 32px;
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .loader {
            width: 38px;
            height: 38px;
            border: 3.5px solid #f1f5f9;
            border-top: 3.5px solid var(--brand-primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .footer-note {
            text-align: center;
            margin-top: 40px;
            font-size: 0.75rem;
            color: var(--text-muted);
            letter-spacing: 0.025em;
        }

        @media (max-width: 480px) {
            .login-card {
                padding: 40px 24px;
            }
        }
    </style>
</head>

<body>

<div class="login-card">
    <div class="loading-state" id="loading">
        <div class="loader mb-3"></div>
        <div class="small fw-bold text-dark">กำลังเข้าสู่เว็บไซต์...</div>
    </div>

    <div class="brand-header">
        <div class="system-title">ระบบจองห้องเรียนอัจฉริยะ</div>
        <div class="system-subtitle">กรุณากรอกข้อมูลเพื่อเข้าสู่เว็บไซต์</div>
    </div>

    <?php if ($error): ?>
        <div class="alert-error">
            <i class="bi bi-info-circle-fill"></i>
            <div><?= $error ?></div>
        </div>
    <?php endif; ?>

    <form method="post" id="loginForm">
        <div class="mb-4">
            <label class="form-label">ชื่อผู้ใช้งาน</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="username" class="form-control" placeholder="กรอกชื่อ" required autofocus>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label">รหัสผ่าน</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" id="password" class="form-control" placeholder="รหัสผ่าน" required>
                <button class="password-toggle" type="button" id="togglePassword">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-login shadow-sm">
            <span>เข้าสู่ระบบ</span>
            <i class="bi bi-arrow-right"></i>
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="#" class="small text-muted">
            ลืมรหัสผ่านกรุณาติดต่อผู้ดูแลระบบ
        </a>
    </div>

    <div class="text-center mt-3">
        <span class="small text-muted">ยังไม่มีบัญชี?</span>
        <a href="register.php"
        class="small fw-bold text-decoration-none"
        style="color: #4f46e5;">
            สมัครสมาชิก
        </a>
    </div>
    <!--<div class="footer-note">
        &copy; <?= date('Y') ?> Smart Classroom Booking<br>
        <span class="opacity-75 small">Designed for *********</span>
    </div>-->
</div>

<script>
    const passwordInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePassword');
    const toggleIcon = toggleBtn.querySelector('i');

    // สลับการแสดงผลรหัสผ่าน
    toggleBtn.addEventListener('click', () => {
        const isPassword = passwordInput.type === 'password';
        passwordInput.type = isPassword ? 'text' : 'password';
        toggleIcon.classList.toggle('bi-eye', !isPassword);
        toggleIcon.classList.toggle('bi-eye-slash', isPassword);
    });

    // แสดง Animation เมื่อกด Submit
    document.getElementById('loginForm').addEventListener('submit', () => {
        document.getElementById('loading').style.display = 'flex';
    });
</script>

</body>
</html>