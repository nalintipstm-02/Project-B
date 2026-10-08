<?php
include 'config.php';

$room_id = $_GET['room_id'] ?? null;

if (!$room_id) {
    echo json_encode([]);
    exit;
}

$sql = "
SELECT
    b.id,
    r.room_name AS title,
    b.booking_date,
    b.start_time,
    b.end_time,
    u.username,
    b.short_description,
    b.detail,
    GROUP_CONCAT(DISTINCT n.detail) AS equipments,
    GROUP_CONCAT(DISTINCT dc.name) AS led_channels

FROM bookings b
JOIN room r ON r.room_id = b.room_id
JOIN users u ON u.id = b.user_id

LEFT JOIN booking_equipments be 
ON be.booking_id = b.id

LEFT JOIN node n 
ON n.node_id = be.node_id

LEFT JOIN project.booking_led_channel blc 
ON blc.booking_id = b.id

LEFT JOIN project.device_channel dc 
ON dc.id = blc.channel_id

WHERE b.status = 'approved'
AND b.room_id = :room_id

GROUP BY b.id
ORDER BY b.booking_date, b.start_time
";

$stmt = $conn->prepare($sql);
$stmt->execute([':room_id' => $room_id]);

$events = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $events[] = [
        'id' => $row['id'],
        'title' => $row['title'],

        'start' => str_replace(' ', 'T', $row['start_time']),
        'end'   => str_replace(' ', 'T', $row['end_time']),

        'extendedProps' => [
            'username' => $row['username'],
            'short_description' => $row['short_description'],
            'detail' => $row['detail'],

            'equipments' => $row['equipments']
                ? explode(',', $row['equipments'])
                : [],

            'led_channels' => $row['led_channels']
                ? explode(',', $row['led_channels'])
                : []
        ]
    ];
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode($events);