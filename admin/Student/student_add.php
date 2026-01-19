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
   📥 IMPORT CSV
================================ */
if (isset($_POST['import_csv'])) {

    if ($_FILES['csv_file']['error'] === 0) {
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        fgetcsv($file); // ข้ามบรรทัด Header 1 บรรทัด

        $success = 0;
        $fail = 0;

        while (($row = fgetcsv($file, 1000, ",")) !== FALSE) {
            // ลำดับคอลัมน์ต้องตรงกับไฟล์ CSV (std_id, name, pass, level, tel, gmail, address, dep_id, room)
            // ใช้ mysqli_real_escape_string เพื่อป้องกัน Error จากเครื่องหมาย ' หรือ " ในข้อความ
            $std_id       = mysqli_real_escape_string($conn, trim($row[0]));
            $std_name     = mysqli_real_escape_string($conn, trim($row[1]));
            $std_password = password_hash(trim($row[2]), PASSWORD_DEFAULT);
            $std_level    = mysqli_real_escape_string($conn, trim($row[3]));
            $std_tel      = mysqli_real_escape_string($conn, trim($row[4]));
            $std_gmail    = mysqli_real_escape_string($conn, trim($row[5]));
            $std_add  = mysqli_real_escape_string($conn, trim($row[6])); // แก้เป็น std_add
            $dep_id       = intval(trim($row[7])); // แปลงเป็นตัวเลข
            $std_room     = isset($row[8]) ? mysqli_real_escape_string($conn, trim($row[8])) : '';

            // ตรวจสอบค่าว่างที่จำเป็น
            if ($std_id == "" || $std_name == "") {
                $fail++;
                continue;
            }

            // เช็คว่ารหัสนักศึกษาซ้ำหรือไม่
            $check = mysqli_query($conn, "SELECT std_id FROM tb_student WHERE std_id='$std_id'");
            if (mysqli_num_rows($check) > 0) {
                $fail++; 
                continue; // ข้ามคนนี้ไป
            }

            // Split Name for CSV
            $parts = explode(' ', $std_name, 2);
            $fname = $parts[0];
            $lname = isset($parts[1]) ? $parts[1] : '';

            // SQL INSERT (ใช้ std_address)
            $sql = "INSERT INTO tb_student 
                    (std_id, std_password, std_name, std_lastname, std_add, std_tel, std_gmail, dep_id, std_level, std_room)
                    VALUES 
                    ('$std_id','$std_password','$fname','$lname','$std_add','$std_tel','$std_gmail','$dep_id', '$std_level', '$std_room')";

            if (mysqli_query($conn, $sql)) {
                $success++;
            } else {
                $fail++; // อาจจะ fail ถ้า dep_id ไม่ตรงกับตาราง tb_department
            }
        }
        fclose($file);

        $message = "✅ นำเข้า CSV สำเร็จ: $success รายการ | ❌ ล้มเหลว/ข้าม: $fail รายการ";
    }
}

