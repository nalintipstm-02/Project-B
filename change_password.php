<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
include 'config.php';

// --- Logic ส่วนการนับแจ้งเตือนสำหรับ Sidebar ---
$newDevicesCount = 0;
$pendingCount = 0;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $stmtSync = $conn->query("SELECT COUNT(*) FROM project.node WHERE node_id NOT IN (SELECT node_id FROM node)");
    $newDevicesCount = $stmtSync->fetchColumn();

    $stmtP = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
    $pendingCount = $stmtP->fetchColumn();
}

// --- Logic การเปลี่ยนรหัสผ่าน ---
$success = "";
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id = $_SESSION['user_id'];
    $current = $_POST['current_password'];
    $new     = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    try {

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = "ไม่พบผู้ใช้งาน";
        }
        elseif (!password_verify($current, $user['password'])) {
            $error = "รหัสผ่านปัจจุบันไม่ถูกต้อง";
        }
        elseif ($new !== $confirm) {
            $error = "รหัสผ่านใหม่ไม่ตรงกัน";
        }
        else {

            $hash = password_hash($new, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->execute([$hash, $user_id]);

            $success = "เปลี่ยนรหัสผ่านสำเร็จ";
        }

    } catch (PDOException $e) {
        $error = "เกิดข้อผิดพลาด";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เปลี่ยนรหัสผ่าน</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Sarabun:wght@400;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            --sidebar-width: 260px;
            --primary-color: #4e73df;
        }

        body {
            background-color: #f8f9fc;
            font-family: 'Inter', 'Sarabun', sans-serif;
            margin: 0;
            overflow-x: hidden;
        }

        /* --- Sidebar & Responsive Navigation --- */
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0; left: 0;
            background: var(--primary-gradient);
            color: white;
            z-index: 1060;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            overflow-y: auto;
        }

        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.35); border-radius: 10px; }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            transition: 0.2s;
            text-decoration: none;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link i { margin-right: 10px; font-size: 1.1rem; }

        .mobile-header-bar {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0;
            height: 60px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            z-index: 1040;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 0 15px;
            align-items: center;
            justify-content: space-between;
        }

        .mobile-nav-toggle {
            background: #f8f9fc;
            color: var(--primary-color);
            width: 42px; height: 42px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.05);
            display: flex; align-items: center; justify-content: center;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.4);
            z-index: 1050;
            backdrop-filter: blur(4px);
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
            transition: margin-left 0.3s ease;
        }

        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border-radius: 1rem;
        }

        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .mobile-header-bar { display: flex; }
            .main-content { margin-left: 0; padding: 1rem; padding-top: 5rem; }
        }

        /* เพิ่ม/แก้ไขเฉพาะส่วนนี้เพื่อให้ Hint ตัวเล็กและดูง่าย */
    .form-control::placeholder {
        color: #adb5bd !important; /* สีเทาจาง */
        font-size: 0.85rem;        /* ขนาดตัวเล็กกว่าปกติ */
        font-weight: 400;          /* ลายเส้นบางลง */
        opacity: 0.8;
    }

    /* สำหรับเบราว์เซอร์อื่นๆ */
    .form-control::-webkit-input-placeholder {
        color: #adb5bd;
        font-size: 0.85rem;
    }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="mobile-header-bar">
    <h2 style="font-weight: 800; color: var(--primary-color); margin: 0; font-size: 1.1rem;">Classroom System</h2>
    <button class="mobile-nav-toggle" onclick="toggleSidebar()">
        <i class="bi bi-list fs-3"></i>
    </button>
</div>

