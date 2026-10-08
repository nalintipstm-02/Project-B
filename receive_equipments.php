<?php
include 'config.php';  // เชื่อมต่อฐานข้อมูล

header('Content-Type: application/json');

// อ่าน JSON จาก request
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON"]);
    exit();
}

try {
    foreach ($data as $node) {
        // ตรวจสอบว่ามี node + room นี้อยู่แล้วหรือไม่
        $check = $conn->prepare("SELECT * FROM Installation_Node WHERE node_id = ? AND room_id = ?");
        $check->execute([$node['node_id'], $node['room_id']]);
        $exists = $check->fetch();

        if ($exists) {
            // อัปเดต Node info
            $stmt = $conn->prepare("UPDATE Node SET node_name=?, node_type=?, detail=?, node_status='active' WHERE node_id=?");
            $stmt->execute([$node['node_name'], $node['node_type'], $node['detail'], $node['node_id']]);
        } else {
            // เพิ่ม Node ใหม่
            $stmt = $conn->prepare("INSERT INTO Node (node_id, node_name, node_type, detail, node_status) VALUES (?, ?, ?, ?, 'active')");
            $stmt->execute([$node['node_id'], $node['node_name'], $node['node_type'], $node['detail']]);

            // เพิ่ม Installation_Node
            $stmt2 = $conn->prepare("INSERT INTO Installation_Node (room_id, node_id, status) VALUES (?, ?, 1)");
            $stmt2->execute([$node['room_id'], $node['node_id']]);
        }

        // เพิ่ม/อัปเดต Room
        $checkRoom = $conn->prepare("SELECT * FROM Room WHERE room_id = ?");
        $checkRoom->execute([$node['room_id']]);
        $roomExists = $checkRoom->fetch();

        if (!$roomExists) {
            $stmtRoom = $conn->prepare("INSERT INTO Room (room_id, room_name, floor_number, room_type, room_status) VALUES (?, ?, ?, ?, 'available')");
            $stmtRoom->execute([$node['room_id'], $node['room_name'], $node['floor_number'], $node['room_type']]);
        }
    }

    echo json_encode(["status" => "success", "message" => "อัปเดต/เพิ่มข้อมูลเรียบร้อย"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
