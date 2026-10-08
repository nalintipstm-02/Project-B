<?php
include 'config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid JSON or not array"]);
    exit();
}

try {
    foreach ($data as $node) {
        // ตรวจว่ามี node นี้อยู่แล้วหรือไม่
        $check = $conn->prepare("SELECT id FROM equipments WHERE node_id = ? AND room_id = ?");
        $check->execute([$node['node_id'], $node['room_id']]);
        $exists = $check->fetch();

        if ($exists) {
            // UPDATE
            $stmt = $conn->prepare("
                UPDATE equipments SET 
                    cluster_id = ?, 
                    node_name = ?, 
                    node_type = ?, 
                    detail = ?, 
                    room_name = ?, 
                    floor_number = ?, 
                    room_type = ?
                WHERE node_id = ? AND room_id = ?
            ");
            $stmt->execute([
                $node['cluster_id'],
                $node['node_name'],
                $node['node_type'],
                $node['detail'],
                $node['room_name'],
                $node['floor_number'],
                $node['room_type'],
                $node['node_id'],
                $node['room_id']
            ]);
        } else {
            // INSERT
            $stmt = $conn->prepare("
                INSERT INTO equipments (
                    cluster_id, node_id, node_name, node_type, detail,
                    room_id, room_name, floor_number, room_type
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $node['cluster_id'],
                $node['node_id'],
                $node['node_name'],
                $node['node_type'],
                $node['detail'],
                $node['room_id'],
                $node['room_name'],
                $node['floor_number'],
                $node['room_type']
            ]);
        }
    }

    echo json_encode(["status" => "success", "message" => "อัปเดต/เพิ่มข้อมูลทั้งหมดเรียบร้อย"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}

?>
