<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
include 'config.php';
include 'sync_mqtt.php';

// --- เชื่อมต่อฐานข้อมูล project (โครงสร้างเดิมห้ามเปลี่ยน) ---
try {
    $conn_project = new PDO("mysql:host=localhost;dbname=project;charset=utf8", "root", "");
    $conn_project->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("เชื่อมต่อฐานข้อมูล project ล้มเหลว: " . $e->getMessage());
}

// --- ส่วนการนับแจ้งเตือนสำหรับ Sidebar ---
$newDevicesCount = 0;
$pendingCount = 0;
try {
    $stmtSync = $conn->query("SELECT COUNT(*) FROM project.node WHERE node_id NOT IN (SELECT node_id FROM node)");
    $newDevicesCount = (int)$stmtSync->fetchColumn();

    // นับเฉพาะรายการที่เป็น pending จริงๆ (ไม่นับรวมค่าว่างที่อาจเกิดจากการยกเลิกที่ผิดพลาด หรือสถานะ cancel)
    $stmtP = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
    $pendingCount = (int)$stmtP->fetchColumn();
} catch (Exception $e) {}

/* ===============================
   Approve / Reject Logic (โครงสร้างเดิมห้ามเปลี่ยน)
   =============================== */
if (isset($_GET['action'], $_GET['id'])) {
    $bookingId = (int)$_GET['id'];
    if ($_GET['action'] === 'approve') {
    try {

        if (!$conn->inTransaction()) {
            $conn->beginTransaction();
        }

        if (!$conn_project->inTransaction()) {
            $conn_project->beginTransaction();
        }
            
            $conn->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?")->execute([$bookingId]);
            
            $stmt = $conn->prepare("SELECT room_id, booking_date, start_time, end_time FROM bookings WHERE id = ?");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($booking) {
                $insert = $conn_project->prepare("
INSERT INTO bookings
(id, room_id, booking_date, start_time, end_time, status)
VALUES (?, ?, ?, ?, ?, 'approved')
ON DUPLICATE KEY UPDATE
status='approved',
room_id=VALUES(room_id),
booking_date=VALUES(booking_date),
start_time=VALUES(start_time),
end_time=VALUES(end_time)
");

$insert->execute([
    $bookingId,
    $booking['room_id'],
    $booking['booking_date'],
    $booking['start_time'],
    $booking['end_time']
]);

$projectBookingId = $bookingId;           
                $nodes = $conn->prepare("SELECT node_id FROM booking_equipments WHERE booking_id = ?");
                $nodes->execute([$bookingId]);
                $nodeList = $nodes->fetchAll(PDO::FETCH_COLUMN);
                
                if ($nodeList && $projectBookingId) {
                    $insertMap = $conn_project->prepare("INSERT IGNORE INTO cluster_booking_node_map (booking_id, node_id) VALUES (?, ?)");
                    foreach ($nodeList as $nodeId) {
                        $insertMap->execute([$projectBookingId, $nodeId]);
                    }
                }
            }
            $conn->commit();
            $conn_project->commit();
            publishSyncEvent("booking_bundle");
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }

            if ($conn_project->inTransaction()) {
                $conn_project->rollBack();
            }
            die("เกิดข้อผิดพลาดในการอนุมัติ: " . $e->getMessage());
        }
    } elseif ($_GET['action'] === 'reject') {
        $conn->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?")->execute([$bookingId]);
    }
    elseif ($_GET['action'] === 'cancel') {
    try {
        if (!$conn->inTransaction()) {
            $conn->beginTransaction();
        }

        if (!$conn_project->inTransaction()) {
            $conn_project->beginTransaction();
        }

        // 1️⃣ booking_system → cancel_by_admin
        $conn->prepare("
            UPDATE bookings 
            SET status = 'cancel_by_admin' 
            WHERE id = ? AND status = 'approved'
        ")->execute([$bookingId]);

        // 2️⃣ project.bookings → cancel_by_admin
        $conn_project->prepare("
            UPDATE bookings 
            SET status = 'cancel_by_admin' 
            WHERE id = ?
        ")->execute([$bookingId]);

        // 3️⃣ ลบ mapping
        $conn_project->prepare("
            DELETE FROM cluster_booking_node_map 
            WHERE booking_id = ?
        ")->execute([$bookingId]);

        $conn->commit();
        $conn_project->commit();
        publishSyncEvent("booking_bundle");

    } catch (Exception $e) {
        $conn->rollBack();
        $conn_project->rollBack();
        die("เกิดข้อผิดพลาดในการยกเลิก: " . $e->getMessage());
    }
}
    header('Location: admin_bookings.php');
    exit();
}

$bookings = $conn->query("
    SELECT 
        b.id,
        u.full_name,
        r.room_name,
        b.booking_date,
        b.start_time,
        b.end_time,
        b.status,
        b.short_description,
        b.detail
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN room r ON b.room_id = r.room_id
    ORDER BY b.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการการจอง - Admin Panel</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.mark.js@2.1.0/dist/datatables.mark.min.css">
    <style>
        :root { 
            --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%); 
            --sidebar-width: 260px; 
        }
        body { background-color: #f8f9fc; font-family: 'Inter', 'Sarabun', sans-serif; }
        
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
        .sidebar .nav-link i { 
            margin-right: 10px; 
            font-size: 1.1rem; 
        }
        
        .sidebar-heading {
            padding: 1rem 1.5rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.4);
            letter-spacing: 0.1rem;
        }

        .main-content { margin-left: var(--sidebar-width); padding: 2rem; min-height: 100vh; }
        .card { border: none; box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1); border-radius: 1rem; }
        .table thead th { background-color: #f8f9fc; color: #4e73df; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; border-top: none; padding: 1rem; }
        .status-badge { padding: 0.5rem 0.8rem; border-radius: 50rem; font-size: 0.75rem; font-weight: 600; min-width: 100px; display: inline-flex; justify-content: center; align-items: center; }
        
        .btn-action-label {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
            border: none;
            gap: 6px;
        }
        .btn-approve-label { background-color: #e6f9f0; color: #1cc88a; }
        .btn-approve-label:hover { background-color: #1cc88a; color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(28, 200, 138, 0.2); }
        .btn-reject-label { background-color: #fff0f0; color: #e74a3b; }
        .btn-reject-label:hover { background-color: #e74a3b; color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(231, 74, 59, 0.2); }

        .room-column {
        display: inline-block;
        width: 85px;            /* กำหนดความกว้างให้คงที่ ตัวหนังสือจะตรงกันเป๊ะ */
        font-weight: 600;       /* ทำตัวหนาขึ้นเล็กน้อย */
        color: #4e73df;         /* ใช้สีน้ำเงินคลาสสิกของระบบคุณ */
        text-align: left;       /* จัดชิดซ้าย */
    }

        /* ปรับแต่ง Table Row ให้ดูโปร่งและพรีเมียมขึ้น */
        .table td {
        padding: 1.1rem 1rem !important;
        vertical-align: middle;
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

        @media (max-width: 992px) { .sidebar { margin-left: calc(-1 * var(--sidebar-width)); } .main-content { margin-left: 0; } }

        .detail-text{
        max-width:200px;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
        }

        .dataTables_filter{
        margin-right:10px;
        }

        .dataTables_length{
        margin-left:10px;
        }
        
        /* ตกแต่งส่วนหัวของ Table (Search & Length) */
.dataTables_wrapper .dataTables_length, 
.dataTables_wrapper .dataTables_filter {
    padding: 1.5rem 1rem; /* เพิ่มพื้นที่ว่าง */
}

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid #e3e6f0;
    border-radius: 10px;
    padding: 6px 12px;
    margin-left: 10px;
    background-color: #f8f9fc;
    transition: all 0.2s;
}

.dataTables_wrapper .dataTables_filter input:focus {
    outline: none;
    border-color: #4e73df;
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.1);
    background-color: #fff;
}

.dataTables_wrapper .dataTables_length select {
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    padding: 4px 8px;
}

/* ตกแต่งส่วนท้ายของ Table (Info & Pagination) */
.dataTables_wrapper .dataTables_info {
    padding: 1.5rem 1rem;
    font-size: 0.85rem;
    color: #858796;
}

.dataTables_wrapper .dataTables_paginate {
    padding: 1.5rem 1rem;
}

.pagination {
    gap: 5px;
}

.page-item.active .page-link {
    background: var(--primary-gradient);
    border-color: transparent;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(78, 115, 223, 0.2);
}

.page-link {
    border-radius: 8px !important;
    border: none;
    color: #4e73df;
    padding: 8px 14px;
    font-weight: 600;
}

.page-link:hover {
    background-color: #f1f3f9;
}

/* แก้ไขกรอบตัวเลือกจำนวนแถวซ้อนกัน */
.dataTables_length select.form-select {
    display: inline-block;
    width: auto;
    min-width: 70px;
    padding: 0.375rem 2rem 0.375rem 0.75rem; /* เว้นระยะให้ลูกศร */
    margin: 0 8px;
    border-radius: 8px;
    border: 1px solid #d1d3e2;
    cursor: pointer;
    background-position: right 0.5rem center; /* ขยับลูกศร dropdown */
}

/* จัดระยะห่างของ Search และ Length ให้ดูพรีเมียม */
.dataTables_wrapper .dataTables_length, 
.dataTables_wrapper .dataTables_filter {
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    padding: 0 1.25rem;
}

/* ปรับแต่งช่อง Search ให้ดูทันสมัย */
.dataTables_filter input {
    border: 1px solid #d1d3e2 !important;
    border-radius: 20px !important; /* ทำให้ขอบมนแบบ Google Search */
    padding: 5px 15px !important;
    background-color: #f8f9fc;
    transition: all 0.2s;
}

.dataTables_filter input:focus {
    box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.15) !important;
    background-color: #fff;
    outline: none;
}

/* ตกแต่งส่วน Pagination ด้านล่าง */
.dataTables_info {
    font-size: 0.85rem;
    color: #858796;
    padding-left: 1.25rem !important;
}

.pagination {
    padding-right: 1.25rem;
}

.page-link {
    border: none !important;
    color: #4e73df;
    margin: 0 2px;
    border-radius: 6px !important;
}

.page-item.active .page-link {
    background: var(--primary-gradient) !important;
    color: white !important;
    box-shadow: 0 3px 6px rgba(78, 115, 223, 0.2);
}

/* ปรับแต่งสีไฮไลท์ตามใจชอบ */
    mark {
        background: #ffeb3b; /* สีเหลืองสว่าง */
        color: black;
        padding: 2px;
        border-radius: 4px;
        font-weight: bold;
    }
    </style>
</head>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/mark.js@8.11.1/dist/jquery.mark.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/datatables.mark.js@2.1.0/dist/datatables.mark.min.js"></script>
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
        
        <a href="admin_bookings.php" class="nav-link active">
            <i class="bi bi-shield-check"></i> จัดการการจอง
            <?php if ($pendingCount > 0): ?>
                <span class="badge bg-danger ms-auto rounded-pill"><?= $pendingCount ?></span>
            <?php endif; ?>
        </a>
        
        <a href="manage_users.php" class="nav-link">
            <i class="bi bi-people"></i> จัดการผู้ใช้
        </a>

        <a href="http://127.0.0.1:5000" target="_blank" class="nav-link">
            <i class="bi bi-hdd-network"></i> จัดการโครงสร้างระบบ
        </a>
        
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
            <h4 class="mb-0 fw-bold">จัดการการจอง</h4>
            <small class="text-muted">ตรวจสอบสถานะการจองห้องเรียน</small>
        </div>
        <div class="text-primary me-2">
            <i class="bi bi-person-circle fs-4"></i>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="bookingTable" class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>ผู้จอง</th>
                            <th>ห้องเรียน</th>
                            <th>วัน/เวลา</th>
                            <th>คำอธิบาย</th>
                            <th>รายละเอียด</th>
                            <th>สถานะ</th>
                            <th class="text-center">การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($bookings as $b): ?>
                        <tr>
                            <td class="ps-4 text-muted small"><?= $b['id'] ?></td>
                            <td class="small text-dark"><?= htmlspecialchars($b['full_name']) ?></td>
                            <td><span class="badge bg-primary-subtle text-primary px-3 py-2"><?= htmlspecialchars($b['room_name']) ?></span></td>
                            <td>
                                <div class="small fw-bold text-dark"><?= date('d/m/Y', strtotime($b['booking_date'])) ?></div>
                                <div class="small text-muted"><?= date('H:i', strtotime($b['start_time'])) ?> - <?= date('H:i', strtotime($b['end_time'])) ?></div>
                            </td>
                            <td>
                            <?php if(!empty($b['short_description'])): ?>
                            <span class="small text-dark"><?= htmlspecialchars($b['short_description']) ?></span>
                            <?php else: ?>
                            <span class="text-muted small">-</span>
                            <?php endif; ?>
                            </td>

                            <td>
                            <?php if(!empty($b['detail'])): ?>
                            <span class="detail-text small" title="<?= htmlspecialchars($b['detail']) ?>">
                            <?= htmlspecialchars($b['detail']) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted small">-</span>
                            <?php endif; ?>
                            </td>

                            <td>
                            <?php
                            $status = $b['status'];
                            if($status === 'pending' || empty($status)) 
                               echo '<span class="status-badge bg-warning-subtle text-warning border border-warning-subtle"><i class="bi bi-clock me-1"></i>รออนุมัติ</span>';
                            elseif($status === 'approved') 
                               echo '<span class="status-badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>อนุมัติแล้ว</span>';
                            elseif($status === 'cancel') 
                               echo '<span class="status-badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="bi bi-dash-circle me-1"></i>ยกเลิกแล้ว</span>';
                            else 
                               echo '<span class="status-badge bg-danger-subtle text-danger border border-danger-subtle"><i class="bi bi-x-circle me-1"></i>ปฏิเสธ</span>';
                            ?>
                            </td>

                            <td class="text-center">
                            <?php if($b['status'] === 'pending' || empty($b['status'])): ?>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="?action=approve&id=<?= $b['id'] ?>" class="btn-action-label btn-approve-label">
                                    <i class="bi bi-check2-circle"></i> อนุมัติ
                                </a>
                                <a href="?action=reject&id=<?= $b['id'] ?>" class="btn-action-label btn-reject-label"
                                    onclick="return confirm('ยืนยันการปฏิเสธการจองนี้?')">
                                    <i class="bi bi-x-circle"></i> ปฏิเสธ
                                </a>
                            </div>

                            <?php elseif($b['status'] === 'approved'): ?>
                                <a href="?action=cancel&id=<?= $b['id'] ?>"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('ยืนยันการยกเลิกการจองนี้')">
                                <i class="bi bi-trash"></i> ยกเลิกรายการ
                                </a>

                            <?php elseif($b['status'] === 'cancel'): ?>
                            <span class="text-secondary small">ยกเลิกแล้ว</span>

                            <?php else: ?>
                            <span class="text-muted small">ดำเนินการแล้ว</span>
                            <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
$('#bookingTable').DataTable({
    mark: true, // <--- เพิ่มบรรทัดนี้เพื่อเปิดใช้งานการไฮไลท์
    pageLength: 25,
    order: [[0, "desc"]],
    lengthMenu: [10, 25, 50, 100],
    dom: "<'row px-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
         "<'row'<'col-sm-12'tr>>" +
         "<'row px-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    language: {
        search: "", 
        searchPlaceholder: "ค้นหาข้อมูลการจอง",
        lengthMenu: "แสดง _MENU_ รายการ",
        info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
        paginate: {
            next: '<i class="bi bi-chevron-right"></i>',
            previous: '<i class="bi bi-chevron-left"></i>'
        }
    }
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>