<nav class="sidebar shadow" id="sidebarMenu">
    <div class="p-3 text-center position-relative">
        <h5 class="fw-bold mb-0">Classroom System</h5>
        <button class="btn btn-sm text-white d-lg-none position-absolute top-50 end-0 translate-middle-y me-2" onclick="toggleSidebar()">
            <i class="bi bi-chevron-left"></i>
        </button>
        <hr class="mt-3 mb-2 opacity-25">
    </div>
    <div class="nav flex-column mt-3">
        <a href="index.php" class="nav-link"><i class="bi bi-speedometer2"></i> หน้าแรก</a>
        <a href="book_room.php" class="nav-link"><i class="bi bi-calendar-plus"></i> จองห้องเรียน</a>
        <a href="my_bookings.php" class="nav-link"><i class="bi bi-person-workspace"></i> ประวัติการจอง</a>
        
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <div class="px-3 mt-4 mb-2 small text-uppercase opacity-50">Admin Panel</div>
            <a href="admin_bookings.php" class="nav-link">
                <i class="bi bi-shield-check"></i> จัดการการจอง
                <?php if ($pendingCount > 0): ?>
                    <span class="badge bg-danger ms-auto rounded-pill"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
            <a href="manage_users.php" class="nav-link"><i class="bi bi-people"></i> จัดการผู้ใช้</a>
            <!-- <a href="http://127.0.0.1:5000" target="_blank" class="nav-link"><i class="bi bi-hdd-network"></i> จัดการโครงสร้างระบบ</a> -->
            <a href="sso.php" target="_blank" class="nav-link"><i class="bi bi-hdd-network"></i> จัดการโครงสร้างระบบ</a>
            <a href="run_sync.php" class="nav-link">
                <i class="bi bi-arrow-repeat"></i> ซิงค์อุปกรณ์
                <?php if ($newDevicesCount > 0): ?>
                    <span class="badge bg-light text-danger ms-auto rounded-pill"><?= $newDevicesCount ?></span>
                <?php endif; ?>
            </a>
            <a href="force_sync.php" id="forceSyncBtn" class="nav-link">
                <i class="bi bi-arrow-repeat"></i> อัปเดตข้อมูลระบบ
            </a>
            <a href="device_dashboard.php" class="nav-link">
                <i class="bi bi-hdd-stack"></i> สถานะอุปกรณ์
            </a>
        <?php endif; ?>

        <a href="change_password.php" class="nav-link active">
            <i class="bi bi-key"></i> เปลี่ยนรหัสผ่าน
        </a>

        <hr class="mx-3 mt-4 mb-2 opacity-25">
        <a href="logout.php" class="nav-link text-white-50 mt-auto mb-3">
            <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
        </a>
    </div>
</nav>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded-4">
        <div>
            <h4 class="mb-0 fw-bold">เปลี่ยนรหัสผ่าน</h4>
        </div>
        <div class="text-primary me-2">
            <i class="bi bi-person-circle fs-4"></i>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-5">
                        <!--<div class="bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px;">
                            <i class="bi bi-shield-lock fs-2"></i>
                        </div>-->
                        <h5 class="fw-bold">เปลี่ยนแปลงรหัสผ่าน</h5>
                    </div>

                    <?php if($error): ?>
                        <div class="alert alert-danger border-0 small mb-4">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">รหัสผ่านปัจจุบัน</label>
                            <input type="password" name="current_password" 
                                   class="form-control form-control-lg fs-6" 
                                   style="border-radius: 12px;" 
                                   placeholder="กรอกรหัสผ่านปัจจุบัน" required>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold text-muted">รหัสผ่านใหม่</label>
                            <input type="password" name="new_password" 
                                   class="form-control form-control-lg fs-6" 
                                   style="border-radius: 12px;" 
                                   placeholder="กรอกรหัสผ่านใหม่" required>
                        </div>

                        <div class="mb-5">
                            <label class="form-label small fw-bold text-muted">ยืนยันรหัสผ่านใหม่</label>
                            <input type="password" name="confirm_password" 
                                   class="form-control form-control-lg fs-6" 
                                   style="border-radius: 12px;" 
                                   placeholder="ยืนยันรหัสผ่าน" required>
                        </div>

                        <div class="text-center mt-2">
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm" style="border-radius: 12px; font-size: 1rem;">
                            <i class="bi bi-check2-circle me-2"></i> บันทึกการเปลี่ยนแปลง
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebarMenu');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}

<?php if($success): ?>
    Swal.fire({
        icon: 'success',
        title: 'เรียบร้อย',
        text: '<?= $success ?>',
        confirmButtonColor: '#4e73df',
        timer: 2000
    }).then(() => {
        window.location.href = 'index.php';
    });
<?php endif; ?>
</script>
</body>
</html>