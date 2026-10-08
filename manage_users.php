<?php
session_start();
// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
include 'config.php';

/* ===== ส่วนการนับแจ้งเตือนสำหรับ Sidebar ===== */
$newDevicesCount = 0;
$pendingCount = 0;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $stmtSync = $conn->query("SELECT COUNT(*) FROM project.node WHERE node_id NOT IN (SELECT node_id FROM node)");
    $newDevicesCount = $stmtSync->fetchColumn();

    $stmtP = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
    $pendingCount = $stmtP->fetchColumn();
}

/* ===== Logic การจัดการผู้ใช้งาน (คงเดิม) ===== */
if(isset($_POST['action']) && $_POST['action']=='add'){
    $stmt = $conn->prepare("INSERT INTO users (username,password,role) VALUES (?,?,?)");
    $stmt->execute([
        $_POST['username'],
        password_hash($_POST['password'], PASSWORD_DEFAULT),
        $_POST['role']
    ]);
    header('Location: manage_users.php?status=added');
    exit();
}

if(isset($_POST['action']) && $_POST['action']=='edit'){
    $id = $_POST['id'];
    $username = $_POST['username'];
    $role = $_POST['role'];

    if(!empty($_POST['password'])){
        $stmt = $conn->prepare("UPDATE users SET username=?, password=?, role=? WHERE id=?");
        $stmt->execute([$username, password_hash($_POST['password'], PASSWORD_DEFAULT), $role, $id]);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username=?, role=? WHERE id=?");
        $stmt->execute([$username, $role, $id]);
    }
    header('Location: manage_users.php?status=updated');
    exit();
}

if(isset($_GET['delete'])){
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header('Location: manage_users.php?status=deleted');
    exit();
}

$users = $conn->query("SELECT * FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            --sidebar-width: 260px;
            --bg-body: #f8f9fc;
        }

        body { 
            background: var(--bg-body);
            font-family: 'Inter', 'Sarabun', sans-serif;
            color: #1e293b;
        }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--primary-gradient);
            color: white;
            z-index: 1000;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            transition: 0.2s;
        }

        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link i { margin-right: 10px; font-size: 1.1rem; }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            min-height: 100vh;
        }

        .card {
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border-radius: 0.75rem;
            margin-bottom: 2rem;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e3e6f0;
            font-weight: bold;
            color: #4e73df;
            padding: 1.25rem;
        }

        .premium-input {
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.7rem 1rem;
            transition: 0.2s;
        }
        .premium-input:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 4px rgba(78, 115, 223, 0.1);
        }

        .table thead th {
            background: #f8fafc;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.8rem;
            padding: 1.25rem;
            border-bottom: 2px solid #edf2f7;
        }

        .badge-role {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .user-avatar {
            width: 38px;
            height: 38px;
            background: #e2e8f0;
            color: #4e73df;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            font-weight: bold;
        }

        .sidebar {
    width: var(--sidebar-width);
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    background: var(--primary-gradient);
    color: white;
    z-index: 1060;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;

    overflow-y: auto;
}

/* Scrollbar สำหรับ Sidebar */

.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.05);
    border-radius: 10px;
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.35);
    border-radius: 10px;
    transition: 0.3s;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.6);
}

.sidebar {
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.4) transparent;
}

        @media (max-width: 992px) {
            .sidebar { margin-left: calc(-1 * var(--sidebar-width)); }
            .main-content { margin-left: 0; }
        }
    </style>
</head>

<body>

<nav class="sidebar shadow">
    <div class="p-3 text-center">
        <h5 class="fw-bold mb-0">Classroom System</h5>
        <hr class="mt-3 mb-2 opacity-25">
    </div>
    <div class="nav flex-column mt-3">
        <a href="index.php" class="nav-link"><i class="bi bi-speedometer2"></i> หน้าแรก</a>
        <a href="book_room.php" class="nav-link"><i class="bi bi-calendar-plus"></i> จองห้องเรียน</a>
        <a href="my_bookings.php" class="nav-link"><i class="bi bi-person-workspace"></i> ประวัติการจอง</a>
        
        <div class="px-3 mt-4 mb-2 small text-uppercase opacity-50">Admin Panel</div>
        <a href="admin_bookings.php" class="nav-link">
            <i class="bi bi-shield-check"></i> จัดการการจอง
            <?php if ($pendingCount > 0): ?>
                <span class="badge bg-danger ms-auto"><?= $pendingCount ?></span>
            <?php endif; ?>
        </a>
        <a href="manage_users.php" class="nav-link active"><i class="bi bi-people"></i> จัดการผู้ใช้</a>
        <!-- <a href="http://127.0.0.1:5000" target="_blank" class="nav-link"><i class="bi bi-hdd-network"></i> จัดการโครงสร้างระบบ</a> -->
        <a href="sso.php" target="_blank" class="nav-link"><i class="bi bi-hdd-network"></i> จัดการโครงสร้างระบบ</a>
        <a href="run_sync.php" class="nav-link">
            <i class="bi bi-arrow-repeat"></i> ซิงค์อุปกรณ์
            <?php if ($newDevicesCount > 0): ?>
                <span class="badge bg-light text-danger ms-auto"><?= $newDevicesCount ?></span>
            <?php endif; ?>
        </a>
        <a href="force_sync.php" id="forceSyncBtn" class="nav-link">
                <i class="bi bi-arrow-repeat"></i> อัปเดตข้อมูลระบบ
            </a>
        <a href="device_dashboard.php" class="nav-link">
                <i class="bi bi-hdd-stack"></i> สถานะอุปกรณ์
        </a>
        <a href="change_password.php" class="nav-link">
        <i class="bi bi-key"></i> เปลี่ยนรหัสผ่าน
        </a>
        <hr class="mx-3 mt-4 mb-2 opacity-25">
        <a href="logout.php" class="nav-link text-white-50">
            <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
        </a>
    </div>
