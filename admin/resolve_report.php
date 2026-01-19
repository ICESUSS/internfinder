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
$check = mysqli_query($conn, "SELECT * FROM tb_reports WHERE report_id = $report_id");
if (mysqli_num_rows($check) == 0) {
    header("Location: report.php?msg=notfound");
    exit();
}

// อัปเดตสถานะเป็น closed
$sql = "UPDATE tb_reports SET status = 'closed' WHERE report_id = $report_id";

if (mysqli_query($conn, $sql)) {
    header("Location: report.php?msg=resolved");
} else {
    header("Location: report.php?msg=error");
}
exit();
?>
