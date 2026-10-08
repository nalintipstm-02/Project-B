<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
include 'config.php';

$user_id = $_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// --- Logic สำหรับยกเลิกการจอง ---
if (isset($_GET['cancel_id'])) {
    $cancel_id = $_GET['cancel_id'];
    try {
        // 1. ตรวจสอบข้อมูลก่อนยกเลิก
        $stmtCheck = $conn->prepare("SELECT status FROM bookings WHERE id = ? AND user_id = ?");
        $stmtCheck->execute([$cancel_id, $user_id]);
        $booking = $stmtCheck->fetch();

        if ($booking) {
            // อนุญาตให้ยกเลิกได้ถ้าสถานะเป็น pending หรือค่าว่าง (กรณีที่ enum ยังไม่รองรับ)
            // หรือตรวจสอบว่าไม่ใช่รายการที่ถูกอนุมัติหรือปฏิเสธไปแล้ว
            if ($booking['status'] == 'pending' || empty($booking['status'])) {
                
                $newStatus = 'cancel'; // ค่าที่จะบันทึกลงไป
                $stmtUpdate = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
                $result = $stmtUpdate->execute([$newStatus, $cancel_id]);

                if ($result) {
                    // ตรวจสอบว่าหลังจาก UPDATE แล้ว ค่าเป็น 'cancel' จริงหรือไม่
                    // ถ้ายังเป็นค่าว่าง แสดงว่า ENUM ในฐานข้อมูลยังไม่ได้เพิ่มคำว่า 'cancel'
                    $stmtVerify = $conn->prepare("SELECT status FROM bookings WHERE id = ?");
                    $stmtVerify->execute([$cancel_id]);
                    $verifiedStatus = $stmtVerify->fetchColumn();

                    if ($verifiedStatus === 'cancel') {
                        $success_msg = "ยกเลิกการจองเรียบร้อยแล้ว";
                    } else {
                        $error_msg = "ไม่สามารถบันทึกสถานะ 'cancel' ได้ (กรุณาเพิ่ม 'cancel' ใน ENUM ของฐานข้อมูล)";
                    }
                } else {
                    $error_msg = "เกิดข้อผิดพลาดในการอัปเดตฐานข้อมูล";
                }
            } else {
                $error_msg = "รายการนี้ถูกดำเนินการไปแล้ว (สถานะ: " . $booking['status'] . ")";
            }
        } else {
            $error_msg = "ไม่พบข้อมูลการจอง";
        }
    } catch (Exception $e) {
        $error_msg = "Error: " . $e->getMessage();
    }
}

// --- Logic สำหรับ Sidebar ---
$newDevicesCount = 0;
$pendingCount = 0;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $stmtSync = $conn->query("SELECT COUNT(*) FROM project.node WHERE node_id NOT IN (SELECT node_id FROM node)");
    $newDevicesCount = $stmtSync->fetchColumn();

    // นับเฉพาะรายการที่สถานะเป็น 'pending' เท่านั้น
    $stmtP = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
    $pendingCount = $stmtP->fetchColumn();
}

// --- ดึงข้อมูลการจอง ---