/* ===============================
   ➕ ADD SINGLE STUDENT
================================ */
if (isset($_POST['add_student'])) {
    // รับค่าและป้องกัน SQL Injection
    $std_id       = mysqli_real_escape_string($conn, $_POST['std_id']);
    $std_password = password_hash($_POST['std_password'], PASSWORD_DEFAULT);
    $std_name     = mysqli_real_escape_string($conn, $_POST['std_name']);
    $std_add     = mysqli_real_escape_string($conn, $_POST['std_add']); // รับค่า address
    $std_tel      = mysqli_real_escape_string($conn, $_POST['std_tel']);
    $std_gmail    = mysqli_real_escape_string($conn, $_POST['std_gmail']);
    $std_level    = mysqli_real_escape_string($conn, $_POST['std_level']);
    $std_room     = mysqli_real_escape_string($conn, $_POST['std_room']);
    $dep_id       = intval($_POST['dep_id']);

    // เช็คซ้ำก่อนบันทึก
    $check_dup = mysqli_query($conn, "SELECT std_id FROM tb_student WHERE std_id = '$std_id'");
    if(mysqli_num_rows($check_dup) > 0){
        $message = "❌ รหัสนักศึกษานี้มีอยู่แล้ว";
    } else {
                 $std_lastname = mysqli_real_escape_string($conn, $_POST['std_lastname']);

        $sql = "INSERT INTO tb_student 
                (std_id, std_password, std_name, std_lastname, std_level, std_room, std_add, std_tel, std_gmail, dep_id)
                VALUES 
                ('$std_id','$std_password','$std_name','$std_lastname','$std_level','$std_room','$std_add','$std_tel','$std_gmail','$dep_id')";

        if (mysqli_query($conn, $sql)) {
            $message = "✅ เพิ่มนักศึกษาเรียบร้อยแล้ว";
        } else {
            $message = "❌ เกิดข้อผิดพลาด: " . mysqli_error($conn);
        }
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
    body { background-color: #f4f6f9; font-family: sans-serif; padding: 20px; }
    input, select { padding:10px; width:100%; margin-bottom:15px; border:1px solid #ddd; border-radius:5px; box-sizing: border-box; }
    button { padding:10px 20px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; }
    button:hover { background-color: #218838; }
    .btn-back { background-color: #6c757d; margin-bottom: 15px; }
    .box { background:#fff; padding:30px; border-radius:8px; margin-bottom:20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
    h2, h3 { margin-top: 0; color: #333; }
    .alert { padding: 15px; margin-bottom: 20px; border-radius: 5px; font-weight: bold; }
    .alert-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .alert-danger { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
</style>
</head>
<body>

<div class="container">
    <h2>👨‍🎓 เพิ่มข้อมูลนักศึกษา</h2>
    
    <a href="../index.php"><button type="button" class="btn-back">← กลับหน้า Dashboard</button></a>

    <?php if($message): ?>
        <div class="alert <?= strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <div class="box">
        <h3>📥 Import ไฟล์ CSV</h3>
        <p style="font-size: 14px; color: #666;">
            <strong>รูปแบบไฟล์ (เรียงคอลัมน์):</strong><br>
            รหัส, ชื่อ-สกุล, รหัสผ่าน, ระดับชั้น, เบอร์โทร, อีเมล, ที่อยู่, ID แผนก, ห้อง(ถ้ามี)
        </p>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required>
            <button type="submit" name="import_csv">นำเข้า CSV</button>
        </form>
    </div>

    <div class="box">
        <h3>➕ เพิ่มรายคน (Manual)</h3>
        <form method="post">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <label>รหัสนักศึกษา *</label>
                    <input name="std_id" required>
                </div>
                <div>
                    <label>รหัสผ่าน (ถ้าไม่ใส่ ระบบจะตั้ง Default)</label>
                    <input name="std_password" placeholder="1234">
                </div>
                <div>
                    <label>ชื่อ *</label>
                    <input name="std_name" required>
                </div>
                <div>
                    <label>นามสกุล *</label>
                    <input name="std_lastname" required>
                </div>
                <div>
                    <label>ระดับชั้น *</label>
                    <select name="std_level" required>
                        <option value="">-- เลือก --</option>
                        <option value="ปวช. 1">ปวช. 1</option>
                        <option value="ปวช. 2">ปวช. 2</option>
                        <option value="ปวช. 3">ปวช. 3</option>
                        <option value="ปวส. 1">ปวส. 1</option>
                        <option value="ปวส. 2">ปวส. 2</option>
                    </select>
                </div>
                <div>
                    <label>เบอร์โทรศัพท์</label>
                    <input name="std_tel">
                </div>
                <div>
                    <label>อีเมล (Gmail)</label>
                    <input name="std_gmail" type="email">
                </div>
                <div>
                    <label>แผนกวิชา *</label>
                    <select name="dep_id" required>
                        <option value="">-- เลือกแผนกวิชา --</option>
                        <?php
                        // ดึงเฉพาะ dep_id และ dep_name เพื่อป้องกัน error หากไม่มี dep_group
                        $sql_dep = "SELECT * FROM tb_department ORDER BY dep_name ASC";
                        $res_dep = mysqli_query($conn, $sql_dep);
                        while ($row_dep = mysqli_fetch_assoc($res_dep)) {
                            echo "<option value='{$row_dep['dep_id']}'>{$row_dep['dep_name']}</option>";
                        }
                        ?>
                    </select>
                </div>
                <div>
                    <label>ห้องเรียน (เช่น 1, 2)</label>
                    <input name="std_room">
                </div>
            </div>
            
            <label>ที่อยู่</label>
            <input name="std_add" placeholder="บ้านเลขที่ ตำบล อำเภอ จังหวัด"> <button type="submit" name="add_student" style="width: 100%; margin-top: 10px;">บันทึกข้อมูล</button>
        </form>
    </div>
</div>

</body>
</html>