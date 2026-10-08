<?php
include 'config.php';

// ดึงรายการจองที่อนุมัติแล้ว
$stmt = $conn->query("
    SELECT b.id, r.name AS room_name, b.booking_date, b.start_time, b.end_time
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    WHERE b.status='approved'
");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// แปลงข้อมูลให้อยู่ในรูปแบบ FullCalendar
$events = [];
foreach($bookings as $b) {
    $events[] = [
        'title' => $b['room_name'],
        'start' => $b['booking_date'].'T'.$b['start_time'],
        'end'   => $b['booking_date'].'T'.$b['end_time'],
    ];
}

header('Content-Type: application/json');
echo json_encode($events);
