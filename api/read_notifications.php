<?php
header('Content-Type: application/json');
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$std_id = $_SESSION['user_id'];

// Input: read_all=true OR notif_id=<id>
if (isset($_POST['read_all']) && $_POST['read_all'] === 'true') {
    $stmt = $conn->prepare("UPDATE tb_notifications SET is_read = 1 WHERE std_id = ?");
    $stmt->bind_param("s", $std_id);
    $stmt->execute();
} elseif (isset($_POST['notif_id'])) {
    $notif_id = (int)$_POST['notif_id'];
    $stmt = $conn->prepare("UPDATE tb_notifications SET is_read = 1 WHERE std_id = ? AND id = ?");
    $stmt->bind_param("si", $std_id, $notif_id);
    $stmt->execute();
}

echo json_encode(['success' => true]);
?>
