<?php
session_start();
date_default_timezone_set('Asia/Bangkok');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
include 'config.php';

// --- ส่วนการนับแจ้งเตือนสำหรับ Sidebar ---
$newDevicesCount = 0;
$pendingCount = 0;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    try {
        $stmtSync = $conn->query("SELECT COUNT(*) FROM project.node WHERE node_id NOT IN (SELECT node_id FROM node)");
        $newDevicesCount = (int)$stmtSync->fetchColumn();
        $stmtP = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
        $pendingCount = (int)$stmtP->fetchColumn();
    } catch (Exception $e) {}
}

/* ดึงห้องสำหรับ Filter */
$rooms = $conn->query("SELECT room_id, room_name FROM room ORDER BY room_name")->fetchAll(PDO::FETCH_ASSOC);
$selected_room = $_GET['room_id'] ?? null;

/* ดึงข้อมูลอุปกรณ์ สถานะล่าสุด และเวลาเรียนแรกของวัน */
$sql = "
SELECT
    r.room_name,
    r.room_id,
    n.node_id,
    n.detail,
    n.node_type,
    n.computer_role,
    ds.channel,
    ds.status,
    ds.updated_at,
    (
        SELECT MIN(start_time)
        FROM booking_system.bookings b
        WHERE b.room_id = r.room_id
        AND b.status = 'approved'
        AND DATE(b.start_time) = CURDATE()
    ) AS first_class_time

FROM project.installation_node i

JOIN booking_system.room r
    ON i.room_id = r.room_id

JOIN project.node n
    ON i.node_id = n.node_id
    AND n.node_status = 'active'

-- อ่านสถานะจาก device_status โดยตรงทั้งหมดผ่าน node_id
LEFT JOIN project.device_status ds
    ON ds.node_id = n.node_id

WHERE 1=1
";

$execute_params = [];

// กรองเฉพาะห้องที่เลือก
if (!empty($selected_room)) {
    $sql .= " AND i.room_id = :room_id";
    $execute_params[':room_id'] = $selected_room;
}

$sql .= " ORDER BY r.room_name, n.node_id";

$stmt = $conn->prepare($sql);
$stmt->execute($execute_params);
$raw_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// จัดกลุ่มข้อมูล
$devices = [];

