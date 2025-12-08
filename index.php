<?php
session_start();
include "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: auth/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$query = $conn->query("SELECT SUM(hours) AS total_hours FROM study_sessions WHERE user_id = $user_id");
$row = $query->fetch_assoc();
$total = $row["total_hours"] ?? 0;
?>

<h1>Study Tracker</h1>
<p>Total study hours: <strong><?php echo $total; ?></strong></p>

<a href="subjects/list.php">Subjects</a><br>
<a href="sessions/list.php">Study Sessions</a><br>
<a href="auth/logout.php">Logout</a>
