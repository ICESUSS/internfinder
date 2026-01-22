<?php
session_start();
include(__DIR__ . '/../../config.php');

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: ../company_list.php");
    exit;
} 

$com_id = (int)$_GET['id'];

// ตรวจสอบว่ามีบริษัทหรือไม่
$chk_stmt = $conn->prepare("SELECT com_id FROM tb_company WHERE com_id = ? LIMIT 1");
if ($chk_stmt) {
    $chk_stmt->bind_param("i", $com_id);
    $chk_stmt->execute();
    $chk_res = $chk_stmt->get_result();
    if ($chk_res->num_rows == 0) {
        $chk_stmt->close();
        header("Location: ../company_list.php?msg=notfound");
        exit;
    }
    $chk_stmt->close();
}

// ตรวจสอบการอ้างอิงจากตารางฝึกงาน
$chk_intern_stmt = $conn->prepare("SELECT intern_id FROM tb_internship WHERE com_id = ? LIMIT 1");
if ($chk_intern_stmt) {
    $chk_intern_stmt->bind_param("i", $com_id);
    $chk_intern_stmt->execute();
    $chk_intern_res = $chk_intern_stmt->get_result();
    if ($chk_intern_res->num_rows > 0) {
        $chk_intern_stmt->close();
        header("Location: ../company_list.php?msg=has_internship");
        exit;
    }
    $chk_intern_stmt->close();
}

// ตรวจสอบการอ้างอิงจากนักศึกษาที่กำหนดสถานประกอบการ
$chk_student_stmt = $conn->prepare("SELECT std_id FROM tb_student WHERE com_id = ? LIMIT 1");
if ($chk_student_stmt) {
    $chk_student_stmt->bind_param("i", $com_id);
    $chk_student_stmt->execute();
    $chk_student_res = $chk_student_stmt->get_result();
    if ($chk_student_res->num_rows > 0) {
        $chk_student_stmt->close();
        header("Location: ../company_list.php?msg=has_internship");
        exit;
    }
    $chk_student_stmt->close();
}

// ลบข้อมูล
$delete_stmt = $conn->prepare("DELETE FROM tb_company WHERE com_id = ?");
if ($delete_stmt) {
    $delete_stmt->bind_param("i", $com_id);
    if ($delete_stmt->execute()) {
        $delete_stmt->close();
        header("Location: ../Com/company_list.php?msg=deleted");
    } else {
        $delete_stmt->close();
        header("Location: ../Com/company_list.php?msg=error");
    }
} else {
    header("Location: ../Com/company_list.php?msg=error");
}
exit;