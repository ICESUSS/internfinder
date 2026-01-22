<?php
session_start();
include __DIR__ . '/../../config.php';

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

// รับ id นักศึกษา
if (!isset($_GET['id'])) {
    header("Location: student_list.php");
    exit;
}

$std_id = trim($_GET['id']);

/* ===============================
   ดึงข้อมูลนักศึกษา
================================ */
$stmt = $conn->prepare("SELECT * FROM tb_student WHERE std_id = ?");
if ($stmt) {
    $stmt->bind_param("s", $std_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $student = $res->fetch_assoc();
    $stmt->close();
} else {
    $student = null;
}

if (!$student) {
    header("Location: student_list.php");
    exit;
}

/* ===============================
   ดึงข้อมูลแผนก
================================ */
$deps_stmt = $conn->prepare("SELECT * FROM tb_department ORDER BY dep_name");
$deps = null;
if ($deps_stmt) {
    $deps_stmt->execute();
    $deps = $deps_stmt->get_result();
}

/* ===============================
   บันทึกข้อมูล
================================ */
    if (isset($_POST['save'])) {
        $std_name  = trim($_POST['std_name']);
        $std_lastname = trim($_POST['std_lastname']);
        $std_level = trim($_POST['std_level']);
        $std_room  = trim($_POST['std_room'] ?? '');
        $std_add   = trim($_POST['std_add'] ?? '');
        $std_add_no = trim($_POST['std_add_no'] ?? '');
        $std_road   = trim($_POST['std_road'] ?? '');
        $std_subdistrict = trim($_POST['std_subdistrict'] ?? '');
        $std_district    = trim($_POST['std_district'] ?? '');
        $std_province    = trim($_POST['std_province'] ?? '');
        $std_zipcode     = trim($_POST['std_zipcode'] ?? '');
        $std_tel   = trim($_POST['std_tel'] ?? '');
        $std_gmail = trim($_POST['std_gmail'] ?? '');
        $dep_id    = intval($_POST['dep_id']);

        $update_stmt = $conn->prepare("UPDATE tb_student SET
                    std_name=?, std_lastname=?, std_level=?, std_room=?, std_add=?,
                    std_add_no=?, std_road=?, std_subdistrict=?, std_district=?,
                    std_province=?, std_zipcode=?, std_tel=?, std_gmail=?, dep_id=?
                WHERE std_id=?");
        if ($update_stmt) {
            $update_stmt->bind_param("sssssssssssssis", 
                $std_name, $std_lastname, $std_level, $std_room, $std_add,
                $std_add_no, $std_road, $std_subdistrict, $std_district,
                $std_province, $std_zipcode, $std_tel, $std_gmail, $dep_id, $std_id);
            if ($update_stmt->execute()) {
                $update_stmt->close();
                header("Location: student_list.php?update=success");
                exit;
            }
            $update_stmt->close();
        }
    }
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>แก้ไขนักศึกษา</title>
<link rel="stylesheet" href="../../assets/css/mobile-responsive.css">
<style>
body { font-family: Tahoma; background:#f4f6f9; }
.container {
    width: 500px;
    margin: 30px auto;
    background: #fff;
    padding: 25px;
    border-radius: 10px;
}
input, select {
    width: 100%;
    padding: 8px;
    margin-bottom: 10px;
}
button {
    padding: 8px 14px;
    background: #2c3e50;
    color: #fff;
    border: none;
    border-radius: 5px;
}
.back {
    display:inline-block;
    margin-top:10px;
    text-decoration:none;
    color:#555;
}
</style>
</head>
<body>

<div class="container">
<h2>✏️ แก้ไขข้อมูลนักศึกษา</h2>

<form method="post">

<label>รหัสนักศึกษา</label>
<input value="<?= $student['std_id'] ?>" disabled>

<label>ชื่อ</label>
<input name="std_name" value="<?= $student['std_name'] ?>" required>

<label>นามสกุล</label>
<input name="std_lastname" value="<?= $student['std_lastname'] ?>" required>

<label>ระดับชั้น</label>
<select name="std_level">
    <option value="ปวช. 1" <?= $student['std_level']=='ปวช. 1'?'selected':'' ?>>ปวช. 1</option>
    <option value="ปวช. 2" <?= $student['std_level']=='ปวช. 2'?'selected':'' ?>>ปวช. 2</option>
    <option value="ปวช. 3" <?= $student['std_level']=='ปวช. 3'?'selected':'' ?>>ปวช. 3</option>
    <option value="ปวส. 1" <?= $student['std_level']=='ปวส. 1'?'selected':'' ?>>ปวส. 1</option>
    <option value="ปวส. 2" <?= $student['std_level']=='ปวส. 2'?'selected':'' ?>>ปวส. 2</option>
</select>

<label>ห้อง</label>
<input name="std_room" value="<?= $student['std_room'] ?>" placeholder="เช่น 1, 2, 3">

<label>ที่อยู่ปัจจุบัน (แสดงในใบคำร้อง)</label>
<div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
    <input name="std_add_no" value="<?= $student['std_add_no'] ?? '' ?>" placeholder="บ้านเลขที่">
    <input name="std_road" value="<?= $student['std_road'] ?? '' ?>" placeholder="ถนน">
    <input name="std_subdistrict" value="<?= $student['std_subdistrict'] ?? '' ?>" placeholder="ตำบล">
    <input name="std_district" value="<?= $student['std_district'] ?? '' ?>" placeholder="อำเภอ">
    <input name="std_province" value="<?= $student['std_province'] ?? '' ?>" placeholder="จังหวัด">
    <input name="std_zipcode" value="<?= $student['std_zipcode'] ?? '' ?>" placeholder="รหัสไปรษณีย์">
</div>

<label>ที่อยู่แบบเต็ม</label>
<input name="std_add" value="<?= $student['std_add'] ?>">

<label>เบอร์โทร</label>
<input name="std_tel" value="<?= $student['std_tel'] ?>">

<label>อีเมล</label>
<input name="std_gmail" value="<?= $student['std_gmail'] ?>">

<label>แผนก</label>
<select name="dep_id">
<?php
// Re-query departments to ensure proper ordering for grouping
$deps_group_stmt = $conn->prepare("SELECT * FROM tb_department ORDER BY dep_group, dep_name");
$current_group = "";
if ($deps_group_stmt) {
    $deps_group_stmt->execute();
    $deps_res = $deps_group_stmt->get_result();
    while($d = $deps_res->fetch_assoc()) {
        if ($current_group != ($d['dep_group'] ?? '')) {
            if ($current_group != "") echo "</optgroup>";
            $current_group = $d['dep_group'] ?? '';
            echo "<optgroup label='" . htmlspecialchars($current_group, ENT_QUOTES, 'UTF-8') . "'>";
        }
    ?>
        <option value="<?= htmlspecialchars($d['dep_id'], ENT_QUOTES, 'UTF-8') ?>"
            <?= $student['dep_id']==$d['dep_id']?'selected':'' ?>>
            <?= htmlspecialchars($d['dep_name'], ENT_QUOTES, 'UTF-8') ?>
        </option>
    <?php 
    }
    if ($current_group != "") echo "</optgroup>";
    $deps_group_stmt->close();
}
?>
</select>



<button type="submit" name="save">บันทึก</button>
</form>

<a class="back" href="student_list.php">← กลับหน้ารายชื่อ</a>
</div>

</body>
</html>
