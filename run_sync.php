<?php
session_start();

include 'line_notify.php';


// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized: เฉพาะผู้ดูแลระบบเท่านั้น");
}

// 1. ตั้งค่า Environment ให้รองรับ UTF-8 (ป้องกันภาษาไทยอ่านไม่ออก)
putenv('PYTHONIOENCODING=utf-8');

// 2. กำหนดชื่อไฟล์และ Path
$fileName = "update_from_central.py"; 
$scriptPath = __DIR__ . DIRECTORY_SEPARATOR . $fileName;

// 3. รันคำสั่ง Python
// หาก Windows หา python ไม่เจอ ให้ระบุ path เต็ม เช่น "C:/Python312/python.exe"
$command = "python \"$scriptPath\" 2>&1"; 
$output = shell_exec($command);

// 4. ตรวจสอบผลลัพธ์
if ($output) {
    /**
     * ใช้การเช็คคำว่า 'Sync' หรือ 'room' แทนคำว่า 'สำเร็จ' 
     * เพราะบางครั้งภาษาไทยจาก Python ใน Windows จะถูกเข้ารหัสผิดเพี้ยน
     */
    $isSuccess = (stripos($output, 'Sync') !== false || stripos($output, 'room') !== false);
    
    // ตรวจสอบว่าไม่มีคำว่า Traceback หรือ Error
    $hasError = (stripos($output, 'Traceback') !== false || stripos($output, 'Error') !== false);

    if ($isSuccess && !$hasError) {
        // ถ้าสำเร็จ: ส่งกลับไปหน้า index พร้อมพารามิเตอร์ success
        notifyAdminDevice("update", "Sync ข้อมูลจาก Central สำเร็จ");
        header("Location: index.php?sync_done=1");
        exit();
    } else {
        // ถ้าล้มเหลว: แสดงหน้าจอ Error พร้อมรายละเอียด
        ?>
        <!DOCTYPE html>
        <html lang="th">
        <head>
            <meta charset="UTF-8">
            <title>Sync Error</title>
            <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        </head>
        <body class="bg-light">
            <div class="container mt-5">
                <div class="card border-danger shadow">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">เกิดข้อผิดพลาดในการ Sync ข้อมูล</h5>
                    </div>
                    <div class="card-body">
                        <p>ระบบตรวจพบปัญหาขณะรันสคริปต์ Python:</p>
                        <pre class="bg-dark text-warning p-3 rounded"><?php echo htmlspecialchars($output); ?></pre>
                        <div class="mt-3">
                            <a href="index.php" class="btn btn-secondary">กลับหน้าหลัก</a>
                            <button onclick="window.location.reload();" class="btn btn-primary">ลองใหม่อีกครั้ง</button>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}

// กรณีรันแล้วไม่มี output (แต่อาจจะสำเร็จ)
header("Location: index.php?sync_done=1");
exit();