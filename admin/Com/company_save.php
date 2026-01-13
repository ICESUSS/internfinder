<?php
include __DIR__ . '/../../config.php';

$com_id    = (int)$_POST['com_id'];
$com_name  = $_POST['com_name'] ?? '';
$com_detail = $_POST['com_detail'] ?? '';

$stmt = $conn->prepare("UPDATE tb_company SET com_name=?, com_detail=? WHERE com_id=?");
$stmt->bind_param("ssi", $com_name, $com_detail, $com_id);
$stmt->execute();

/* ==== อัปโหลดรูป ==== */
if (!empty($_FILES['com_img']['name'])) {

    $dir = __DIR__ . '/../../uploads/companies/';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $ext = strtolower(pathinfo($_FILES['com_img']['name'], PATHINFO_EXTENSION));
    $allow = ['jpg','jpeg','png','webp'];

    if (!in_array($ext, $allow)) {
        die("ชนิดไฟล์ไม่รองรับ");
    }

    if ($_FILES['com_img']['size'] > 2 * 1024 * 1024) {
        die("ไฟล์ใหญ่เกิน 2MB");
    }

    $newName = "company_" . $com_id . "." . $ext;
    move_uploaded_file($_FILES['com_img']['tmp_name'], $dir . $newName);

    $stmt = $conn->prepare("UPDATE tb_company SET com_img=? WHERE com_id=?");
    $stmt->bind_param("si", $newName, $com_id);
    $stmt->execute();
}

header("Location: ../company_detail.php?id=".$com_id);
