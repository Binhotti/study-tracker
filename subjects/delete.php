<?php
// subjects/delete.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config/db.php';

// check logged
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../index.php');
    exit;
}

$subject_id = (int) $_GET['id'];

// Optional: verify this subject belongs to the logged user
$stmt = $conn->prepare("SELECT id FROM subjects WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $subject_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    header('Location: ../index.php');
    exit;
}

$conn->begin_transaction();

try {
    $d1 = $conn->prepare("DELETE FROM study_sessions WHERE subject_id = ? AND user_id = ?");
    $d1->bind_param("ii", $subject_id, $user_id);
    $d1->execute();

    $d2 = $conn->prepare("DELETE FROM subjects WHERE id = ? AND user_id = ?");
    $d2->bind_param("ii", $subject_id, $user_id);
    $d2->execute();

    $conn->commit();

    header('Location: ../index.php');
    exit;
} catch (Exception $e) {
    $conn->rollback();
    echo "Error deleting: " . $e->getMessage();
    exit;
}
