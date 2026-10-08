<?php
date_default_timezone_set('Asia/Bangkok');
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

/*
 * สร้าง Token แบบสุ่ม
 */
$token = bin2hex(random_bytes(32));

/*
 * เก็บเฉพาะ hash ของ token ลง DB
 */
$token_hash = hash('sha256', $token);

/*
 * Token มีอายุ 60 วินาที
 */
$expires_at = date('Y-m-d H:i:s', time() + 60);

$stmt = $conn->prepare("
    INSERT INTO sso_tokens
    (user_id, token_hash, expires_at, used)
    VALUES (?, ?, ?, 0)
");

$stmt->execute([
    $_SESSION['user_id'],
    $token_hash,
    $expires_at
]);

/*
 * Flask Server
 */
// $python_url = "http://192.168.1.100:5000/sso";
$python_url = "http://192.168.34.125:5000/sso";
/*
 * ส่ง Token ไป Flask
 */
header(
    "Location: " . $python_url .
    "?token=" . urlencode($token)
);

exit();
?>