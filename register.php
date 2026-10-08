<?php
ini_set('session.cookie_lifetime', 0);
session_start();

include 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // =========================
    // VALIDATION
    // =========================

    if (
        empty($full_name) ||
        empty($email) ||
        empty($phone) ||
        empty($username) ||
        empty($password) ||
        empty($confirm_password)
    ) {
        $error = 'กรุณากรอกข้อมูลให้ครบทุกช่อง';
    }

    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'รูปแบบอีเมลไม่ถูกต้อง';
    }

    elseif (strlen($username) < 4) {
        $error = 'ชื่อผู้ใช้งานต้องมีอย่างน้อย 4 ตัวอักษร';
    }

    elseif (strlen($password) < 8) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    }

    elseif ($password !== $confirm_password) {
        $error = 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน';
    }

    else {

        // =========================
        // CHECK DUPLICATE USERNAME
        // =========================

        $stmt = $conn->prepare("
            SELECT id
            FROM users
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->execute([$username]);

        if ($stmt->fetch()) {
            $error = 'ชื่อผู้ใช้งานนี้ถูกใช้งานแล้ว';
        }

        else {

            // =========================
            // CHECK DUPLICATE EMAIL
            // =========================

            $stmt = $conn->prepare("
                SELECT id
                FROM users
                WHERE email = ?
                LIMIT 1
            ");

            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'อีเมลนี้ถูกใช้งานแล้ว';
            }

            else {

                // =========================
                // HASH PASSWORD
                // =========================

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                // =========================
                // INSERT USER
                // =========================

                $stmt = $conn->prepare("
                    INSERT INTO users
                    (
                        username,
                        full_name,
                        email,
                        phone,
                        password,
                        role,
                        status,
                        created_at,
                        updated_at
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'user',
                        'active',
                        NOW(),
                        NOW()
                    )
                ");

                try {

                    $stmt->execute([
                        $username,
                        $full_name,
                        $email,
                        $phone,
                        $hashed_password
                    ]);

                    $success = 'สมัครสมาชิกสำเร็จ สามารถเข้าสู่ระบบได้ทันที';

                    // ล้างค่า form
                    $full_name = '';
                    $email = '';
                    $phone = '';
                    $username = '';

                } catch (PDOException $e) {

                    $error = 'ไม่สามารถสมัครสมาชิกได้ กรุณาลองใหม่อีกครั้ง';

                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>สมัครสมาชิก | Classroom System</title>

    <!-- Bootstrap -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- Bootstrap Icons -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        rel="stylesheet"
    >

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600&display=swap"
        rel="stylesheet"
    >

    <style>

        :root {

            --brand-primary: #4f46e5;
            --brand-secondary: #6366f1;

            --text-main: #1e293b;
            --text-muted: #64748b;

            --bg-gradient:
                linear-gradient(
                    135deg,
                    #f8fafc 0%,
                    #e2e8f0 100%
                );

            --glass-card:
                rgba(255, 255, 255, 0.9);
        }

        * {
            box-sizing: border-box;
        }

        body {

            min-height: 100vh;

            background: var(--bg-gradient);

            display: flex;

            align-items: center;

            justify-content: center;

            font-family:
                'Plus Jakarta Sans',
                'Sarabun',
                sans-serif;

            margin: 0;

            padding: 30px 20px;

            color: var(--text-main);
        }

        body::before {

            content: "";

            position: fixed;

            width: 350px;

            height: 350px;

            background:
                radial-gradient(
                    circle,
                    rgba(79, 70, 229, 0.10) 0%,
                    transparent 70%
                );

            top: 5%;

            left: 8%;

            z-index: -1;
        }

        body::after {

            content: "";

            position: fixed;

            width: 300px;

            height: 300px;

            background:
                radial-gradient(
                    circle,
                    rgba(99, 102, 241, 0.08) 0%,
                    transparent 70%
                );

            bottom: 5%;

            right: 8%;

            z-index: -1;
        }

        .register-card {

            width: 100%;

            max-width: 500px;

            padding: 45px 40px;

            border-radius: 32px;

            background: var(--glass-card);

            backdrop-filter: blur(24px);

            -webkit-backdrop-filter: blur(24px);

            box-shadow:
                0 25px 50px -12px
                rgba(0, 0, 0, 0.08);

            border:
                1px solid
                rgba(255, 255, 255, 0.7);

            position: relative;
        }

        .brand-header {

            text-align: center;

            margin-bottom: 30px;
        }

        .system-title {

            font-size: 24px;

            font-weight: 700;

            color: var(--text-main);

            margin-bottom: 5px;
        }

        .system-subtitle {

            font-size: 14px;

            color: var(--text-muted);
        }

        .form-label {

            font-weight: 600;

            font-size: 0.85rem;

            color: #475569;

            margin-bottom: 8px;

            display: block;
        }

        .input-group {

            background: #f1f5f9;

            border:
                1.5px solid
                transparent;

            border-radius: 14px;

            transition: all 0.25s ease;

            overflow: hidden;
        }

        .input-group:focus-within {

            background: #fff;

            border-color:
                var(--brand-primary);

            box-shadow:
                0 0 0 4px
                rgba(79, 70, 229, 0.1);
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

            padding:
                12px
                16px
                12px
                8px;

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

        .btn-register {

            background:
                linear-gradient(
                    135deg,
                    var(--brand-primary),
                    var(--brand-secondary)
                );

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

        .btn-register:hover {

            transform: translateY(-1px);

            box-shadow:
                0 10px 20px -5px
                rgba(79, 70, 229, 0.4);

            filter: brightness(1.1);

            color: white;
        }

        .alert-error {

            background: #fef2f2;

            border:
                1px solid
                #fee2e2;

            color: #dc2626;

            border-radius: 12px;

            padding: 12px 16px;

            font-size: 0.85rem;

            margin-bottom: 20px;

            display: flex;

            align-items: center;

            gap: 10px;
        }

        .alert-success {

            background: #f0fdf4;

            border:
                1px solid
                #bbf7d0;

            color: #15803d;

            border-radius: 12px;

            padding: 12px 16px;

            font-size: 0.85rem;

            margin-bottom: 20px;

            display: flex;

            align-items: center;

            gap: 10px;
        }

        .back-login {

            text-align: center;

            margin-top: 22px;

            font-size: 0.85rem;

            color: var(--text-muted);
        }

        .back-login a {

            color: var(--brand-primary);

            font-weight: 600;

            text-decoration: none;
        }

        .back-login a:hover {

            text-decoration: underline;
        }

        .password-hint {

            font-size: 0.72rem;

            color: var(--text-muted);

            margin-top: 6px;

            padding-left: 4px;
        }

        @media (max-width: 480px) {

            body {
                padding: 20px 12px;
            }

            .register-card {

                padding:
                    35px
                    24px;

                border-radius: 25px;
            }

            .system-title {

                font-size: 21px;
            }
        }

    </style>

</head>

<body>

<div class="register-card">

    <!-- Header -->

    <div class="brand-header">

        <div class="system-title">
            ระบบจองห้องเรียนอัจฉริยะ
        </div>

        <div class="system-subtitle">
            สร้างบัญชีสำหรับเข้าใช้งานระบบ
        </div>

    </div>


    <!-- Error -->

    <?php if ($error): ?>

        <div class="alert-error">

            <i class="bi bi-exclamation-circle-fill"></i>

            <div>
                <?= htmlspecialchars($error) ?>
            </div>

        </div>

    <?php endif; ?>


    <!-- Success -->

    <?php if ($success): ?>

        <div class="alert-success">

            <i class="bi bi-check-circle-fill"></i>

            <div>
                <?= htmlspecialchars($success) ?>
            </div>

        </div>

    <?php endif; ?>


    <!-- Register Form -->

    <form method="POST" id="registerForm">

        <!-- Full Name -->

        <div class="mb-3">

            <label class="form-label">
                ชื่อ-นามสกุล
            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-person"></i>
                </span>

                <input
                    type="text"
                    name="full_name"
                    class="form-control"
                    placeholder="กรอกชื่อ-นามสกุล"
                    value="<?= htmlspecialchars($full_name ?? '') ?>"
                    required
                >

            </div>

        </div>


        <!-- Email -->

        <div class="mb-3">

            <label class="form-label">
                อีเมล
            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-envelope"></i>
                </span>

                <input
                    type="email"
                    name="email"
                    class="form-control"
                    placeholder="example@email.com"
                    value="<?= htmlspecialchars($email ?? '') ?>"
                    required
                >

            </div>

        </div>


        <!-- Phone -->

        <div class="mb-3">

            <label class="form-label">
                เบอร์โทรศัพท์
            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-telephone"></i>
                </span>

                <input
                    type="tel"
                    name="phone"
                    class="form-control"
                    placeholder="กรอกเบอร์โทรศัพท์"
                    value="<?= htmlspecialchars($phone ?? '') ?>"
                    required
                >

            </div>

        </div>


        <!-- Username -->

        <div class="mb-3">

            <label class="form-label">
                ชื่อผู้ใช้งาน
            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-person-badge"></i>
                </span>

                <input
                    type="text"
                    name="username"
                    class="form-control"
                    placeholder="กรอกชื่อผู้ใช้งาน"
                    value="<?= htmlspecialchars($username ?? '') ?>"
                    required
                    autocomplete="username"
                >

            </div>

        </div>


        <!-- Password -->

        <div class="mb-3">

            <label class="form-label">
                รหัสผ่าน
            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-lock"></i>
                </span>

                <input
                    type="password"
                    name="password"
                    id="password"
                    class="form-control"
                    placeholder="กรอกรหัสผ่าน"
                    required
                    autocomplete="new-password"
                >

                <button
                    type="button"
                    class="password-toggle"
                    id="togglePassword"
                >

                    <i class="bi bi-eye"></i>

                </button>

            </div>

            <div class="password-hint">
                รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร
            </div>

        </div>


        <!-- Confirm Password -->

        <div class="mb-3">

            <label class="form-label">
                ยืนยันรหัสผ่าน
            </label>

            <div class="input-group">

                <span class="input-group-text">
                    <i class="bi bi-lock-fill"></i>
                </span>

                <input
                    type="password"
                    name="confirm_password"
                    id="confirmPassword"
                    class="form-control"
                    placeholder="กรอกรหัสผ่านอีกครั้ง"
                    required
                    autocomplete="new-password"
                >

                <button
                    type="button"
                    class="password-toggle"
                    id="toggleConfirmPassword"
                >

                    <i class="bi bi-eye"></i>

                </button>

            </div>

        </div>


        <!-- Submit -->

        <button
            type="submit"
            class="btn btn-register shadow-sm"
        >

            <span>
                สมัครสมาชิก
            </span>

            <i class="bi bi-arrow-right"></i>

        </button>

    </form>


    <!-- Back to Login -->

    <div class="back-login">

        มีบัญชีอยู่แล้ว?

        <a href="login.php">
            เข้าสู่ระบบ
        </a>

    </div>

</div>


<script>

    // =========================
    // PASSWORD TOGGLE
    // =========================

    const passwordInput =
        document.getElementById('password');

    const togglePassword =
        document.getElementById('togglePassword');

    const passwordIcon =
        togglePassword.querySelector('i');


    togglePassword.addEventListener(
        'click',
        () => {

            const isPassword =
                passwordInput.type === 'password';

            passwordInput.type =
                isPassword ? 'text' : 'password';

            passwordIcon.classList.toggle(
                'bi-eye',
                !isPassword
            );

            passwordIcon.classList.toggle(
                'bi-eye-slash',
                isPassword
            );

        }
    );


    // =========================
    // CONFIRM PASSWORD TOGGLE
    // =========================

    const confirmPasswordInput =
        document.getElementById(
            'confirmPassword'
        );

    const toggleConfirmPassword =
        document.getElementById(
            'toggleConfirmPassword'
        );

    const confirmPasswordIcon =
        toggleConfirmPassword.querySelector('i');


    toggleConfirmPassword.addEventListener(
        'click',
        () => {

            const isPassword =
                confirmPasswordInput.type === 'password';

            confirmPasswordInput.type =
                isPassword ? 'text' : 'password';

            confirmPasswordIcon.classList.toggle(
                'bi-eye',
                !isPassword
            );

            confirmPasswordIcon.classList.toggle(
                'bi-eye-slash',
                isPassword
            );

        }
    );


    // =========================
    // CHECK PASSWORD
    // =========================

    document
        .getElementById('registerForm')
        .addEventListener(
            'submit',
            function(e) {

                const password =
                    passwordInput.value;

                const confirmPassword =
                    confirmPasswordInput.value;

                if (password !== confirmPassword) {

                    e.preventDefault();

                    alert(
                        'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน'
                    );

                    confirmPasswordInput.focus();

                    return;
                }

                if (password.length < 8) {

                    e.preventDefault();

                    alert(
                        'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร'
                    );

                    passwordInput.focus();

                    return;
                }

            }
        );

</script>

</body>

</html>