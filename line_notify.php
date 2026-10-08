<?php

date_default_timezone_set('Asia/Bangkok');

/**
 * ฟังก์ชันหลักสำหรับส่งข้อความ Push Message เข้า LINE Group
 */
function sendLineMessage($message)
{
    // Channel Access Token จาก LINE Developers
    $LINE_TOKEN = "qxpqFm5C/Db7cL+jTVaF1BImGfbkQo70YuZVzFS2de4PbWwzSCg8qPhAL++LibYRDTUw5Oj/ryTWd47FQTpoqsDJ/oiD59GHn5m6Q3VY1DuHrSGQMjnHDBekdLfnCXrQih3viPhdKfqC5dgQCiRTjAdB04t89/1O/w1cDnyilFU=";
    
    // Group ID ของกลุ่ม LINE Admin
    $ADMIN_GROUP_ID = "C2e0dc5b7e5d588ed66f9405244f55c08";

    $data = [
        "to" => $ADMIN_GROUP_ID,
        "messages" => [
            [
                "type" => "text",
                "text" => $message
            ]
        ]
    ];

    $ch = curl_init("https://api.line.me/v2/bot/message/push");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "Authorization: Bearer " . $LINE_TOKEN
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $result = curl_exec($ch);
    curl_close($ch);

    return $result;
}

/**
 * 🔔 แจ้งเตือนการจองห้องเรียนใหม่ (สำหรับ User)
 */
function notifyAdminBooking($roomName = "-", $userName = "-", $startTime = "-", $endTime = "-")
{
    $message =
        "มีรายการขอจองห้องเรียนใหม่ (รออนุมัติ)\n\n" .
        "ห้อง: $roomName\n" .
        "ผู้ขอจอง: $userName\n" .
        "เวลา: " . date("d/m/Y H:i", strtotime($startTime)) . " - " . date("H:i", strtotime($endTime)) . "\n\n" .
        "กรุณาเข้าสู่ระบบเพื่อตรวจสอบและอนุมัติ";

    sendLineMessage($message);
}

/**
 * 🚪 แจ้งเตือนเมื่อตรวจพบประตูเปิดค้างไว้
 */
function notifyDoorAlert($roomName = "ห้องเรียน")
{
    $message =
        "ตรวจพบประตูเปิดค้างไว้ ($roomName)\n\n" .
        "กรุณาดำเนินการปิดประตูให้เรียบร้อย\n" .
        "เวลาแจ้งเตือน: " . date("d/m/Y H:i:s");

    sendLineMessage($message);
}

/**
 * 🔧 แจ้งเตือนการจัดการอุปกรณ์
 */
function notifyAdminDevice($action = "-", $deviceName = "-")
{
    $actionText = "อัปเดตข้อมูลอุปกรณ์";
    if ($action === "create") {
        $actionText = "เพิ่มอุปกรณ์ใหม่";
    } elseif ($action === "update") {
        $actionText = "แก้ไขข้อมูลอุปกรณ์";
    } elseif ($action === "delete") {
        $actionText = "ลบอุปกรณ์";
    }

    $message =
        "$actionText\n\n" .
        "อุปกรณ์: $deviceName\n" .
        "อัปเดตเวลา: " . date("d/m/Y H:i:s");

    sendLineMessage($message);
}