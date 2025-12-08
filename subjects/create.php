<?php
// subjects/create.php
session_start();
require_once("../config/db.php");
if (!isset($_SESSION['user_id'])) header("Location: ../auth/login.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $user_id = $_SESSION['user_id'];
    if ($name) {
        $sql = "INSERT INTO subjects (user_id, name) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $name);
        $stmt->execute();
    }
}
header("Location: ../index.php");
exit;
