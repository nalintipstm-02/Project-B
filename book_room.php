<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
include 'config.php';
include 'line_notify.php';
include 'sync_mqtt.php';

$success = '';
$error = '';

// --- ดึงข้อมูลจำนวนการแจ้งเตือนสำหรับ Sidebar ---
$newDevicesCount = 0;
$pendingCount = 0;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $stmtSync = $conn->query("SELECT COUNT(*) FROM project.node WHERE node_id NOT IN (SELECT node_id FROM node)");
    $newDevicesCount = $stmtSync->fetchColumn();

    $stmtP = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
    $pendingCount = $stmtP->fetchColumn();
}

/* ===============================
   1. ดึงข้อมูลห้อง + อุปกรณ์
   =============================== */
$sql = "
    SELECT r.room_id, r.room_name, n.node_id, n.detail, n.node_type,
       n.computer_role,
       dc.id AS channel_id, dc.name AS channel_name
    FROM room r
    LEFT JOIN installation_node ins ON ins.room_id = r.room_id
    LEFT JOIN node n ON n.node_id = ins.node_id
    LEFT JOIN project.device_channel dc ON n.node_id = dc.node_id
    WHERE n.node_status = 'active'
    ORDER BY r.room_name, n.node_id, dc.id
";
$data = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// --- แทนที่ Loop foreach เดิม (ประมาณบรรทัดที่ 35) ด้วย Loop นี้ ---
$rooms = [];
foreach ($data as $row) {
    $rid = $row['room_id'];
    if (!isset($rooms[$rid])) {
        $rooms[$rid] = ['room_id' => $rid, 'room_name' => $row['room_name'], 'nodes' => []];
    }
    if ($row['node_id']) {
        $nid = $row['node_id'];
        // จัดกลุ่ม Node และ Channel
        if (!isset($rooms[$rid]['nodes'][$nid])) {
            $rooms[$rid]['nodes'][$nid] = [
                'node_id' => $nid, 
                'detail' => $row['detail'], 
                'node_type' => $row['node_type'],
                'computer_role' => $row['computer_role'], // เก็บ type ไว้เช็ค
                'channels' => []
            ];
        }
        if ($row['channel_id']) {
            $rooms[$rid]['nodes'][$nid]['channels'][] = [
                'channel_id' => $row['channel_id'],
                'name' => $row['channel_name']
            ];
        }
    }
}
// ปรับโครงสร้าง Array ใหม่อีกครั้งเพื่อให้ JSON ใน JS ใช้ง่าย
foreach ($rooms as $key => $room) {
    $rooms[$key]['nodes'] = array_values($room['nodes']);
}
$all_rooms = array_values($rooms);

