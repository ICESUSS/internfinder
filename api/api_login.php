<?php
session_start();
include '../config.php';

// ตรวจสอบว่า request มี username และ password
if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
    header('Location: ../login.php');
    exit();
}

$username = $_REQUEST['username'];
$password = $_REQUEST['password'];

// ค้นหานักศึกษาจาก tb_student
$sql = "SELECT * FROM tb_student WHERE std_id = ? AND std_password = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['std_id'];
        $_SESSION['user_name'] = $row['std_name'];
        $_SESSION['user_type'] = 'student';
        $stmt->close();
        header('Location: ../admin/dashboard.php');
        exit();
    }
    $stmt->close();
}

header('Location: ../login.php?error=invalid');
exit();