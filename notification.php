<?php
require_once __DIR__ . '/../config.php';

/* เพิ่มแจ้งเตือน */
function createNotification($user_id, $title, $message)
{
    global $conn;

    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, title, message)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user_id, $title, $message]);
}

/* นับแจ้งเตือนที่ยังไม่อ่าน */
function countUnreadNotifications($user_id)
{
    global $conn;

    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM notifications
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$user_id]);

    return $stmt->fetchColumn();
}
