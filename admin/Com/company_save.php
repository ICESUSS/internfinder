<?php
session_start();
include __DIR__ . '/../../config.php';

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$com_id         = (int)$_POST['com_id'];
$com_name       = $_POST['com_name'] ?? '';
$com_tel        = $_POST['com_tel'] ?? '';
$com_email      = $_POST['com_email'] ?? '';
$com_contact    = $_POST['com_contact'] ?? '';
$com_contact_pos = $_POST['com_contact_pos'] ?? '';
$com_add_no     = $_POST['com_add_no'] ?? '';
$com_road       = $_POST['com_road'] ?? '';
$com_subdistrict = $_POST['com_subdistrict'] ?? '';
$com_district   = $_POST['com_district'] ?? '';
$com_province   = $_POST['com_province'] ?? '';
$com_zipcode    = $_POST['com_zipcode'] ?? '';
$com_detail     = $_POST['com_detail'] ?? '';

if ($com_id > 0) {
    // Update
    $sql = "UPDATE tb_company SET com_name=?, com_tel=?, com_email=?, com_contact=?, com_contact_pos=?, com_add_no=?, com_road=?, com_subdistrict=?, com_district=?, com_province=?, com_zipcode=?, com_detail=? WHERE com_id=?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) die("Prepare failed (Update): " . $conn->error . " | SQL: " . $sql);
    $stmt->bind_param("ssssssssssssi", $com_name, $com_tel, $com_email, $com_contact, $com_contact_pos, $com_add_no, $com_road, $com_subdistrict, $com_district, $com_province, $com_zipcode, $com_detail, $com_id);
    $stmt->execute();
} else {
    // Insert
    $sql = "INSERT INTO tb_company (com_name, com_tel, com_email, com_contact, com_contact_pos, com_add_no, com_road, com_subdistrict, com_district, com_province, com_zipcode, com_detail) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) die("Prepare failed (Insert): " . $conn->error . " | SQL: " . $sql);
    $stmt->bind_param("ssssssssssss", $com_name, $com_tel, $com_email, $com_contact, $com_contact_pos, $com_add_no, $com_road, $com_subdistrict, $com_district, $com_province, $com_zipcode, $com_detail);
    $stmt->execute();
    $com_id = $conn->insert_id;
}

/* ==== อัปโหลดรูป ==== */
if (!empty($_FILES['com_img']['name'])) {
    // Validate file upload
    if ($_FILES['com_img']['error'] === UPLOAD_ERR_OK) {
        $dir = __DIR__ . '/../../uploads/companies/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // Check file size (max 2MB)
        if ($_FILES['com_img']['size'] <= 2 * 1024 * 1024) {
            // Check MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['com_img']['tmp_name']);
            finfo_close($finfo);
            
            $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
            $ext = strtolower(pathinfo($_FILES['com_img']['name'], PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];

            if (in_array($mime, $allowed_mimes) && in_array($ext, $allowed_exts)) {
                $newName = "company_" . $com_id . "." . $ext;
                if (move_uploaded_file($_FILES['com_img']['tmp_name'], $dir . $newName)) {
                    $stmt = $conn->prepare("UPDATE tb_company SET com_img=? WHERE com_id=?");
                    $stmt->bind_param("si", $newName, $com_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }
}

header("Location: company_list.php?msg=" . ($com_id > 0 ? 'updated' : 'added'));
?>
