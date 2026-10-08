<?php
function publishSyncEvent($table) {

    $url = "http://192.168.1.100:5001/trigger-sync";

    $data = json_encode([
        "table" => $table
    ]);

    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($response === false) {
        error_log("Sync API error: " . curl_error($ch));
    }

    curl_close($ch);

    return $http_code === 200;
}   