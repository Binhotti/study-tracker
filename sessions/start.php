<?php
session_start();
include "../config/db.php";

header('Content-Type: application/json');

$user_id = $_SESSION["user_id"];
$subject_id = $_POST["subject_id"];

$sql = "INSERT INTO study_sessions (user_id, subject_id, start_time, session_date)
        VALUES (?, ?, NOW(), CURDATE())";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $subject_id);

if ($stmt->execute()) {
    $session_id = $stmt->insert_id;
    echo json_encode([
        'ok' => true,
        'session_id' => $session_id,
        'start_time' => date('Y-m-d H:i:s')
    ]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Database error']);
}
?>
