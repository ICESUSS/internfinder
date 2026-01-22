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

$report_id = intval($_GET['id']);

// ตรวจสอบว่ารายงานนี้มีอยู่จริง
$check_stmt = $conn->prepare("SELECT * FROM tb_reports WHERE report_id = ?");
if ($check_stmt) {
    $check_stmt->bind_param("i", $report_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();
    if ($check_res->num_rows == 0) {
        $check_stmt->close();
        header("Location: report.php?msg=notfound");
        exit();
    }
    $check_stmt->close();
}

// อัปเดตสถานะเป็น closed
$status = 'closed';
$update_stmt = $conn->prepare("UPDATE tb_reports SET status = ? WHERE report_id = ?");
if ($update_stmt) {
    $update_stmt->bind_param("si", $status, $report_id);
    if ($update_stmt->execute()) {
        $update_stmt->close();
        header("Location: report.php?msg=resolved");
    } else {
        $update_stmt->close();
        header("Location: report.php?msg=error");
    }
} else {
    header("Location: report.php?msg=error");
}
exit();
?>
