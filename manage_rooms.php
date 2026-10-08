<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
include 'config.php';

// ดึงอุปกรณ์ทั้งหมด
$equipments = $conn->query("SELECT * FROM equipments")->fetchAll(PDO::FETCH_ASSOC);

// เพิ่มห้อง
if(isset($_POST['action']) && $_POST['action']=='add') {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $stmt = $conn->prepare("INSERT INTO rooms (name, description) VALUES (?, ?)");
    $stmt->execute([$name,$desc]);
    $room_id = $conn->lastInsertId();
    if(!empty($_POST['equipments'])){
        $stmt2 = $conn->prepare("INSERT INTO room_equipments (room_id, equipment_id) VALUES (?, ?)");
        foreach($_POST['equipments'] as $eq) $stmt2->execute([$room_id,$eq]);
    }
    header('Location: manage_rooms.php');
    exit();
}

// ลบห้อง
if(isset($_GET['delete'])){
    $stmt = $conn->prepare("DELETE FROM rooms WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header('Location: manage_rooms.php');
    exit();
}

// ดึงห้องทั้งหมด
$rooms = $conn->query("SELECT * FROM rooms")->fetchAll(PDO::FETCH_ASSOC);

// ฟังก์ชันดึงอุปกรณ์ของแต่ละห้อง
function getRoomEquipments($conn,$room_id){
    $stmt = $conn->prepare("SELECT equipment_id FROM room_equipments WHERE room_id=?");
    $stmt->execute([$room_id]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>จัดการห้องเรียน</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-4">
<h2>จัดการห้องเรียน</h2>

<!-- เพิ่มห้อง -->
<div class="card mb-4 p-3">
<h5>เพิ่มห้องใหม่</h5>
<form method="POST">
<input type="hidden" name="action" value="add">
<div class="mb-3">
<label>ชื่อห้อง</label>
<input type="text" name="name" class="form-control" required>
</div>
<div class="mb-3">
<label>รายละเอียด</label>
<textarea name="description" class="form-control"></textarea>
</div>
<div class="mb-3">
<label>เชื่อมอุปกรณ์</label><br>
<?php foreach($equipments as $e): ?>
<input type="checkbox" name="equipments[]" value="<?= $e['id'] ?>"> <?= htmlspecialchars($e['name']) ?><br>
<?php endforeach; ?>
</div>
<button class="btn btn-primary">เพิ่มห้อง</button>
</form>
</div>

<!-- ตารางห้องทั้งหมด -->
<table class="table table-bordered">
<tr><th>ห้อง</th><th>รายละเอียด</th><th>อุปกรณ์</th><th>จัดการ</th></tr>
<?php foreach($rooms as $r): ?>
<tr>
<td><?= htmlspecialchars($r['name']) ?></td>
<td><?= htmlspecialchars($r['description']) ?></td>
<td>
<?php
$eqs = getRoomEquipments($conn,$r['id']);
$stmt2 = $conn->prepare("SELECT name FROM equipments WHERE id IN(".implode(',',array_map('intval',$eqs)).")");
$stmt2->execute();
$eq_names = $stmt2->fetchAll(PDO::FETCH_COLUMN);
echo implode(', ',$eq_names);
?>
</td>
<td>
<a href="?delete=<?= $r['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('ลบห้องนี้?')">ลบ</a>
</td>
</tr>
<?php endforeach; ?>
</table>
<a href="index.php" class="btn btn-secondary">กลับหน้าหลัก</a>
</div>
</body>
</html>
