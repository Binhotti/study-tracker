<?php
require_once 'config/db.php';
session_start();
$user_id = $_SESSION['user_id'] ?? 1;

$sql = "SELECT id, subject_id, start_time, end_time, hours, session_date FROM study_sessions WHERE user_id = ? ORDER BY id DESC LIMIT 20";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();

echo "Sessions for user id= $user_id:\n";
while ($row = $res->fetch_assoc()) {
    echo sprintf("id=%d subject=%d date=%s start=%s end=%s hours=%s\n",
        $row['id'], $row['subject_id'], $row['session_date'], $row['start_time'] ?? 'NULL', $row['end_time'] ?? 'NULL', $row['hours'] === null ? 'NULL' : $row['hours']);
}

$stmt->close();
?>