/* ===============================
   2. Logic การบันทึกการจอง (ปรับปรุงให้ Sync ข้อมูลไป project)
   =============================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $room_id    = $_POST['room_id'];
    $short_description = trim($_POST['short_description']);
    $detail = trim($_POST['detail'] ?? '');

    if(!empty($_POST['start_time']) && !empty($_POST['end_time'])){
    $start_time = str_replace('T', ' ', $_POST['start_time']) . ':00';
    $end_time   = str_replace('T', ' ', $_POST['end_time']) . ':00';

    if(strtotime($start_time) < time()){
            $error = "ไม่สามารถจองเวลาย้อนหลังได้";
        }
}else{

    $error = "กรุณาเลือกเวลา";
}
    $booking_date = date('Y-m-d', strtotime($start_time));
    $user_id = $_SESSION['user_id'];

    // --- กำหนดสถานะตาม Role ---
    $booking_status = ($_SESSION['role'] === 'admin') ? 'approved' : 'pending';

    // ตรวจสอบการจองซ้ำ
    // เริ่ม transaction ก่อนตรวจสอบ
$conn->beginTransaction();
if(empty($_POST['start_time']) || empty($_POST['end_time'])){
    $error = "กรุณาเลือกเวลาเริ่มต้นและสิ้นสุด";
}

// ตรวจสอบการจองซ้ำ (Lock row)
$check = $conn->prepare("
    SELECT id FROM bookings 
    WHERE room_id = ?
    AND status IN ('pending','approved')
    AND (start_time < ? AND end_time > ?)
    FOR UPDATE
");

$check->execute([$room_id, $end_time, $start_time]);

if ($check->rowCount() > 0) {

    $conn->rollBack();
    $error = "ช่วงเวลานี้มีการจองแล้ว กรุณาตรวจสอบตารางเวลาอีกครั้ง";

} else {
        
        try {

    // 1️⃣ insert booking_system.bookings
    $stmt = $conn->prepare("
        INSERT INTO bookings 
        (user_id, first_name, last_name, room_id, booking_date, start_time, end_time, status, short_description, detail)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $user_id,
        $first_name,
        $last_name,
        $room_id,
        $booking_date,
        $start_time,
        $end_time,
        $booking_status,
        $short_description,
        $detail
    ]);

    $booking_id = $conn->lastInsertId();


    // 2️⃣ insert booking_equipments
    $stmt2 = $conn->prepare("
        INSERT INTO booking_equipments (booking_id, node_id) 
        VALUES (?, ?)
    ");

    foreach (($_POST['node'] ?? []) as $nid) {
        $stmt2->execute([$booking_id, $nid]);
    }


    // --- ถ้า admin จอง → sync ไป project DB ---
    if ($booking_status === 'approved') {

        $conn_project = new PDO(
            "mysql:host=localhost;dbname=project;charset=utf8",
            "root",
            ""
        );

        $conn_project->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn_project->beginTransaction();

        // 3️⃣ insert project.bookings
        $insertP = $conn_project->prepare("
            INSERT INTO bookings 
            (id, room_id, booking_date, start_time, end_time, status)
            VALUES (?, ?, ?, ?, ?, 'approved')
            ON DUPLICATE KEY UPDATE
                room_id = VALUES(room_id),
                booking_date = VALUES(booking_date),
                start_time = VALUES(start_time),
                end_time = VALUES(end_time),
                status = 'approved'
        ");

        $insertP->execute([
            $booking_id,
            $room_id,
            $booking_date,
            $start_time,
            $end_time
        ]);


        // --- รวม node ---
        $selected_nodes = $_POST['node'] ?? [];

        if (isset($_POST['led_channels'])) {
            foreach ($_POST['led_channels'] as $nid => $channels) {
                $selected_nodes[] = $nid;
            }
        }

        $selected_nodes = array_unique($selected_nodes);


        // 4️⃣ cluster_booking_node_map
        if (!empty($selected_nodes)) {

            $insertMap = $conn_project->prepare("
                INSERT IGNORE INTO cluster_booking_node_map 
                (booking_id, node_id)
                VALUES (?, ?)
            ");

            foreach ($selected_nodes as $nid) {
                $insertMap->execute([$booking_id, $nid]);
            }
        }


        // 5️⃣ booking_led_channel
        if (isset($_POST['led_channels'])) {

            $insertLedCh = $conn_project->prepare("
                INSERT INTO booking_led_channel 
                (booking_id, node_id, channel_id)
                VALUES (?, ?, ?)
            ");

            foreach ($_POST['led_channels'] as $nid => $channels) {
                foreach ($channels as $chid) {
                    $insertLedCh->execute([$booking_id, $nid, $chid]);
                }
            }
        }

        $conn_project->commit();
    }


    // commit booking_system
    $conn->commit();


    // แจ้ง RPi sync
    publishSyncEvent("booking_bundle");


    if ($booking_status === 'approved') {
        $success = "จองห้องเรียนสำเร็จ";
    } else {
        $success = "ส่งคำขอจองห้องเรียนสำเร็จ กรุณารอการอนุมัติ";
    }

} catch (Exception $e) {

    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    if (isset($conn_project) && $conn_project->inTransaction()) {
        $conn_project->rollBack();
    }

    $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
}
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จองห้องเรียน - Classroom System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%); 
            --sidebar-width: 260px; 
            --primary-color: #4e73df;
        }
        body { background-color: #f8f9fc; font-family: 'Inter', 'Sarabun', sans-serif; margin: 0; overflow-x: hidden; }
        
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

        /* --- Improved Mobile Header & Toggle --- */
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
            width: 42px;
            height: 42px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .mobile-logo-text {
            font-weight: 800;
            color: var(--primary-color);
            margin: 0;
            font-size: 1.1rem;
            letter-spacing: -0.5px;
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

        .card { border: none; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); border-radius: 1rem; }
        .form-label { font-weight: 600; color: #4e73df; }
        .form-control, .form-select { border-radius: 0.5rem; padding: 0.6rem 1rem; border: 1px solid #d1d3e2; transition: all 0.2s; }
        #equipment-container { background: #f8f9fc; border-radius: 0.75rem; border: 1px dashed #4e73df; min-height: 100px; }
        .hover-shadow-sm:hover { box-shadow: 0 4px 8px rgba(0,0,0,0.05); cursor: pointer; border-color: #4e73df !important; }

        @media (max-width: 992px) { 
            .sidebar { transform: translateX(-100%); } 
            .sidebar.show { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .mobile-header-bar { display: flex; }
            .main-content { margin-left: 0; padding: 1rem; padding-top: 5rem; }
            .desktop-header { display: none !important; }
        }
    </style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<div class="mobile-header-bar">
    <h2 class="mobile-logo-text">CLASSROOM</h2>
    <button class="mobile-nav-toggle" onclick="toggleSidebar()">
        <!-- ใช้ bi-list เพื่อให้ไอคอน 3 ขีดตรงเท่ากัน -->
        <i class="bi bi-list fs-3"></i>
    </button>
</div>

<nav class="sidebar shadow" id="sidebarMenu">
    <div class="p-3 text-center">
        <h5 class="fw-bold mb-0">Classroom System</h5>
        <hr class="mt-3 mb-2 opacity-25">
    </div>
    <div class="nav flex-column mt-3">
        <a href="index.php" class="nav-link"><i class="bi bi-speedometer2"></i> หน้าแรก</a>
        <a href="book_room.php" class="nav-link active"><i class="bi bi-calendar-plus"></i> จองห้องเรียน</a>
        <a href="my_bookings.php" class="nav-link"><i class="bi bi-person-workspace"></i> ประวัติการจอง
    </a>
        
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <div class="px-3 mt-4 mb-2 small text-uppercase opacity-50">Admin Panel</div>
            <a href="admin_bookings.php" class="nav-link">
                <i class="bi bi-shield-check"></i> จัดการการจอง
                <?php if ($pendingCount > 0): ?><span class="badge bg-danger ms-auto rounded-pill"><?= $pendingCount ?></span><?php endif; ?>
            </a>
            <a href="manage_users.php" class="nav-link"><i class="bi bi-people"></i> จัดการผู้ใช้</a>
            <!-- <a href="http://127.0.0.1:5000" target="_blank" class="nav-link"><i class="bi bi-hdd-network"></i> จัดการโครงสร้างระบบ</a> -->
            <a href="sso.php" target="_blank" class="nav-link"><i class="bi bi-hdd-network"></i> จัดการโครงสร้างระบบ</a>
            <a href="run_sync.php" class="nav-link">
                <i class="bi bi-arrow-repeat"></i> ซิงค์อุปกรณ์
                <?php if ($newDevicesCount > 0): ?><span class="badge bg-light text-danger ms-auto rounded-pill"><?= $newDevicesCount ?></span><?php endif; ?>
            </a>
            <a href="force_sync.php" id="forceSyncBtn" class="nav-link">
                <i class="bi bi-arrow-repeat"></i> อัปเดตข้อมูลระบบ
            </a>
            <a href="device_dashboard.php" class="nav-link">
                <i class="bi bi-hdd-stack"></i> สถานะอุปกรณ์
            </a>
            
        <?php endif; ?>
        <a href="change_password.php" class="nav-link">
        <i class="bi bi-key"></i> เปลี่ยนรหัสผ่าน
        </a>
        <hr class="mx-3 mt-4 mb-2 opacity-25">
        <a href="logout.php" class="nav-link text-white-50 mt-auto mb-3">
            <i class="bi bi-box-arrow-right"></i> ออกจากระบบ
        </a>
    </div>
</nav>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded-4 desktop-header">
        <div>
            <h4 class="mb-0 fw-bold">จองห้องเรียน</h4>
            <small class="text-muted">กรอกข้อมูลเพื่อส่งคำขอจอง</small>
        </div>
        <div class="text-primary me-2">
            <i class="bi bi-person-circle fs-4"></i>
        </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-body p-4 p-md-5">
                    <form method="POST" id="bookingForm" novalidate>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">ชื่อผู้จอง</label>
                                <input type="text" name="first_name" class="form-control" placeholder="ระบุชื่อจริง" required value="<?= $_SESSION['first_name'] ?? '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">นามสกุล</label>
                                <input type="text" name="last_name" class="form-control" placeholder="ระบุนามสกุล" required value="<?= $_SESSION['last_name'] ?? '' ?>">
                            </div>
                        </div>
                        <div class="mb-4">
                                <label class="form-label">คำอธิบายโดยย่อ</label>
                                <input type="text" 
                                    name="short_description" 
                                    class="form-control" 
                                    placeholder="เช่น ใช้สอน/ประชุม/สัมมนา">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">รายละเอียดเพิ่มเติม (ถ้ามี)</label>
                            <textarea name="detail"
                                    class="form-control"
                                    rows="3"
                                    placeholder="เช่น ใช้สำหรับสอนLab"></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">เลือกห้องเรียน</label>
                            <select name="room_id" class="form-select" required>
                                <option value="">-- เลือกห้องที่ต้องการ --</option>
                                <?php foreach($all_rooms as $r): ?>
                                    <option value="<?= $r['room_id'] ?>"> <?= htmlspecialchars($r['room_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">วัน/เวลาเริ่มใช้งาน</label>
                                <input type="datetime-local" id="start_time" name="start_time" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">วัน/เวลาสิ้นสุด</label>
                                <input type="datetime-local" id="end_time" name="end_time" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">อุปกรณ์ภายในห้องเรียน</label>
                            <div id="equipment-container" class="p-4">
                                <div id="equipment-list" class="row text-start w-100">
                                    <div class="col-12 text-center py-2">
                                        <!--<i class="bi bi-info-circle text-muted me-2"></i>-->
                                        <span class="text-muted">กรุณาเลือกห้องเรียนก่อนเพื่อแสดงรายการอุปกรณ์</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                            <a href="index.php" class="btn btn-light rounded-pill px-4 text-secondary">
                                <i class="bi bi-arrow-left me-1"></i> ยกเลิก
                            </a>
                            <button type="submit" id="btnSubmit" class="btn btn-primary rounded-pill px-5 shadow">
                                <i class="bi bi-check2-circle me-1"></i> ส่งคำขอจองห้อง
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const roomsData = <?= json_encode($all_rooms) ?>;

    function toggleSidebar() {
        $('#sidebarMenu').toggleClass('show');
        $('#sidebarOverlay').toggleClass('show');
    }

    // โหลดอุปกรณ์ตามห้อง
    // --- แทนที่ฟังก์ชัน change ของ room_id เดิม (ประมาณบรรทัดที่ 261) ด้วยชุดนี้ ---
$('select[name=room_id]').change(function() {

    const roomId = $(this).val();
    const room = roomsData.find(r => r.room_id == roomId);
    let html = '';

    if (room && room.nodes.length > 0) {

        room.nodes.forEach(n => {

            // ซ่อนเฉพาะคอมพิวเตอร์นักศึกษา
            if (n.node_type === 'computer' && n.computer_role === 'student') {
                return;
            }

            // -------------------------
            // LED
            // -------------------------
            if (n.node_type === 'led' && n.channels && n.channels.length > 0) {

                html += `
                <div class="col-12 mb-3">
                    <div class="p-3 border rounded-3 bg-white" style="border: 1px solid #d1d3e2 !important;">
                        <div class="d-flex align-items-center mb-3">
                            <span class="fw-bold text-dark" style="font-size: 1.05rem;">
                                หลอดไฟ (เลือกที่ต้องการที่จะเปิด)
                            </span>
                        </div>
                        <div class="row g-2">`;

                n.channels.forEach(ch => {

                    html += `
                    <div class="col-md-6">
                        <div class="form-check p-3 border rounded-3 bg-white hover-shadow-sm transition-all"
                            style="cursor:pointer; border: 1px solid #e3e6f0 !important;"
                            onclick="$(this).find('input').click()">

                            <input class="form-check-input ms-0 me-2"
                                type="checkbox"
                                name="led_channels[${n.node_id}][]"
                                value="${ch.channel_id}"
                                id="ch_${ch.channel_id}"
                                onclick="event.stopPropagation()">

                            <label class="form-check-label fw-medium text-dark"
                                style="cursor:pointer;"
                                for="ch_${ch.channel_id}">
                                ${ch.name}
                            </label>

                        </div>
                    </div>`;

                });

                html += `
                        </div>
                    </div>
                </div>`;

            } 
            // -------------------------
            // อุปกรณ์ทั่วไป
            // -------------------------
            else {

                html += `
                <div class="col-md-6 mb-2">
                    <div class="form-check p-3 border rounded-3 bg-white hover-shadow-sm transition-all"
                         style="border: 1px solid #d1d3e2 !important;"
                         onclick="$(this).find('input').click()">

                        <input class="form-check-input ms-0 me-2 device-checkbox"
                            type="checkbox"
                            data-type="${n.node_type}"
                            name="node[]"
                            value="${n.node_id}"
                            id="node_${n.node_id}"
                            onclick="event.stopPropagation()">

                        <label class="form-check-label fw-medium text-dark"
                            style="cursor:pointer;"
                            for="node_${n.node_id}">
                            ${n.detail}
                        </label>

                    </div>
                </div>`;
            }

        });

    } 
    else if (roomId) {

        html = `
        <div class="col-12 text-center text-danger py-2">
            <i class="bi bi-exclamation-circle me-2"></i>
            ห้องนี้ยังไม่มีอุปกรณ์ลงทะเบียน
        </div>`;

    } 
    else {

        html = `
        <div class="col-12 text-center text-muted py-2">
            <i class="bi bi-info-circle me-2"></i>
            กรุณาเลือกห้องเรียนก่อนเพื่อแสดงรายการอุปกรณ์
        </div>`;

    }

    $('#equipment-list').fadeOut(100, function() {
        $(this).html(html).fadeIn(200);
    });

});
$('input[name="first_name"], input[name="last_name"]').on('keydown', function(e) {
    if (e.key === "Tab") {
        e.preventDefault();
    }
});
    $(document).ready(function() {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        const minDateTime = now.toISOString().slice(0, 16);
        $('#start_time').attr('min', minDateTime);

        $('#start_time, #end_time').on('change', function() {
            const startVal = $('#start_time').val();
            const endVal = $('#end_time').val();
            
            if (!startVal || !endVal) return;

            const startTime = new Date(startVal);
            const endTime = new Date(endVal);
            const nowCheck = new Date();

            if (startTime < (nowCheck - 60000)) {
                Swal.fire({ icon: 'warning', title: 'เวลาไม่ถูกต้อง', text: 'ไม่สามารถเลือกเวลาเริ่มต้นในอดีตได้' });
                $('#start_time').val('');
                return;
            }

            if (endTime <= startTime) {
                Swal.fire({ icon: 'error', title: 'ลำดับเวลาไม่ถูกต้อง', text: 'เวลาสิ้นสุดต้องมากกว่าเวลาเริ่มต้น' });
                let autoEnd = new Date(startTime);
                autoEnd.setHours(autoEnd.getHours() + 1);
                autoEnd.setMinutes(autoEnd.getMinutes() - autoEnd.getTimezoneOffset());
                $('#end_time').val(autoEnd.toISOString().slice(0, 16));
            }
        });
//         $(document).on('change', '.device-checkbox', function(e){

//     const type = $(this).data('type');

//     let computerChecked = $('.device-checkbox[data-type="computer"]:checked').length > 0;

//     if(type === 'audio_equipment' && !computerChecked){

//         e.preventDefault();
//         $(this).prop('checked', false);

//         Swal.fire({
//             icon: 'warning',
//             title: 'ลำดับการเลือกอุปกรณ์',
//             text: 'กรุณาเลือกคอมพิวเตอร์ก่อนแล้วจึงเลือกเครื่องเสียงเท่านั้น'
//         });

//     }

// });


//$(document).on('change', '.device-checkbox', function(e){

    //const type = $(this).data('type');

    //let computerChecked = $('.device-checkbox[data-type="computer"]:checked').length > 0;
    //let speakerChecked = $('.device-checkbox[data-type="audio_equipment"]:checked').length > 0;

    // ถ้าเลือกเครื่องเสียงแต่ไม่มีคอม
    //if(type === 'audio_equipment' && !computerChecked){

        //e.preventDefault();
        //$(this).prop('checked', false);

        //Swal.fire({
        //    icon: 'warning',
        //    title: 'ไม่สามารถเลือกได้',
        //    text: 'กรุณาเลือกคอมพิวเตอร์ด้วยจึงจะเลือกเครื่องเสียงได้'
        //});

    //}

//});

        <?php if($success): ?>
            Swal.fire({ icon: 'success', title: 'สำเร็จ', text: '<?= $success ?>' }).then(() => { window.location.href = 'my_bookings.php'; });
        <?php endif; ?>

        <?php if($error): ?>
            Swal.fire({ icon: 'error', title: 'ข้อผิดพลาด', text: '<?= $error ?>' });
        <?php endif; ?>

    $('#bookingForm').on('submit', function(e) {

    let start = $('#start_time').val();
    let end   = $('#end_time').val();

    if(start === '' || end === ''){
        e.preventDefault();

        Swal.fire({
            icon:'warning',
            title:'กรุณาเลือกเวลา',
            text:'ต้องเลือกเวลาเริ่มต้นและเวลาสิ้นสุดก่อนจอง'
        });

        return;
    }

    let startTime = new Date(start);
    let endTime   = new Date(end);

    if(endTime <= startTime){
        e.preventDefault();

        Swal.fire({
            icon:'error',
            title:'เวลาไม่ถูกต้อง',
            text:'เวลาสิ้นสุดต้องมากกว่าเวลาเริ่มต้น'
        });

        return;
    }

    let first = $('input[name="first_name"]').val().trim();
    let last  = $('input[name="last_name"]').val().trim();
    let shortDesc = $('input[name="short_description"]').val().trim();

    if (first === '' || last === '') {
        e.preventDefault();

        Swal.fire({
            icon: 'warning',
            title: 'ข้อมูลไม่ครบ',
            text: 'กรุณากรอกชื่อและนามสกุล'
        });

        return;
    }

    if (shortDesc === '') {
        e.preventDefault();

        Swal.fire({
            icon: 'warning',
            title: 'กรอกข้อมูล',
            text: 'กรุณากรอกคำอธิบายโดยย่อ'
        });

        return;
    }

    // ⭐ ตรวจว่ามีการเลือก "ประตู" หรือยัง
    // let doorChecked = $('.device-checkbox[data-type="door"]:checked').length;

    // if (doorChecked === 0) {
    //     e.preventDefault();

    //     Swal.fire({
    //         icon: 'warning',
    //         title: 'ต้องเลือกประตู',
    //         text: 'กรุณาเลือกประตูทุกครั้งก่อนทำการจอง'
    //     });

    //     return;
    // }

    // ตรวจอุปกรณ์รวม
    let nodes = $('input[name="node[]"]:checked').length;
    let leds  = $('input[name^="led_channels"]:checked').length;

    if (nodes === 0 && leds === 0) {
        e.preventDefault();

        Swal.fire({
            icon: 'warning',
            title: 'เลือกอุปกรณ์',
            text: 'กรุณาเลือกอุปกรณ์ที่ต้องใช้งานอย่างน้อย 1 รายการ'
        });

        return;
    }

});

});
</script>
</script>
</body>
</html>