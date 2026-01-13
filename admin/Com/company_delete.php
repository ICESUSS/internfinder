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
$chk = mysqli_query($conn, "SELECT com_id FROM tb_company WHERE com_id='$com_id' LIMIT 1");
if (mysqli_num_rows($chk) == 0) {
    header("Location: ../company_list.php?msg=notfound");
    exit;
}

// ตรวจสอบการอ้างอิงจากตารางฝึกงาน
$chk_intern = mysqli_query($conn, "SELECT intern_id FROM tb_internship WHERE com_id='$com_id' LIMIT 1");
if (mysqli_num_rows($chk_intern) > 0) {
    header("Location: ../company_list.php?msg=has_internship");
    exit;
}

// ตรวจสอบการอ้างอิงจากนักศึกษาที่กำหนดสถานประกอบการ
$chk_student = mysqli_query($conn, "SELECT std_id FROM tb_student WHERE com_id='$com_id' LIMIT 1");
if (mysqli_num_rows($chk_student) > 0) {
    header("Location: ../company_list.php?msg=has_internship");
    exit;
}

// ลบข้อมูล
$sql = "DELETE FROM tb_company WHERE com_id='$com_id'";
if (mysqli_query($conn, $sql)) {
    header("Location: ../Com/company_list.php?msg=deleted");
} else {
    header("Location: ../Com/company_list.php?msg=error");
}
exit;