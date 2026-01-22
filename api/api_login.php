<?php
session_start();
include '../config.php';

// ตรวจสอบว่า request มี username และ password
if (!isset($_REQUEST['username']) || !isset($_REQUEST['password'])) {
    header('Location: ../login.php');
    exit();
}

$username = trim($_REQUEST['username']);
$password = $_REQUEST['password'];

// ค้นหานักศึกษาจาก tb_student (ไม่เปรียบเทียบ password ใน SQL)
$sql = "SELECT * FROM tb_student WHERE std_id = ?";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        
        // ตรวจสอบรหัสผ่าน - ใช้ password_verify() เท่านั้น
        $password_valid = false;
        
        // ถ้ารหัสผ่านเป็น hashed (เริ่มต้นด้วย $2y$ หรือ $2a$ หรือ $2b$)
        if (preg_match('/^\$2[ayb]\$/', $row['std_password'])) {
            $password_valid = password_verify($password, $row['std_password']);
        } else {
            // ถ้ายังเป็น plain text (ไม่ควรใช้ แต่รองรับเพื่อ migration)
            // เปรียบเทียบและ hash ใหม่ทันที
            if ($password === $row['std_password']) {
                $password_valid = true;
                // Hash รหัสผ่านใหม่ทันที
                $new_hash = password_hash($password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE tb_student SET std_password = ? WHERE std_id = ?");
                $update_stmt->bind_param("ss", $new_hash, $row['std_id']);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
        
        if ($password_valid) {
            $_SESSION['user_id'] = $row['std_id'];
            $_SESSION['user_name'] = $row['std_name'];
            $_SESSION['user_type'] = 'student';
            $stmt->close();
            header('Location: ../student/index.php');
            exit();
        }
    }
    $stmt->close();
}

header('Location: ../login.php?error=invalid');
exit();