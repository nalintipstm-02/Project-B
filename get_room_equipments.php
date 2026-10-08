<?php
include 'config.php';
$room_id = $_GET['room_id'] ?? 0;

$stmt = $conn->prepare("
    SELECT n.node_id, n.node_name, n.node_type, n.detail
    FROM Node n
    JOIN Installation_Node ins ON n.node_id = ins.node_id
    WHERE ins.room_id = ?
");
$stmt->execute([$room_id]);
$nodes = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($nodes as $n){
    echo '<input type="checkbox" name="equipments[]" value="'.$n['node_id'].'"> '.htmlspecialchars($n['node_name']).'<br>';
}
