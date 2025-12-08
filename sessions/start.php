<?php
session_start();
include "../config/db.php";

$user_id = $_SESSION["user_id"];
$subject_id = $_POST["subject_id"];

$sql = "INSERT INTO study_sessions (user_id, subject_id, start_time, session_date)
        VALUES (?, ?, NOW(), CURDATE())";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $subject_id);
$stmt->execute();

header("Location: ../index.php");
?>
