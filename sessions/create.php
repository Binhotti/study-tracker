<?php
session_start();
include "../config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}   

$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $subject_id = $_POST["subject_id"];
    $hours = $_POST["hours"];
    $date = $_POST["session_date"];

    $sql = "INSERT INTO study_sessions (user_id, subject_id, hours, session_date) 
            VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iids", $user_id, $subject_id, $hours, $date);
    $stmt->execute();

    header("Location: list.php");
}
?>
