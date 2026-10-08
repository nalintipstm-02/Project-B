<?php
session_start();

// อนุญาตเฉพาะ admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized");
}

/* -----------------------
   ตรวจสอบ Raspberry Pi
----------------------- */

$rpi_ip = "192.168.1.101";
$port = 1883; // MQTT port

$timeout = 2;
$connection = @fsockopen($rpi_ip, $port, $errno, $errstr, $timeout);

if (!$connection) {
    // ถ้าเชื่อมต่อไม่ได้
    header("Location: index.php?rpi_offline=1");
    exit();
}

fclose($connection);

/* -----------------------
   ถ้า RPI online ให้ Sync
----------------------- */

require_once 'sync_mqtt.php';

try {

    // ส่ง event ไป Raspberry Pi
    publishSyncEvent("api_update");

    // Sync สำเร็จ
    header("Location: index.php?sync_done=1");
    exit();

} catch (Exception $e) {

    // ถ้า MQTT มีปัญหา
    header("Location: index.php?rpi_offline=1");
    exit();

}