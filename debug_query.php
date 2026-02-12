<?php
include 'config.php';
$sql = "INSERT INTO tb_company (com_name, com_tel, com_email, com_contact, com_contact_pos, com_add_no, com_road, com_subdistrict, com_district, com_province, com_zipcode, com_detail) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo "Error: " . $conn->error;
} else {
    echo "Success: Prepare worked.";
}
?>
