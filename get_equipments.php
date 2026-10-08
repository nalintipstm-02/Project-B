<?php
include 'config.php';

if (isset($_GET['room_id'])) {
    $room_id = (int)$_GET['room_id'];

    // ดึงข้อมูล node ที่อยู่ในห้องนั้น ๆ
    $stmt = $conn->prepare("
        SELECT n.node_id, n.node_name, n.node_type, n.detail, n.node_status
        FROM node n
        JOIN room_nodes rn ON n.node_id = rn.node_id
        WHERE rn.room_id = ?
    ");
    $stmt->execute([$room_id]);

    $nodes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($nodes);
}
?>
