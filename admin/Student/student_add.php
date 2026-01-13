<?php
session_start();
include __DIR__ . '/../../config.php';

// ตรวจสอบ admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$message = "";

/* ===============================
   IMPORT CSV
================================ */
if (isset($_POST['import_csv'])) {

    if ($_FILES['csv_file']['error'] === 0) {

        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        fgetcsv($file); // ข้าม header

        $success = 0;
        $fail = 0;

        while (($row = fgetcsv($file, 1000, ",")) !== FALSE) {

            $std_id       = trim($row[0]);
            $std_password = trim($row[1]);
            $std_name     = trim($row[2]);
            $std_add      = trim($row[3]);
            $std_tel      = trim($row[4]);
            $std_gmail    = trim($row[5]);
            $dep_id       = trim($row[6]);
            $std_level    = trim($row[7]);

            if ($std_id == "" || $std_name == "") {
                $fail++;
                continue;
            }

            // เช็คซ้ำ
            $check = mysqli_query($conn, "SELECT std_id FROM tb_student WHERE std_id='$std_id'");
            if (mysqli_num_rows($check) > 0) {
                $fail++;
                continue;
            }

            $sql = "INSERT INTO tb_student 
                    (std_id, std_password, std_name, std_add, std_tel, std_gmail, dep_id, std_level)
                    VALUES 
                    ('$std_id','$std_password','$std_name','$std_add','$std_tel','$std_gmail','$dep_id', '$std_level')";

            if (mysqli_query($conn, $sql)) {
                $success++;
            } else {
                $fail++;
            }
        }
        fclose($file);

        $message = "✅ นำเข้า CSV สำเร็จ: $success รายการ | ❌ ล้มเหลว: $fail รายการ";
    }
}

/* ===============================
   ADD SINGLE STUDENT
================================ */
if (isset($_POST['add_student'])) {

    $std_id       = $_POST['std_id'];
    $std_password = $_POST['std_password'];
    $std_name     = $_POST['std_name'];
    $std_add      = $_POST['std_add'];
    $std_tel      = $_POST['std_tel'];
    $std_gmail    = $_POST['std_gmail'];
    $std_level    = $_POST['std_level'];
    $dep_id       = $_POST['dep_id'];

    $sql = "INSERT INTO tb_student 
            (std_id, std_password, std_name, std_level, std_add, std_tel, std_gmail, dep_id)
            VALUES 
            ('$std_id','$std_password','$std_name','$std_level','$std_add','$std_tel','$std_gmail','$dep_id')";

    if (mysqli_query($conn, $sql)) {
        $message = "✅ เพิ่มนักศึกษาสำเร็จ";
    } else {
        $message = "❌ เพิ่มไม่สำเร็จ";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>เพิ่มนักศึกษา</title>
<link rel="stylesheet" href="../../assets/css/mobile-responsive.css">
<style>
input, select { padding:6px; width:100%; margin-bottom:8px; }
button { padding:8px 14px; }
.box { background:#fff; padding:20px; border-radius:8px; margin-bottom:20px; }
</style>
</head>
<body>

<h2>เพิ่มนักศึกษา (Admin)</h2>
<p style="color:green;"><?= $message ?></p>
<p><a href="../dashboard.php"><button type="button">← กลับไป Dashboard</button></a></p>
<p style="color:green;"><?= $message ?></p>

<div class="box">
<h3>📥 Import CSV</h3>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="csv_file" accept=".csv" required>
    <button type="submit" name="import_csv">นำเข้า CSV</button>
</form>
</div>

<div class="box">

<h3>➕ เพิ่มรายคน</h3>
<form method="post">
<input name="std_id" placeholder="รหัสนักศึกษา" required>
<input name="std_password" placeholder="รหัสผ่าน">
<input name="std_name" placeholder="ชื่อ-นามสกุล" required>
<input name="std_add" placeholder="ที่อยู่">
<input name="std_tel" placeholder="เบอร์โทร">
<input name="std_gmail" placeholder="อีเมล">
<select name="std_level" required>
    <option value="">-- เลือกระดับชั้น --</option>
    <option value="ปวช. 1">ปวช. 1</option>
    <option value="ปวช. 2">ปวช. 2</option>
    <option value="ปวช. 3">ปวช. 3</option>
    <option value="ปวส. 1">ปวส. 1</option>
    <option value="ปวส. 2">ปวส. 2</option>
</select>
<select name="dep_id" required>
    <option value="">-- เลือกแผนกวิชา --</option>
    <?php
    $sql_dep = "SELECT * FROM tb_department ORDER BY dep_group, dep_name";
    $res_dep = mysqli_query($conn, $sql_dep);
    $current_group = "";
    while ($row_dep = mysqli_fetch_assoc($res_dep)) {
        if ($current_group != $row_dep['dep_group']) {
            if ($current_group != "") echo "</optgroup>";
            $current_group = $row_dep['dep_group'];
            echo "<optgroup label='$current_group'>";
        }
        echo "<option value='{$row_dep['dep_id']}'>{$row_dep['dep_name']}</option>";
    }
    if ($current_group != "") echo "</optgroup>";
    ?>
</select>
<button type="submit" name="add_student">บันทึก</button>
</form>
</div>

</body>
</html>
