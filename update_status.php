<?php
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: text/plain');

try {

$project = new PDO(
    "mysql:host=localhost;dbname=project;charset=utf8mb4",
    "root",
    ""
);

$project->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ==========================
   VALIDATE INPUT
========================== */

if (!isset($_POST['status']) || !isset($_POST['node_id'])) {
    http_response_code(400);
    exit("ERROR: missing parameters");
}

$status = strtolower(trim($_POST['status']));
$node_id = intval($_POST['node_id']);
$channel = isset($_POST['channel']) ? intval($_POST['channel']) : 0;

if (!in_array($status, ['on','off'])) {
    http_response_code(400);
    exit("ERROR: invalid status");
}

/* ==========================
   CHECK OLD STATUS
========================== */

$stmt = $project->prepare("
    SELECT status, updated_at
    FROM device_status
    WHERE node_id = ? AND channel = ?
");

$stmt->execute([$node_id, $channel]);

$oldData = $stmt->fetch(PDO::FETCH_ASSOC);

if ($oldData) {

    $lastUpdate = strtotime($oldData['updated_at']);
    $timeDiff = time() - $lastUpdate;

    if ($oldData && $oldData['status'] === $status) {

    // อัปเดตเวลา heartbeat
    $stmt = $project->prepare("
        UPDATE device_status 
        SET updated_at = NOW()
        WHERE node_id = ? AND channel = ?
    ");

    $stmt->execute([$node_id, $channel]);

    exit("ALIVE");
}
}

/* ==========================
   UPSERT STATUS
========================== */

$sql = "
INSERT INTO device_status (node_id, channel, status, updated_at)
VALUES (:node_id, :channel, :status, NOW())
ON DUPLICATE KEY UPDATE
status = VALUES(status),
updated_at = NOW()
";

$stmt = $project->prepare($sql);

$stmt->execute([
':node_id' => $node_id,
':channel' => $channel,
':status' => $status
]);

echo "SUCCESS";

} catch (PDOException $e) {

http_response_code(500);
echo "DATABASE_ERROR";

}
?>