$stmt = $conn->prepare("
    SELECT 
        b.*, 
        r.room_name,
        u.full_name
    FROM bookings b
    JOIN room r ON b.room_id = r.room_id
    JOIN users u ON b.user_id = u.id
    WHERE b.user_id = ?
    ORDER BY b.id DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการจองของฉัน - Classroom System</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/datatables.mark.js@2.1.0/dist/datatables.mark.min.css">

    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            --sidebar-width: 260px;
        }

        body {
            background-color: #f8f9fc;
            font-family: 'Inter', 'Sarabun', sans-serif;
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
            border-radius: 1rem;
        }

        .table thead th {
            background-color: #f8f9fc;
            text-transform: uppercase;
            font-size: 0.8rem;
            font-weight: 700;
            color: #4e73df;
            padding: 1.2rem 1rem;
            border-top: none;
        }

        .status-badge {
            padding: 0.5rem 0.8rem;
            border-radius: 50rem;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            min-width: 100px;
            justify-content: center;
        }

        .btn-cancel {
            color: #e74a3b;
            background: #fff;
            border: 1px solid #e74a3b;
            transition: all 0.2s;
            font-size: 0.85rem;
        }
        .btn-cancel:hover {
            background: #e74a3b;
            color: #fff;
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

        /* จัดคอลัมน์ห้องเรียนให้ตรงกัน */
    .room-text {
        font-weight: 700;
        color: #2c3e50;
        display: inline-block;
    }

    /* จัดช่วงเวลาให้โปร่ง ไม่มีกรอบ และไม่ซ้อนกัน */
    .time-display {
        font-family: 'Inter', sans-serif;
        font-weight: 600;
        color: #4a5568;
        white-space: nowrap; /* ป้องกันเวลาขึ้นบรรทัดใหม่ */
        letter-spacing: 0.3px;
    }

    /* ปรับแต่ง Tag อุปกรณ์ให้ดูสะอาดตา */
    .device-tag {
        background-color: transparent !important;
        color: #4e73df !important;
        border: 1px solid #e3e6f0 !important;
        font-weight: 400 !important;
        box-shadow: none !important;
        font-size: 0.7rem !important;
        padding: 4px 10px !important;
    }
    .description-text{
    max-width: 220px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

    /* เพิ่มระยะห่างในตาราง */
    .table td {
        padding: 1.25rem 0.75rem !important;
        vertical-align: middle;
    }

        @media (max-width: 992px) {
            .sidebar { margin-left: calc(-1 * var(--sidebar-width)); }
            .main-content { margin-left: 0; }
        }

        /* ตั้งค่าสีไฮไลท์ */
    mark {
        background: #ffeb3b;
        color: black;
        padding: 2px;
        border-radius: 4px;
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
/* --- การไฮไลท์คำค้นหา (Mark.js) --- */
mark {
    background: #fff3cd !important; /* สีเหลืองอ่อนดูนวลตา */
    color: #856404 !important;
    padding: 0.1em 0.2em !important;
    border-radius: 4px !important;
    font-weight: 600 !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05);
}

/* --- ปรับแต่ง Dropdown และ Search ให้มนแบบ Capsule --- */
.dataTables_length select.form-select {
    border-radius: 50rem !important; /* มนระดับ Capsule */
    padding-left: 1rem !important;
    padding-right: 2rem !important;
    border: 1px solid #d1d3e2 !important;
    background-color: #fff !important;
}

.dataTables_filter input {
    border-radius: 50rem !important; /* มนระดับ Capsule */
    padding: 6px 20px !important;
    border: 1px solid #d1d3e2 !important;
    background-color: #f8f9fc !important;
    width: 250px !important;
}

/* --- เพิ่ม Padding รอบตารางเพื่อให้ไม่ชิดขอบ Card --- */
.dataTables_wrapper .row:first-child {
    padding: 1.5rem 1.5rem 0.5rem 1.5rem !important;
}

.dataTables_wrapper .row:last-child {
    padding: 1rem 1.5rem 1.5rem 1.5rem !important;
}

/* เส้นคั่นระหว่างหัวตารางกับเนื้อหา */
#bookingTable thead th {
    border-bottom: 2px solid #f2f2f2 !important;
}
mark {
    background: #ffeb3b !important;
    color: black !important;
}
    </style>
</head>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/mark.js@8.11.1/dist/jquery.mark.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

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
        <a href="my_bookings.php" class="nav-link active"><i class="bi bi-person-workspace"></i> ประวัติการจอง</a>
        
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <div class="px-3 mt-4 mb-2 small text-uppercase opacity-50">Admin Panel</div>
            <a href="admin_bookings.php" class="nav-link">
                <i class="bi bi-shield-check"></i> จัดการการจอง
                <?php if ($pendingCount > 0): ?>
                    
                    <span class="badge bg-danger ms-auto"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
            <a href="manage_users.php" class="nav-link"><i class="bi bi-people"></i> จัดการผู้ใช้</a>
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

<div class="main-content">
    
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded-4">
        <div>
            <h4 class="mb-0 fw-bold">รายการจองของฉัน</h4>
            <small class="text-muted">ตรวจสอบสถานะและรายละเอียดการจองของคุณ</small>
        </div>
        <div class="text-primary me-2">
            <i class="bi bi-person-circle fs-4"></i>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card border-0 shadow-sm overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="bookingTable" class="table table-hover align-middle mb-0">

                    <thead>
                        <tr>
                            <th class="ps-4">ผู้จอง</th>
                            <th>ห้องเรียน</th>
                            <th>วัน/เวลา</th>
                            <th>สถานะการจอง</th>
                            <th>คำอธิบาย</th>
                            <th>รายละเอียด</th>
                            <th>อุปกรณ์ที่ใช้</th>
                            <th class="text-center pe-4">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($bookings)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x d-block mb-2" style="font-size: 2.5rem; opacity: 0.3;"></i>
                                    คุณยังไม่มีประวัติการจองในระบบ
                                </td>
                            </tr>
                        <?php endif; ?>


                        <?php foreach($bookings as $b): ?>
                        <tr>

                            <!-- ผู้จอง -->
                            <td class="ps-4">
                                <span class="small text-dark">
                                    <?= htmlspecialchars($b['full_name']) ?>
                                </span>
                            </td>

                            <!-- ห้องเรียน -->
                            <td>
                                <span class="room-text">
                                    <?= htmlspecialchars($b['room_name']) ?>
                                </span>
                            </td>

                            <!-- วัน/เวลา -->
                            <td>
                                <div class="small fw-bold text-dark">
                                    <?= date('d/m/Y', strtotime($b['booking_date'])) ?>
                                </div>

                                <div class="small text-muted">
                                    <?= date('H:i', strtotime($b['start_time'])) ?>
                                    -
                                    <?= date('H:i', strtotime($b['end_time'])) ?>
                                </div>
                            </td>  
                            <td>
                          <?php
                            $current_status = $b['status'];
                            if($current_status == 'pending' || empty($current_status)) {
                               echo '<span class="status-badge bg-warning-subtle text-warning"><i class="bi bi-clock me-1"></i>รอดำเนินการ</span>';
                            } elseif($current_status == 'approved') {
                               echo '<span class="status-badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>อนุมัติแล้ว</span>';
                            } elseif($current_status == 'cancel') {
                               echo '<span class="status-badge bg-secondary-subtle text-secondary"><i class="bi bi-dash-circle me-1"></i>ยกเลิกแล้ว</span>';
                            } else {
                               echo '<span class="status-badge bg-danger-subtle text-danger"><i class="bi bi-x-circle me-1"></i>ปฏิเสธ</span>';
                            }
                        ?>
                            </td>
                            <td>
                            <?php if(!empty($b['short_description'])): ?>
                            <span class="text-secondary small">
                            <?= htmlspecialchars($b['short_description']) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted small">-</span>
                            <?php endif; ?>
                            </td>

                            <td>
                            <?php if(!empty($b['detail'])): ?>
                            <span class="text-secondary small">
                            <?= htmlspecialchars($b['detail']) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted small">-</span>
                            <?php endif; ?>
                            </td>
                    
                            <td>
                        <?php
    // 1. ดึงอุปกรณ์ทั่วไป (Air, Computer, etc.)
                        $stmt2 = $conn->prepare("
                            SELECT n.detail 
                            FROM booking_equipments be
                            JOIN node n ON be.node_id = n.node_id
                            WHERE be.booking_id = ?
                        ");
                        $stmt2->execute([$b['id']]); 
                        $eqs = $stmt2->fetchAll(PDO::FETCH_COLUMN);

    // 2. ดึงชื่อ LED Channels จากฐานข้อมูล project
                        $stmtLed = $conn->prepare("
                            SELECT dc.name 
                            FROM project.booking_led_channel blc
                            JOIN project.device_channel dc ON blc.channel_id = dc.id
                            WHERE blc.booking_id = ?
                        ");
                        $stmtLed->execute([$b['id']]);
                        $leds = $stmtLed->fetchAll(PDO::FETCH_COLUMN);

    // --- การแสดงผลแบบบล็อกเดียวกันทั้งหมด ---
                        if(empty($eqs) && empty($leds)) {
                            echo '<span class="text-muted small italic">ไม่มีอุปกรณ์</span>';
                        } else {
        // แสดงอุปกรณ์ทั่วไป
                            foreach($eqs as $e) {
                                echo '<span class="badge device-tag me-1 mb-1">'.htmlspecialchars($e).'</span>';
                            }
        // แสดง LED Channels (ปรับให้เหมือนอุปกรณ์ปกติ: ไม่มีไอคอน และใช้ Class เดียวกัน)
                            foreach($leds as $l) {
                                echo '<span class="badge device-tag me-1 mb-1">'.htmlspecialchars($l).'</span>';
                            }
                        }
                        ?>
                        </td>
    
                        <td class="text-center pe-4">
                            <?php if($b['status'] == 'pending' || empty($b['status'])): ?>
                                    <button class="btn btn-sm btn-cancel rounded-pill px-3" onclick="confirmCancel(<?= $b['id'] ?>)">
                                    ยกเลิก
                                    </button>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
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
function confirmCancel(bookingId) {
    Swal.fire({
        title: 'ยืนยันการยกเลิก',
        text: "คุณต้องการยกเลิกรายการจองห้องเรียนนี้ใช่หรือไม่",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74a3b',
        cancelButtonColor: '#6e707e',
        confirmButtonText: 'ยกเลิกการจอง',
        cancelButtonText: 'ปิดหน้าต่าง'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'my_bookings.php?cancel_id=' + bookingId;
        }
    })
}

$(document).ready(function() {
    // DataTable Configuration
    $('#bookingTable').DataTable({
        mark: true, // เปิดใช้งานการไฮไลท์
        pageLength: 25,
        order: [[2, "desc"]], // เรียงตามวันที่ (Column ที่ 1)
        lengthMenu: [10, 25, 50, 100],
        // จัด Layout: sSearch อยู่ขวา, lLength อยู่ซ้าย
        dom: "<'row px-3'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
             "<'row'<'col-sm-12'tr>>" +
             "<'row px-3'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
        language: {
            search: "",
            searchPlaceholder: "ค้นหาประวัติการจอง",
            lengthMenu: "แสดง _MENU_ รายการ",
            info: "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
            infoFiltered: "(จากทั้งหมด _MAX_ รายการ)",
            paginate: {
                next: '<i class="bi bi-chevron-right"></i>',
                previous: '<i class="bi bi-chevron-left"></i>'
            }
        },
        // ปรับแต่งคลาสของ Select เมื่อตารางวาดเสร็จ
        fnDrawCallback: function() {
            $('.dataTables_length select').addClass('form-select form-select-sm');
        }
    });

    // ส่วนของ SweetAlert เดิมของคุณ...
    <?php if($success_msg): ?>
    Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: '<?= $success_msg ?>', confirmButtonColor: '#4e73df' });
    <?php endif; ?>

    <?php if($error_msg): ?>
    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: '<?= $error_msg ?>', confirmButtonColor: '#4e73df' });
    <?php endif; ?>
});
</script>
</body>
</html>
