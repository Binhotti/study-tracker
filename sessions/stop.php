<?php
session_start();
include "../config/db.php";

header('Content-Type: application/json');

$session_id = $_POST["session_id"] ?? null;
$elapsed_seconds = isset($_POST["elapsed_seconds"]) ? (int)$_POST["elapsed_seconds"] : 0;

// Calculate hours from seconds (elapsed_seconds / 3600)
$hours = $elapsed_seconds / 3600;

error_log("STOP.PHP - Session ID: $session_id, Elapsed: $elapsed_seconds, Hours: $hours");

if (!$session_id) {
    echo json_encode(['ok' => false, 'error' => 'No session ID']);
    exit;
}

$sql = "UPDATE study_sessions 
        SET end_time = NOW(),
        hours = ?
        WHERE id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'Prepare error: ' . $conn->error]);
    exit;
}

$stmt->bind_param("di", $hours, $session_id);

if ($stmt->execute()) {
    error_log("STOP.PHP - Update successful for session $session_id");
    echo json_encode(['ok' => true, 'hours' => $hours, 'elapsed_seconds' => $elapsed_seconds]);
} else {
    error_log("STOP.PHP - Update failed: " . $stmt->error);
    echo json_encode(['ok' => false, 'error' => 'Execute error: ' . $stmt->error]);
}
$stmt->close();
?>
