<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
include 'config.php';

// --- Logic ส่วนการนับแจ้งเตือนสำหรับ Sidebar (ห้ามเปลี่ยนโครงสร้างเดิม) ---
$newDevicesCount = 0;
$pendingCount = 0;
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $stmtSync = $conn->query("SELECT COUNT(*) FROM project.node WHERE node_id NOT IN (SELECT node_id FROM node)");
    $newDevicesCount = $stmtSync->fetchColumn();

    $stmtP = $conn->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'");
    $pendingCount = $stmtP->fetchColumn();
}

$rooms = $conn->query("SELECT room_id, room_name FROM room ORDER BY room_name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าแรก - Classroom System</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
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
            top: 0;
            left: 0;
            right: 0;
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

        .mobile-nav-toggle:active {
            transform: scale(0.9);
            background: #eef1f7;
        }

        .mobile-logo-text {
            font-weight: 800;
            color: var(--primary-color);
            margin: 0;
            font-size: 1.1rem;
            letter-spacing: -0.5px;
        }

        /* Sidebar Overlay */
        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(0,0,0,0.4);
            z-index: 1050;
            backdrop-filter: blur(4px);
            transition: opacity 0.3s;
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

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e3e6f0;
            font-weight: bold;
            color: #4e73df;
            padding: 1.25rem;
        }

        #calendar {
            padding: 1rem;
            background: white;
            border-bottom-left-radius: 1rem;
            border-bottom-right-radius: 1rem;
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

        /* Keep Original Calendar & Event Styles */
        .fc { --fc-border-color: #edf0f7; }
        .fc-theme-standard td, .fc-theme-standard th { border: 1px solid #f0f2f8; }
        .fc .fc-toolbar-title { font-size: 1.3rem; font-weight: 700; color: #4e73df; }
        
        .custom-event {
            padding: 4px 8px;
            border-radius: 6px;
            border-left: 4px solid #224abe;
            background-color: #f0f4ff;
            color: #224abe;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            cursor: pointer;
        }
        
        .event-time { font-size: 0.75rem; font-weight: 700; margin-bottom: 2px; opacity: 0.8; display: flex; align-items: center; }
        .event-title-text { font-size: 0.85rem; font-weight: 700; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .event-user { font-size: 0.75rem; display: flex; align-items: center; opacity: 0.9; }

        /* --- RESPONSIVE MEDIA QUERIES --- */
        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.show { transform: translateX(0); }
            .sidebar-overlay.show { display: block; }
            .mobile-header-bar { display: flex; }
            .main-content { margin-left: 0; padding: 1rem; padding-top: 5rem; }
            .desktop-header { display: none !important; }
        }

        @media (max-width: 576px) {
            .fc .fc-toolbar { flex-direction: column; gap: 10px; }
            .fc .fc-toolbar-title { font-size: 1.1rem; }
            .card-header { font-size: 0.9rem; padding: 1rem; }
            #calendar { padding: 0.5rem; }
        }
    </style>
</head>
<body>

<!-- Overlay สำหรับมือถือ -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- ส่วนหัวสำหรับมือถือ (Mobile Header) -->
<div class="mobile-header-bar">
    <h2 class="mobile-logo-text">Classroom System</h2>
    <button class="mobile-nav-toggle" id="menuToggle" onclick="toggleSidebar()">
        <!-- เปลี่ยนจาก bi-list-nested เป็น bi-list เพื่อให้ขีดเท่ากัน ไม่เอียง -->
        <i class="bi bi-list fs-3"></i>
    </button>
</div>

<nav class="sidebar shadow" id="sidebarMenu">
    <div class="p-3 text-center position-relative">
        <h5 class="fw-bold mb-0">Classroom System</h5>
        <!-- ปุ่มปิดสำหรับมือถือ -->
        <button class="btn btn-sm text-white d-lg-none position-absolute top-50 end-0 translate-middle-y me-2" onclick="toggleSidebar()">
            <i class="bi bi-chevron-left"></i>
        </button>
        <hr class="mt-3 mb-2 opacity-25">
    </div>
    <div class="nav flex-column mt-3">
        <a href="index.php" class="nav-link active"><i class="bi bi-speedometer2"></i> หน้าแรก</a>
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
            <a href="run_sync.php" id="syncBtn" class="nav-link">
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
    <!-- Desktop Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 bg-white p-3 shadow-sm rounded-4 desktop-header">
        <div>
            <h4 class="mb-0 fw-bold">ตารางการใช้ห้องเรียน</h4>
            <small class="text-muted">ยินดีต้อนรับ <?= htmlspecialchars($_SESSION['username']) ?></small>
        </div>
        
    </div>

    <!-- Filter Card -->
    <div class="card mb-4">
        <div class="card-body py-4">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-auto">
                    <span class="fw-bold text-dark"></i>เลือกห้องเรียน</span>
                </div>
                <div class="col-12 col-md-5">
                    <select id="roomFilter" class="form-select border-primary-subtle shadow-sm rounded-pill py-2">
                        <option value="">-- กรุณาเลือกห้องเรียน --</option>
                        <?php foreach ($rooms as $r): ?>
                            <option value="<?= $r['room_id'] ?>"> <?= htmlspecialchars($r['room_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendar Container -->
    <div id="calendarContainer" class="card d-none">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span id="calendarTitle" class="text-truncate">ตารางเวลาการใช้งาน</span>
            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3">
                <i class="bi bi-check-circle-fill me-1"></i> อนุมัติแล้ว
            </span>
        </div>
        <div class="card-body p-0">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Help Box -->
    <div id="helperBox" class="card py-5 border-dashed bg-white text-center" style="border: 2px dashed #d1d3e2 !important;">
        <div class="card-body">
            <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 80px; height: 80px;">
                <i class="bi bi-search text-primary fs-2"></i>
            </div>
            <h5 class="fw-bold text-dark">ยังไม่มีข้อมูลแสดง</h5>
            <p class="text-muted mx-auto" style="max-width: 500px;">โปรดเลือกห้องเรียนจากเมนูด้านบนเพื่อดูรายละเอียดตารางเวลา</p>
        </div>
    </div>
</div>

<!-- Modal รายละเอียด (ห้ามปรับโครงสร้าง) -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem;">
            <div class="modal-header bg-light py-3" style="border-radius: 1rem 1rem 0 0;">
                <h6 class="modal-title fw-bold text-primary">รายละเอียดการเข้าใช้งาน</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4" id="modalContent"></div>
            <div class="modal-footer border-0">
                <button class="btn btn-secondary px-4 rounded-pill w-100" data-bs-dismiss="modal">ปิดหน้าต่าง</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebarMenu');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('show');
    overlay.classList.toggle('show');
}

document.addEventListener('DOMContentLoaded', function () {
    const roomSelect  = document.getElementById('roomFilter');
    const calendarEl  = document.getElementById('calendar');
    const calendarBox = document.getElementById('calendarContainer');
    const helperBox   = document.getElementById('helperBox');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: window.innerWidth < 768 ? 'timeGridDay' : 'timeGridWeek',
        locale: 'th',
        height: 'auto',
        slotMinTime: '00:00:00',
        slotMaxTime: '23:59:00',
        allDaySlot: false,
        slotDuration: '00:30:00',
        nowIndicator: true,
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: window.innerWidth < 768 ? '' : 'timeGridDay,timeGridWeek'
        },
        eventContent: function(arg) {
            const timeStr = arg.timeText;
            const userStr = arg.event.extendedProps.username || 'Unspecified';
            const titleStr = arg.event.title || 'ห้องเรียน';
            return {
                html: `
                  <div class="custom-event">
                    <div class="event-time"><i class="bi bi-clock-history me-1"></i> ${timeStr}</div>
                    <div class="event-title-text">${titleStr}</div>
                    <div class="event-user"><i class="bi bi-person-circle me-1"></i> ${userStr}</div>
                  </div>
                `
            };
        },
        eventClick: function(info) {

    const desc = info.event.extendedProps.short_description || '-';
    const detail = info.event.extendedProps.detail || '-';
    const equipments = info.event.extendedProps.equipments || [];
    const leds = info.event.extendedProps.led_channels || [];

    let equipmentHtml = 'ไม่มีอุปกรณ์';
    let ledHtml = 'ไม่มีไฟที่เปิด';

    if (Array.isArray(equipments) && equipments.length > 0) {
    equipmentHtml = equipments.map(e => `<span class="badge bg-primary me-1">${e}</span>`).join('');
    }
    if (Array.isArray(leds) && leds.length > 0) {
    ledHtml = leds.map(l => `<span class="badge bg-warning text-dark me-1">${l}</span>`).join('');
    }

    document.getElementById('modalContent').innerHTML = `
    <div class="text-center mb-4">
        <div class="badge bg-primary-subtle text-primary mb-2 px-3">รายละเอียดการจอง</div>
        <h4 class="fw-bold text-dark">${info.event.title}</h4>
    </div>

    <div class="list-group list-group-flush border rounded-3 overflow-hidden shadow-sm">

        <div class="list-group-item d-flex justify-content-between p-3">
            <span class="text-muted"><i class="bi bi-person me-2"></i>ผู้จอง</span>
            <span class="fw-bold text-primary">${info.event.extendedProps.username}</span>
        </div>

        <div class="list-group-item d-flex justify-content-between p-3">
            <span class="text-muted"><i class="bi bi-calendar3 me-2"></i>วันที่</span>
            <span class="fw-bold">
                ${info.event.start.toLocaleDateString('th-TH', { dateStyle: 'long' })}
            </span>
        </div>

        <div class="list-group-item d-flex justify-content-between p-3">
            <span class="text-muted"><i class="bi bi-clock me-2"></i>ช่วงเวลา</span>
            <span class="fw-bold text-success">
                ${info.event.start.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}
                -
                ${info.event.end.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}
            </span>
        </div>

        <div class="list-group-item p-3">
            <span class="text-muted"><i class="bi bi-chat-left-text me-2"></i>คำอธิบาย</span>
            <div class="fw-bold mt-1">${desc}</div>
        </div>

        <div class="list-group-item p-3">
            <span class="text-muted"><i class="bi bi-file-text me-2"></i>รายละเอียด</span>
            <div class="fw-bold mt-1">${detail}</div>
        </div>

        <div class="list-group-item p-3">
            <span class="text-muted"><i class="bi bi-hdd-network me-2"></i>อุปกรณ์ที่ใช้</span>
            <div class="mt-2">${equipmentHtml}</div>
        </div>
        <div class="list-group-item p-3">
            <span class="text-muted"><i class="bi bi-lightbulb me-2"></i>ไฟที่เปิด</span>
            <div class="mt-2">${ledHtml}</div>
        </div>

    </div>
`;

    new bootstrap.Modal(document.getElementById('bookingModal')).show();
},
        events: function(fetchInfo, successCallback, failureCallback) {
            const roomId = roomSelect.value;
            if(!roomId) return;
            fetch(`load_events.php?room_id=${roomId}`)
                .then(res => res.json())
                .then(data => successCallback(data))
                .catch(err => failureCallback(err));
        }
    });

    calendar.render();

    window.addEventListener('resize', () => {
        const newView = window.innerWidth < 768 ? 'timeGridDay' : 'timeGridWeek';
        if (calendar.view.type !== newView) {
            calendar.changeView(newView);
        }
        calendar.updateSize();
    });

    roomSelect.addEventListener('change', function () {
        if (this.value) {
            helperBox.classList.add('d-none');
            calendarBox.classList.remove('d-none');
            calendar.refetchEvents();
            document.getElementById('calendarTitle').innerText = "ตารางการใช้ห้อง: " + this.options[this.selectedIndex].text;
            setTimeout(() => { calendar.updateSize(); }, 50);
        } else {
            calendarBox.classList.add('d-none');
            helperBox.classList.remove('d-none');
        }
    });

    const syncBtn = document.getElementById('syncBtn');
    if (syncBtn) {
        syncBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const href = this.getAttribute('href');
            Swal.fire({
                title: 'กำลังซิงค์ข้อมูล',
                text: 'กำลังเชื่อมโยงข้อมูลอุปกรณ์ใหม่',
                icon: 'info',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); window.location.href = href; }
            });
        });
    }

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('sync_done')) {
        Swal.fire({ icon: 'success', title: 'เรียบร้อย', text: 'ข้อมูลได้รับการซิงค์แล้ว', timer: 1500, showConfirmButton: false })
            .then(() => window.history.replaceState({}, document.title, window.location.pathname));
    }
});
const forceSyncBtn = document.getElementById('forceSyncBtn');

if (forceSyncBtn) {
    forceSyncBtn.addEventListener('click', function(e) {
        e.preventDefault();

        const href = this.getAttribute("href");

        Swal.fire({
            title: 'กำลังอัปเดตข้อมูล',
            text: 'กำลังส่งข้อมูลไปยัง Raspberry Pi',
            icon: 'info',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
                setTimeout(() => {
                    window.location.href = href;
                }, 500);
            }
        });
    });
}

// ตรวจสอบสถานะหลัง redirect
const urlParams = new URLSearchParams(window.location.search);

if (urlParams.has('rpi_offline')) {
    Swal.fire({
        icon: 'error',
        title: 'ไม่สามารถเชื่อมต่อ Raspberry Pi',
        text: 'กรุณาตรวจสอบว่า Raspberry Pi เปิดอยู่หรือเชื่อมต่อเครือข่าย'
    }).then(() => {
        window.history.replaceState({}, document.title, window.location.pathname);
    });
}

</script>
</body>
</html>