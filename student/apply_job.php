<?php
session_start();
include('../config.php');

// ตรวจสอบสิทธิ์นักศึกษา
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: ../login.php");
    exit();
}

$std_id = $_SESSION['user_id'];
$detail_id = isset($_GET['id']) ? $_GET['id'] : 0;
$msg = '';
$msg_type = '';

// ดึงข้อมูลงานที่เลือกมาแสดง
$sql_job = "SELECT d.*, c.com_name, c.com_img 
            FROM tb_company_detail d 
            JOIN tb_company c ON d.com_id = c.com_id 
            WHERE d.detail_id = ?";
$stmt = $conn->prepare($sql_job);
$stmt->bind_param("i", $detail_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    echo "ไม่พบข้อมูลตำแหน่งงาน";
    exit();
}

// จัดการเมื่อกดปุ่มส่งใบสมัคร
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $note = $_POST['note'];
    $com_id = $job['com_id'];
    
    // ตรวจสอบว่าเคยสมัครงานนี้ไปหรือยัง (สถานะ pending หรือ approved)
    $check_sql = "SELECT * FROM tb_internship WHERE std_id = ? AND detail_id = ? AND status IN ('pending', 'approved')";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("si", $std_id, $detail_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $msg = "คุณได้สมัครตำแหน่งนี้ไปแล้ว หรือกำลังรอการตรวจสอบ";
        $msg_type = "error";
    } else {
        // จัดการอัปโหลดไฟล์
        $upload_dir = "../uploads/"; // สร้างโฟลเดอร์นี้ด้วยนะครับ
        
        // Resume
        $resume_name = "";
        if (!empty($_FILES['resume']['name'])) {
            $resume_ext = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
            $resume_name = "resume_" . $std_id . "_" . time() . "." . $resume_ext;
            move_uploaded_file($_FILES['resume']['tmp_name'], $upload_dir . $resume_name);
        }

        // Transcript
        $transcript_name = "";
        if (!empty($_FILES['transcript']['name'])) {
            $trans_ext = pathinfo($_FILES['transcript']['name'], PATHINFO_EXTENSION);
            $transcript_name = "trans_" . $std_id . "_" . time() . "." . $trans_ext;
            move_uploaded_file($_FILES['transcript']['tmp_name'], $upload_dir . $transcript_name);
        }

        // บันทึกลงฐานข้อมูล tb_internship
        $insert_sql = "INSERT INTO tb_internship (std_id, com_id, detail_id, resume_file, transcript_file, note, status) 
                       VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        $stmt_ins = $conn->prepare($insert_sql);
        $stmt_ins->bind_param("siisss", $std_id, $com_id, $detail_id, $resume_name, $transcript_name, $note);
        
        if ($stmt_ins->execute()) {
            $msg = "ส่งใบสมัครเรียบร้อยแล้ว! กรุณารอแอดมินตรวจสอบ";
            $msg_type = "success";
        } else {
            $msg = "เกิดข้อผิดพลาด: " . $conn->error;
            $msg_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยื่นใบสมัครฝึกงาน</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap');
        
        body { font-family: 'Sarabun', sans-serif; background: #f5f7fa; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        
        .job-header { display: flex; align-items: center; gap: 20px; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .job-header img { width: 80px; height: 80px; object-fit: cover; border-radius: 8px; }
        .job-title { font-size: 22px; font-weight: 600; color: #1E88E5; }
        .company-name { font-size: 16px; color: #666; margin-top: 5px; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        textarea.form-control { height: 100px; resize: vertical; }
        
        .btn-submit { background: #1E88E5; color: white; padding: 12px 25px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; width: 100%; transition: 0.2s; }
        .btn-submit:hover { background: #1565C0; }
        
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; }
        .alert-success { background: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        .alert-error { background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        
        .file-hint { font-size: 13px; color: #888; margin-top: 5px; }
    </style>
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
</head>
<body>

<div class="container">
    <?php if ($msg): ?>
        <div class="alert alert-<?php echo $msg_type; ?>">
            <?php echo $msg; ?>
        </div>
        <?php if ($msg_type == 'success'): ?>
            <p style="text-align: center;"><a href="dashboard.php">กลับหน้าหลัก</a></p>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($msg_type != 'success'): ?>
    <div class="job-header">
        <img src="../uploads/company_img/<?php echo !empty($job['com_img']) ? $job['com_img'] : 'default.png'; ?>" alt="Logo">
        <div>
            <div class="job-title"><?php echo htmlspecialchars($job['job_title']); ?></div>
            <div class="company-name">🏢 <?php echo htmlspecialchars($job['com_name']); ?></div>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label>ชื่อ-สกุล (ผู้สมัคร)</label>
            <input type="text" class="form-control" value="<?php echo $_SESSION['user_name']; ?>" disabled>
        </div>

        <div class="form-group">
            <label for="resume">📄 อัปโหลด Resume (PDF เท่านั้น)</label>
            <input type="file" name="resume" id="resume" class="form-control" accept=".pdf" required>
            <div class="file-hint">* จำเป็นต้องมี Resume เพื่อให้ผู้ประกอบการพิจารณา</div>
        </div>

        <div class="form-group">
            <label for="transcript">📊 อัปโหลดผลการเรียน (Transcript)</label>
            <input type="file" name="transcript" id="transcript" class="form-control" accept=".pdf, .jpg, .png">
        </div>

        <div class="form-group">
            <label for="note">📝 ข้อความเพิ่มเติม (ถ้ามี)</label>
            <textarea name="note" id="note" class="form-control" placeholder="แนะนำตัวสั้นๆ หรือบอกเหตุผลที่อยากฝึกงานที่นี่..."></textarea>
        </div>

        <button type="submit" class="btn-submit">🚀 ส่งใบสมัคร</button>
    </form>
    <?php endif; ?>
</div>

</body>
</html>