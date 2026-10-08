<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}
include 'config.php';

// เพิ่มอุปกรณ์
if(isset($_POST['action']) && $_POST['action']=='add'){
    $name = $_POST['name'];
    $stmt = $conn->prepare("INSERT INTO equipments (name) VALUES (?)");
    $stmt->execute([$name]);
    header('Location: manage_equipments.php');
    exit();
}

// ลบอุปกรณ์
if(isset($_GET['delete'])){
    $stmt = $conn->prepare("DELETE FROM equipments WHERE id=?");
    $stmt->execute([$_GET['delete']]);
    header('Location: manage_equipments.php');
    exit();
}

$equipments = $conn->query("SELECT * FROM equipments")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>จัดการอุปกรณ์</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-4">
<h2>จัดการอุปกรณ์</h2>

<div class="card mb-4 p-3">
<h5>เพิ่มอุปกรณ์</h5>
<form method="POST">
<input type="hidden" name="action" value="add">
<div class="mb-3">
<label>ชื่ออุปกรณ์</label>
<input type="text" name="name" class="form-control" required>
</div>
<button class="btn btn-primary">เพิ่มอุปกรณ์</button>
</form>
</div>

<table class="table table-bordered">
<tr><th>ID</th><th>ชื่ออุปกรณ์</th><th>จัดการ</th></tr>
<?php foreach($equipments as $e): ?>
<tr>
<td><?= $e['id'] ?></td>
<td><?= htmlspecialchars($e['name']) ?></td>
<td>
<a href="?delete=<?= $e['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('ลบอุปกรณ์นี้?')">ลบ</a>
</td>
</tr>
<?php endforeach; ?>
</table>

<a href="index.php" class="btn btn-secondary">กลับหน้าหลัก</a>
</div>
</body>
</html>
