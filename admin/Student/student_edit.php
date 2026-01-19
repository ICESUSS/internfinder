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

$std_id = $_GET['id'];

/* ===============================
   ดึงข้อมูลนักศึกษา
================================ */
$sql = "SELECT * FROM tb_student WHERE std_id='$std_id'";
$res = mysqli_query($conn, $sql);
$student = mysqli_fetch_assoc($res);

if (!$student) {
    header("Location: student_list.php");
    exit;
}

/* ===============================
   ดึงข้อมูลแผนก
================================ */
$deps = mysqli_query($conn, "SELECT * FROM tb_department");

/* ===============================
   บันทึกข้อมูล
================================ */
    if (isset($_POST['save'])) {
    
        $std_name  = $_POST['std_name'];
        $std_level = $_POST['std_level'];
        $std_room  = $_POST['std_room'];
        $std_add   = $_POST['std_add'];
        $std_tel   = $_POST['std_tel'];
        $std_gmail = $_POST['std_gmail'];
        $dep_id    = $_POST['dep_id'];
    
        $std_name  = $_POST['std_name'];
        $std_lastname = $_POST['std_lastname'];

        $sql = "UPDATE tb_student SET
                    std_name='$std_name',
                    std_lastname='$std_lastname',
                    std_level='$std_level',
                    std_room='$std_room',
                    std_add='$std_add',
                    std_tel='$std_tel',
                    std_gmail='$std_gmail',
                    dep_id='$dep_id'
                WHERE std_id='$std_id'";
    
        if (mysqli_query($conn, $sql)) {
            header("Location: student_list.php?update=success");
            exit;
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

<label>ที่อยู่</label>
<input name="std_add" value="<?= $student['std_add'] ?>">

<label>เบอร์โทร</label>
<input name="std_tel" value="<?= $student['std_tel'] ?>">

<label>อีเมล</label>
<input name="std_gmail" value="<?= $student['std_gmail'] ?>">

<label>แผนก</label>
<select name="dep_id">
<?php
// Re-query departments to ensure proper ordering for grouping
$deps = mysqli_query($conn, "SELECT * FROM tb_department ORDER BY dep_group, dep_name");
$current_group = "";
while($d = mysqli_fetch_assoc($deps)) {
    if ($current_group != $d['dep_group']) {
        if ($current_group != "") echo "</optgroup>";
        $current_group = $d['dep_group'];
        echo "<optgroup label='$current_group'>";
    }
?>
    <option value="<?= $d['dep_id'] ?>"
        <?= $student['dep_id']==$d['dep_id']?'selected':'' ?>>
        <?= $d['dep_name'] ?>
    </option>
<?php } 
if ($current_group != "") echo "</optgroup>";
?>
</select>



<button type="submit" name="save">บันทึก</button>
</form>

<a class="back" href="student_list.php">← กลับหน้ารายชื่อ</a>
</div>

</body>
</html>
