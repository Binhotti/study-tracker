<?php
session_start();
include "../config/db.php";

$session_id = $_POST["session_id"];

$sql = "UPDATE study_sessions 
        SET end_time = NOW(),
        hours = TIMESTAMPDIFF(SECOND, start_time, NOW()) / 3600
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $session_id);
$stmt->execute();

header("Location: ../index.php");
?>
