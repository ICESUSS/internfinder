<?php
session_start();
include __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'invalid_method']);
    exit;
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

$std_id = $_SESSION['user_id'];
$com_id = isset($_POST['com_id']) ? (int) $_POST['com_id'] : 0;
if ($com_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'invalid_com_id']);
    exit;
}

// Ensure table exists (safe to run on each request)
$createSql = "CREATE TABLE IF NOT EXISTS tb_saved (
    save_id INT AUTO_INCREMENT PRIMARY KEY,
    std_id INT NOT NULL,
    com_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_std_com (std_id, com_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$conn->query($createSql);

// Check if already saved
$stmt = $conn->prepare("SELECT save_id FROM tb_saved WHERE std_id = ? AND com_id = ? LIMIT 1");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'db_prepare']);
    exit;
}
$stmt->bind_param('ii', $std_id, $com_id);
$stmt->execute();
$res = $stmt->get_result();
$existing = ($res && $res->num_rows > 0) ? $res->fetch_assoc() : null;
$stmt->close();

if ($existing) {
    // remove
    $del = $conn->prepare("DELETE FROM tb_saved WHERE save_id = ? LIMIT 1");
    if ($del) {
        $del->bind_param('i', $existing['save_id']);
        $ok = $del->execute();
        $del->close();
        echo json_encode(['success' => (bool)$ok, 'saved' => false]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'db_delete']);
        exit;
    }
} else {
    // insert
    $ins = $conn->prepare("INSERT INTO tb_saved (std_id, com_id) VALUES (?, ?)");
    if ($ins) {
        $ins->bind_param('ii', $std_id, $com_id);
        $ok = $ins->execute();
        $ins->close();
        echo json_encode(['success' => (bool)$ok, 'saved' => (bool)$ok]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'db_insert']);
        exit;
    }
}
