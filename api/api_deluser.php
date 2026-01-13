<?php
session_start();
include '../config.php';

// ตรวจสอบสิทธิ์และ ID
if (!isset($_SESSION['user_id'])) {
    // not logged in, redirect to login
    header('Location: ../login.php');
    exit();
}

// รับ ID จาก GET หรือ POST
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($id <= 0) {
    // invalid id, redirect back
    $dest = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') ? '../admin_dashboard.php' : '../student_dashboard.php';
    header('Location: ' . $dest . '?error=invalid_id');
    exit();
}

// ลบเรคอร์ดจาก tb_student โดยใช้ prepared statement
$sql = "DELETE FROM tb_student WHERE std_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $stmt->close();
        $dest = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') ? '../admin_dashboard.php' : '../student_dashboard.php';
        header('Location: ' . $dest . '?msg=deleted');
        exit();
    } else {
        $stmt->close();
        $dest = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') ? '../admin_dashboard.php' : '../student_dashboard.php';
        header('Location: ' . $dest . '?error=not_found');
        exit();
    }
} else {
    $dest = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') ? '../admin_dashboard.php' : '../student_dashboard.php';
    header('Location: ' . $dest . '?error=db');
    exit();
}
?>