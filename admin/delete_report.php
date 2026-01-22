<?php
session_start();
include '../config.php';

// ตรวจสอบสิทธิ์ (Admin Only)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// ตรวจสอบ ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: report.php?msg=error");
    exit();
}

$report_id = (int)$_GET['id'];

// ลบรายงาน
$stmt = $conn->prepare("DELETE FROM tb_reports WHERE report_id = ?");
if ($stmt) {
    $stmt->bind_param('i', $report_id);
    if ($stmt->execute()) {
        header("Location: report.php?msg=deleted");
    } else {
        header("Location: report.php?msg=error");
    }
    $stmt->close();
} else {
    header("Location: report.php?msg=error");
}

exit();
?>