foreach ($raw_data as $row) {
    $nid = $row['node_id'];
    $status_val = strtolower(trim($row['status'] ?? 'off'));

    if (!isset($devices[$nid])) {
        $devices[$nid] = [
            'room_name'        => $row['room_name'],
            'detail'           => $row['detail'],
            'node_type'        => $row['node_type'],
            'computer_role'    => $row['computer_role'],
            'status'           => $status_val,
            'updated_at'       => $row['updated_at'] ?? null,
            'first_class_time' => $row['first_class_time'] ?? null
        ];
    } else {
        // หากมีหลายแถว เอาอัปเดตและสถานะแถวล่าสุด
        if (!empty($row['updated_at'])) {
            $devices[$nid]['updated_at'] = $row['updated_at'];
        }
        if ($status_val === 'on' || $status_val === '1') {
            $devices[$nid]['status'] = $status_val;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สถานะอุปกรณ์</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    
    <style>
        :root { 
            --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%); 
            --sidebar-width: 260px; 
        }
        body { background-color: #f8f9fc; font-family: 'Inter', 'Sarabun', sans-serif; }
        
        /* Sidebar Style */
        .sidebar { 
            width: var(--sidebar-width); height: 100vh; position: fixed; 
            top: 0; left: 0; background: var(--primary-gradient); 
            color: white; z-index: 1060; display: flex; flex-direction: column; 
            overflow-y: auto; scrollbar-width: thin; scrollbar-color: rgba(255,255,255,0.4) transparent;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar::-webkit-scrollbar { width: 6px; }
        .sidebar::-webkit-scrollbar-track { background: rgba(255,255,255,0.05); border-radius: 10px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.35); border-radius: 10px; }
        .sidebar::-webkit-scrollbar-thumb:hover { background: rgba(255,255,255,0.6); }

        .sidebar .nav-link { 
            color: rgba(255, 255, 255, 0.8); padding: 1rem 1.5rem; 
            display: flex; align-items: center; transition: 0.2s; 
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { 
            color: white; background: rgba(255, 255, 255, 0.1); 
        }
        .sidebar .nav-link i { margin-right: 10px; font-size: 1.1rem; }

        .main-content { margin-left: var(--sidebar-width); padding: 2rem; min-height: 100vh; }
        
        /* Card & Status Style */
        .card { border: none; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); border-radius: 1rem; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 5px; }
        
        .device-card {
            border: none;
            border-radius: 15px;
            transition: all 0.3s ease;
            background: #ffffff;
        }
        .device-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.05) !important;
        }
        
        @media (max-width: 992px) { 
            .sidebar { display: none; } 
            .main-content { margin-left: 0; } 
        }
    </style>
</head>
<body>

<nav class="sidebar shadow">
    <div class="p-3 text-center">
        <h5 class="fw-bold mb-0 text-white">Classroom System</h5>
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
            <a href="sso.php" target="_blank" class="nav-link"><i class="bi bi-hdd-network"></i> จัดการโครงสร้างระบบ</a>
            <a href="run_sync.php" id="syncBtn" class="nav-link">
                <i class="bi bi-arrow-repeat"></i> ซิงค์อุปกรณ์
                <?php if ($newDevicesCount > 0): ?>
                    <span class="badge bg-light text-danger ms-auto rounded-pill"><?= $newDevicesCount ?></span>
                <?php endif; ?>
            </a>
            <a href="force_sync.php" id="forceSyncBtn" class="nav-link">
                <i class="bi bi-arrow-repeat"></i> อัปเดตข้อมูลระบบ
            </a>
            <a href="device_dashboard.php" class="nav-link active">
                <i class="bi bi-hdd-stack"></i> สถานะอุปกรณ์
            </a>
        <?php endif; ?>

        <a href="change_password.php" class="nav-link">
            <i class="bi bi-key"></i> เปลี่ยนรหัสผ่าน
        </a>

        <hr class="mx-3 mt-4 mb-2 opacity-25">
        <a href="logout.php" class="nav-link text-white-50">
            <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
        </a>
    </div>
</nav>

<!-- Modal สำหรับแสดง QR Code ขนาดใหญ่ -->
<?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
<div class="modal fade" id="lineQrModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content text-center p-3" style="border-radius: 20px;">
            <div class="modal-header border-0 pb-0">
                <div class="modal-title fw-bold text-dark d-flex align-items-center gap-2 text-nowrap" style="font-size: 0.85rem;">
                    <i class="bi bi-line text-success fs-5"></i> สแกนเข้าร่วมกลุ่มเพื่อรับแจ้งเตือน
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div class="bg-white p-2 rounded-3 d-inline-block border shadow-sm mb-2">
                    <img src="img/booking.jpg" alt="LINE QR Code" style="width: 220px; height: 220px; display: block;">
                </div>
                <p class="text-muted small mb-0" style="font-size: 0.75rem;">
                    สแกน QR Code เพื่อรับแจ้งเตือนผ่าน Line
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="main-content p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded-4">
        <div>
            <h4 class="mb-0 fw-bold">สถานะอุปกรณ์</h4>
            <small class="text-muted">ตรวจสอบสถานะการทำงานของอุปกรณ์ภายในห้อง</small>
        </div>
        
        <div class="d-flex align-items-center gap-3">
            <form method="GET" class="d-flex align-items-center">
                <select name="room_id" class="form-select rounded-pill shadow-sm" onchange="this.form.submit()" style="min-width: 200px;">
                    <option value="">แสดงทุกห้องเรียน</option>
                    <?php foreach($rooms as $r): ?>
                        <option value="<?= $r['room_id'] ?>" <?= ($selected_room == $r['room_id']) ? 'selected' : '' ?>>
                            ห้อง <?= htmlspecialchars($r['room_name']) ?>
                        </option>
                    <?php endforeach;?>
                </select>
            </form>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <button type="button" class="btn btn-outline-success rounded-pill d-flex align-items-center gap-2 shadow-sm px-3" data-bs-toggle="modal" data-bs-target="#lineQrModal">
                    <i class="bi bi-line fs-5"></i>
                    <span class="fw-bold small d-none d-sm-inline">รับแจ้งเตือนผ่าน LINE</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if(!empty($devices)): ?>
        <?php 
        $grouped = [];
        foreach($devices as $d) { 
            $grouped[$d['room_name']][] = $d; 
        }
        
        foreach($grouped as $roomName => $items): 
        ?>
        
        <div class="d-flex align-items-center mb-4 mt-2">
            <h5 class="fw-bold text-dark mb-0">
                <i class="bi bi-door-open-fill text-primary me-2"></i>ห้อง <?= htmlspecialchars($roomName) ?>
            </h5>
            <div class="flex-grow-1 ms-3 border-top opacity-25"></div>
        </div>

        <div class="row g-4 mb-5">
            <?php foreach($items as $d): 
                $is_led = ($d['node_type'] == 'led');
                $is_online = ($d['updated_at'] && (time() - strtotime($d['updated_at'])) <= 30);
            ?>
            <div class="col-12 col-lg-6 col-xl-4">
                <div class="card device-card shadow-sm h-100 border-0 rounded-4 overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="fw-bold mb-1">
                                    <?= htmlspecialchars($d['detail']) ?>
                                </h6>
                                <span class="badge bg-light text-secondary fw-normal border" style="font-size: 0.7rem;">
                                    <?= strtoupper($d['node_type']) ?>
                                </span>
                            </div>
                            <div class="text-end">
                                <div class="small fw-bold <?= $is_online ? 'text-success' : 'text-muted' ?>">
                                    <span class="status-dot <?= $is_online ? 'bg-success' : 'bg-secondary' ?>"></span>
                                    <?= $is_online ? 'ออนไลน์' : 'ออฟไลน์' ?>
                                </div>
                            </div>
                        </div>

                        <!-- แสดงสถานะหลัก ON/OFF แบบเดียวกันทุกอุปกรณ์ -->
                        <div class="d-flex align-items-center justify-content-between mt-4 p-2 bg-light rounded-3">
                            <span class="text-muted small">สถานะการทำงาน</span>
                            <?php
                            $status_text = 'OFF';
                            $status_class = 'bg-danger';

                            $current_status = strtolower(trim($d['status'] ?? 'off'));

                            if ($d['node_type'] == 'computer') {
                                if ($d['computer_role'] == 'student') {
                                    $now = time();
                                    $first_class_str = $d['first_class_time'] ?? null;
                                    $first_class = $first_class_str ? strtotime($first_class_str) : null;

                                    if ($first_class && $now < $first_class) {
                                        $status_text = 'OFF';
                                        $status_class = 'bg-danger';
                                    } else {
                                        $status_text = 'USER CONTROL';
                                        $status_class = 'bg-warning text-dark';
                                    }
                                } else { // คอมอาจารย์
                                    if ($current_status == 'on' || $current_status == '1') {
                                        $status_text = 'ON';
                                        $status_class = 'bg-success';
                                    } else {
                                        $status_text = 'OFF';
                                        $status_class = 'bg-danger';
                                    }
                                }
                            } else { // อุปกรณ์ทั่วไป และ หลอดไฟ
                                if ($current_status == 'on' || $current_status == '1') {
                                    $status_text = 'ON';
                                    $status_class = 'bg-success';
                                } else {
                                    $status_text = 'OFF';
                                    $status_class = 'bg-danger';
                                }
                            }
                            ?>

                            <span class="badge <?= $status_class ?> rounded-pill px-3 shadow-sm">
                                <?= $status_text ?>
                            </span>
                        </div>
                        
                        <div class="mt-3 pt-3 border-top text-center">
                            <small class="text-muted" style="font-size: 0.7rem;">
                                <i class="bi bi-clock-history me-1"></i> 
                                อัปเดตล่าสุด: <?= $d['updated_at'] ? date('H:i:s', strtotime($d['updated_at'])) : '-' ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
    <div class="text-center py-5 bg-white rounded-4 shadow-sm">
        <i class="bi bi-cpu text-muted" style="font-size: 3rem;"></i>
        <p class="mt-3 text-muted fw-bold">ไม่พบข้อมูลอุปกรณ์ในห้องเรียนนี้</p>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    const REFRESH_INTERVAL = 10000; 
    let refreshTimer = setTimeout(autoRefresh, REFRESH_INTERVAL);

    function autoRefresh() {
        window.location.reload();
    }

    const lineModal = document.getElementById('lineQrModal');

    if (lineModal) {
        lineModal.addEventListener('show.bs.modal', function () {
            clearTimeout(refreshTimer);
        });

        lineModal.addEventListener('hidden.bs.modal', function () {
            refreshTimer = setTimeout(autoRefresh, REFRESH_INTERVAL);
        });
    }
</script>
</body>
</html>