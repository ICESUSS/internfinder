<?php
session_start();
include __DIR__ . '/../../config.php';

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// ตรวจสอบ parameter
if (!isset($_GET['id'])) {
    header("Location: student_list.php");
    exit;
}

$std_id = $_GET['id'];

/* ===============================
   ตรวจสอบนักศึกษามีอยู่จริง
================================ */
$chk_student = mysqli_query($conn, "SELECT std_id FROM tb_student WHERE std_id='$std_id'");
if (mysqli_num_rows($chk_student) == 0) {
    header("Location: student_list.php?msg=notfound");
    exit;
}

/* ===============================
   ตรวจสอบประวัติฝึกงาน
================================ */
$chk_intern = mysqli_query($conn, 
    "SELECT intern_id FROM tb_internship WHERE std_id='$std_id' LIMIT 1"
);

if (mysqli_num_rows($chk_intern) > 0) {
    // มีประวัติฝึกงาน → ห้ามลบ
    header("Location: student_list.php?msg=has_internship");
    exit;
}

/* ===============================
   ลบข้อมูลนักศึกษา
================================ */
$sql = "DELETE FROM tb_student WHERE std_id='$std_id'";
if (mysqli_query($conn, $sql)) {
    header("Location: student_list.php?msg=deleted");
} else {
    header("Location: student_list.php?msg=error");
}
exit;
