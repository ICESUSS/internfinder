<?php
session_start();
include __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

$std_id = $_SESSION['user_id'];

// ensure table exists
$createSql = "CREATE TABLE IF NOT EXISTS tb_reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    std_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(32) NOT NULL DEFAULT 'open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
$conn->query($createSql);

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    // create a new report
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if ($subject === '' || $message === '') {
        echo json_encode(['success' => false, 'error' => 'missing_fields']);
        exit;
    }

    if (mb_strlen($subject) > 255) {
        $subject = mb_substr($subject, 0, 255);
    }

    $ins = $conn->prepare("INSERT INTO tb_reports (std_id, subject, message) VALUES (?, ?, ?)");
    if (!$ins) {
        echo json_encode(['success' => false, 'error' => 'db_prepare']);
        exit;
    }
    $ins->bind_param('iss', $std_id, $subject, $message);
    $ok = $ins->execute();
    if ($ok) {
        $reportId = $ins->insert_id;
        $ins->close();
        // fetch created_at
        $stmt = $conn->prepare("SELECT created_at FROM tb_reports WHERE report_id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $reportId);
            $stmt->execute();
            $res = $stmt->get_result();
            $created_at = ($res && $res->num_rows) ? $res->fetch_assoc()['created_at'] : null;
            $stmt->close();
        } else {
            $created_at = null;
        }

        echo json_encode(['success' => true, 'report_id' => $reportId, 'created_at' => $created_at]);
        exit;
    } else {
        echo json_encode(['success' => false, 'error' => 'db_insert']);
        exit;
    }
} elseif ($method === 'GET') {
    // list reports for this student
    $stmt = $conn->prepare("SELECT report_id, subject, message, status, created_at FROM tb_reports WHERE std_id = ? ORDER BY created_at DESC");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'db_prepare']);
        exit;
    }
    $stmt->bind_param('i', $std_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) $rows[] = $r;
    }
    $stmt->close();

    echo json_encode(['success' => true, 'reports' => $rows]);
    exit;
} else {
    echo json_encode(['success' => false, 'error' => 'invalid_method']);
    exit;
}