</nav>

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded-4">
        <div>
            <h4 class="mb-0 fw-bold">จัดการผู้ใช้งาน</h4>
            <small class="text-muted">เพิ่มหรือจัดการผู้ใช้ใหม่</small>
        </div>
        <div class="text-primary me-2">
            <i class="bi bi-person-circle fs-4"></i>
        </div>
    </div>

    <div class="card border-0">
        <div class="card-header border-0 bg-white">
            <i class="bi bi-person-plus-fill me-2"></i> เพิ่มผู้ใช้ใหม่
        </div>
        <div class="card-body p-4">
            <form method="POST" class="row g-3 align-items-end">
                <input type="hidden" name="action" value="add">
                
                <div class="col-md-4">
                    <label class="form-label small fw-bold">ชื่อผู้ใช้งาน</label>
                    <input type="text" name="username" class="form-control premium-input" placeholder="กรอกชื่อผู้ใช้" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold">รหัสผ่าน</label>
                    <input type="password" name="password" class="form-control premium-input" placeholder="กรอกรหัสผ่าน" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-bold">สิทธิ์การใช้งาน</label>
                    <select name="role" class="form-select premium-input">
                        <option value="user">ผู้ใช้ทั่วไป (User)</option>
                        <option value="admin">ผู้ดูแลระบบ (Admin)</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm">
                        เพิ่มข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0">
        <div class="card-header border-0 bg-white d-flex justify-content-between align-items-center">
            <span><i class="bi bi-people-fill me-2"></i> บัญชีผู้ใช้ทั้งหมด</span>
            <span class="badge bg-primary rounded-pill px-3"><?= count($users) ?> บัญชี</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 80px;">ID</th>
                        <th>ชื่อผู้ใช้งาน</th>
                        <th class="text-center">ระดับสิทธิ์</th>
                        <th class="text-end pe-4">การจัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($users as $u): ?>
                    <tr>
                        <td class="text-center text-muted fw-bold"><?= $u['id'] ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="user-avatar me-3">
                                    <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                </div>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($u['username']) ?></span>
                            </div>
                        </td>
                        <td class="text-center">
                            <?php if($u['role'] == 'admin'): ?>
                                <span class="badge badge-role bg-danger-subtle text-danger border border-danger-subtle">
                                    <i class="bi bi-shield-check me-1"></i> ADMIN
                                </span>
                            <?php else: ?>
                                <span class="badge badge-role bg-primary-subtle text-primary border border-primary-subtle">
                                    <i class="bi bi-person me-1"></i> USER
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-4">
                            <button 
                                class="btn btn-sm btn-light border rounded-pill px-3 me-1 fw-bold"
                                data-bs-toggle="modal" 
                                data-bs-target="#editModal"
                                data-id="<?= $u['id'] ?>"
                                data-username="<?= $u['username'] ?>"
                                data-role="<?= $u['role'] ?>">
                                <i class="bi bi-pencil me-1 text-primary"></i> แก้ไข
                            </button>

                            <?php if($u['username'] != 'admin'): ?>
                            <a href="?delete=<?= $u['id'] ?>" 
                               class="btn btn-sm btn-light border rounded-pill px-3 text-danger fw-bold"
                               onclick="return confirm('ยืนยันที่จะลบผู้ใช้นี้?')">
                               <i class="bi bi-trash3 me-1"></i> ลบ
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-header border-0 p-4 pb-0">
                    <h5 class="fw-bold text-primary"><i class="bi bi-pencil-square me-2"></i>แก้ไขข้อมูลผู้ใช้</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">ชื่อผู้ใช้งาน</label>
                        <input type="text" name="username" id="edit-username" class="form-control premium-input" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">เปลี่ยนรหัสผ่าน <span class="text-muted fw-normal">(ปล่อยว่างถ้าไม่เปลี่ยน)</span></label>
                        <input type="password" name="password" class="form-control premium-input" placeholder="••••••••">
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">ระดับสิทธิ์</label>
                        <select name="role" id="edit-role" class="form-select premium-input">
                            <option value="user">ผู้ใช้ทั่วไป (User)</option>
                            <option value="admin">ผู้ดูแลระบบ (Admin)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">บันทึกข้อมูล</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const editModal = document.getElementById('editModal');
    editModal.addEventListener('show.bs.modal', e => {
        const btn = e.relatedTarget;
        document.getElementById('edit-id').value = btn.dataset.id;
        document.getElementById('edit-username').value = btn.dataset.username;
        document.getElementById('edit-role').value = btn.dataset.role;
    });

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('status')) {
        const status = urlParams.get('status');
        let title = '';
        let icon = 'success';
        if(status === 'added') title = 'เพิ่มผู้ใช้สำเร็จ';
        if(status === 'updated') title = 'อัปเดตข้อมูลสำเร็จ';
        if(status === 'deleted') { title = 'ลบข้อมูลสำเร็จ'; icon = 'warning'; }
        if(title) {
            Swal.fire({ icon: icon, title: title, timer: 1500, showConfirmButton: false }).then(() => {
                window.history.replaceState({}, document.title, window.location.pathname);
            });
        }
    }
</script>
</body>
</html>