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

// Student data fetching removed

// ตรวจสอบจำนวนที่รับ (Capacity)
$capacity = isset($job['job_capacity']) ? (int)$job['job_capacity'] : 0;
$applied_count = 0;

if ($capacity > 0) {
    $cap_sql = "SELECT COUNT(*) as cnt FROM tb_internship WHERE detail_id = ? AND status = 'approved'";
    $cap_stmt = $conn->prepare($cap_sql);
    $cap_stmt->bind_param("i", $detail_id);
    $cap_stmt->execute();
    $cap_res = $cap_stmt->get_result()->fetch_assoc();
    $applied_count = (int)$cap_res['cnt'];
}

$is_full = ($capacity > 0 && $applied_count >= $capacity);

// ตรวจสอบ Mode: Edit (แก้ไขใบสมัคร)
$mode = isset($_GET['mode']) ? $_GET['mode'] : '';
$existing_app = null;

// ตรวจสอบว่ามีใบสมัครเดิมหรือไม่
$check_sql = "SELECT * FROM tb_internship WHERE std_id = ? AND detail_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("si", $std_id, $detail_id);
$check_stmt->execute();
$res_check = $check_stmt->get_result();

if ($res_check->num_rows > 0) {
    $existing_app = $res_check->fetch_assoc();
    
    // ถ้าสถานะไม่ใช่ pending ห้ามแก้ไข (Lock data)
    if ($existing_app['status'] !== 'pending') {
        if ($mode == 'edit') {
            // Allow editing even if approved/rejected to correct document data
            $msg = "หมายเหตุ: คุณกำลังแก้ไขข้อมูลในใบสมัครที่อยู่ในสถานะ <b>" . htmlspecialchars($existing_app['status']) . "</b>";
            $msg_type = "warning";
        } else {
            // If not in edit mode but already applied
            $msg = "คุณได้สมัครตำแหน่งนี้แล้ว (สถานะ: " . htmlspecialchars($existing_app['status']) . ") <a href='?id=$detail_id&mode=edit' class='w3-text-blue'>แก้ไขข้อมูลใบสมัคร/คำร้อง</a>";
            $msg_type = "info";
        }
    } elseif ($mode == 'edit') {
        // Normal edit for pending
    } else {
        $msg = "คุณได้สมัครตำแหน่งนี้แล้ว <a href='?id=$detail_id&mode=edit' class='w3-text-blue'>แก้ไขใบสมัคร</a>";
        $msg_type = "warning";
    }
} else {
    // ไม่มีใบสมัครเดิม - ตรวจสอบว่าตำแหน่งเต็มหรือไม่
    if ($is_full) {
        $msg = "ตำแหน่งนี้รับเต็มแล้ว ($applied_count/$capacity คน) ไม่สามารถสมัครได้";
        $msg_type = "error";
    }
}

// จัดการเมื่อกดปุ่มส่งใบสมัคร
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Removal of strict status check on POST to allow editing document data

    $note = $_POST['note'];
    $parent_name = $_POST['parent_name'];
    $parent_relation = $_POST['parent_relation'];
    $parent_tel = $_POST['parent_tel'];
    $parent_id_card = $_POST['parent_id_card'];
    $parent_address = $_POST['parent_address'];
    $gpax = $_POST['gpax'];
    $request_type = $_POST['request_type'];
    $contact_name = $_POST['contact_name'] ?? '';
    $term = $_POST['term'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    $com_id = $job['com_id'];
    $upload_dir = "../uploads/";

    // Logic สำหรับ UPDATE
    if ($existing_app) {
        $resume_name = $existing_app['resume_file'];
        $transcript_name = $existing_app['transcript_file'];

        // Check New Resume
        if (!empty($_FILES['resume']['name'])) {
            // Validate file upload
            if ($_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
                $msg = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์ Resume";
                $msg_type = "error";
            } else {
                // Check file size (max 5MB)
                if ($_FILES['resume']['size'] > 5 * 1024 * 1024) {
                    $msg = "ไฟล์ Resume ใหญ่เกินไป (สูงสุด 5MB)";
                    $msg_type = "error";
                } else {
                    // Check MIME type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $_FILES['resume']['tmp_name']);
                    finfo_close($finfo);
                    
                    $allowed_mimes = ['application/pdf'];
                    $resume_ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
                    
                    if (!in_array($mime, $allowed_mimes) || $resume_ext !== 'pdf') {
                        $msg = "ไฟล์ Resume ต้องเป็น PDF เท่านั้น";
                        $msg_type = "error";
                    } else {
                        $resume_name = "resume_" . $std_id . "_" . time() . "." . $resume_ext;
                        if (!move_uploaded_file($_FILES['resume']['tmp_name'], $upload_dir . $resume_name)) {
                            $msg = "ไม่สามารถบันทึกไฟล์ Resume ได้";
                            $msg_type = "error";
                        }
                    }
                }
            }
        }

        // Check New Transcript
        if (!empty($_FILES['transcript']['name']) && (!isset($msg) || $msg_type !== 'error')) {
            // Validate file upload
            if ($_FILES['transcript']['error'] !== UPLOAD_ERR_OK) {
                $msg = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์ Transcript";
                $msg_type = "error";
            } else {
                // Check file size (max 5MB)
                if ($_FILES['transcript']['size'] > 5 * 1024 * 1024) {
                    $msg = "ไฟล์ Transcript ใหญ่เกินไป (สูงสุด 5MB)";
                    $msg_type = "error";
                } else {
                    // Check MIME type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $_FILES['transcript']['tmp_name']);
                    finfo_close($finfo);
                    
                    $allowed_mimes = ['application/pdf', 'image/jpeg', 'image/png'];
                    $trans_ext = strtolower(pathinfo($_FILES['transcript']['name'], PATHINFO_EXTENSION));
                    $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];
                    
                    if (!in_array($mime, $allowed_mimes) || !in_array($trans_ext, $allowed_exts)) {
                        $msg = "ไฟล์ Transcript ต้องเป็น PDF, JPG หรือ PNG เท่านั้น";
                        $msg_type = "error";
                    } else {
                        $transcript_name = "trans_" . $std_id . "_" . time() . "." . $trans_ext;
                        if (!move_uploaded_file($_FILES['transcript']['tmp_name'], $upload_dir . $transcript_name)) {
                            $msg = "ไม่สามารถบันทึกไฟล์ Transcript ได้";
                            $msg_type = "error";
                        }
                    }
                }
            }
        }
        
        // ถ้ามี error ในการอัปโหลด ให้หยุดการทำงาน
        if (!isset($msg) || $msg_type !== 'error') {
            $update_sql = "UPDATE tb_internship SET resume_file=?, transcript_file=?, note=?, parent_name=?, parent_relation=?, parent_tel=?, parent_id_card=?, parent_address=?, gpax=?, request_type=?, contact_name=?, term=?, start_date=?, end_date=? WHERE intern_id=?";
            $stmt_up = $conn->prepare($update_sql);
            $stmt_up->bind_param("ssssssssssssssi", $resume_name, $transcript_name, $note, $parent_name, $parent_relation, $parent_tel, $parent_id_card, $parent_address, $gpax, $request_type, $contact_name, $term, $start_date, $end_date, $existing_app['intern_id']);
            
            if ($stmt_up->execute()) {
                $msg = "บันทึกการแก้ไขเรียบร้อยแล้ว!";
                $msg_type = "success";
                // Refresh data
                $existing_app['note'] = $note;
                $existing_app['resume_file'] = $resume_name;
                $existing_app['transcript_file'] = $transcript_name;
                $existing_app['parent_tel'] = $parent_tel;
                $existing_app['gpax'] = $gpax;
                $existing_app['request_type'] = $request_type;
                $existing_app['contact_name'] = $contact_name;
                $existing_app['term'] = $term;
                $existing_app['start_date'] = $start_date;
                $existing_app['end_date'] = $end_date;
            } else {
                $msg = "เกิดข้อผิดพลาด: " . $conn->error;
                $msg_type = "error";
            }
            $stmt_up->close();
        }

    } else {
        // Logic สำหรับ INSERT (สมัครใหม่)
        
        // Resume
        $resume_name = "";
        if (!empty($_FILES['resume']['name'])) {
            // Validate file upload
            if ($_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
                $msg = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์ Resume";
                $msg_type = "error";
            } else {
                // Check file size (max 5MB)
                if ($_FILES['resume']['size'] > 5 * 1024 * 1024) {
                    $msg = "ไฟล์ Resume ใหญ่เกินไป (สูงสุด 5MB)";
                    $msg_type = "error";
                } else {
                    // Check MIME type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $_FILES['resume']['tmp_name']);
                    finfo_close($finfo);
                    
                    $allowed_mimes = ['application/pdf'];
                    $resume_ext = strtolower(pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION));
                    
                    if (!in_array($mime, $allowed_mimes) || $resume_ext !== 'pdf') {
                        $msg = "ไฟล์ Resume ต้องเป็น PDF เท่านั้น";
                        $msg_type = "error";
                    } else {
                        $resume_name = "resume_" . $std_id . "_" . time() . "." . $resume_ext;
                        if (!move_uploaded_file($_FILES['resume']['tmp_name'], $upload_dir . $resume_name)) {
                            $msg = "ไม่สามารถบันทึกไฟล์ Resume ได้";
                            $msg_type = "error";
                        }
                    }
                }
            }
        }

        // Transcript
        $transcript_name = "";
        if (!empty($_FILES['transcript']['name']) && (!isset($msg) || $msg_type !== 'error')) {
            // Validate file upload
            if ($_FILES['transcript']['error'] !== UPLOAD_ERR_OK) {
                $msg = "เกิดข้อผิดพลาดในการอัปโหลดไฟล์ Transcript";
                $msg_type = "error";
            } else {
                // Check file size (max 5MB)
                if ($_FILES['transcript']['size'] > 5 * 1024 * 1024) {
                    $msg = "ไฟล์ Transcript ใหญ่เกินไป (สูงสุด 5MB)";
                    $msg_type = "error";
                } else {
                    // Check MIME type
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $_FILES['transcript']['tmp_name']);
                    finfo_close($finfo);
                    
                    $allowed_mimes = ['application/pdf', 'image/jpeg', 'image/png'];
                    $trans_ext = strtolower(pathinfo($_FILES['transcript']['name'], PATHINFO_EXTENSION));
                    $allowed_exts = ['pdf', 'jpg', 'jpeg', 'png'];
                    
                    if (!in_array($mime, $allowed_mimes) || !in_array($trans_ext, $allowed_exts)) {
                        $msg = "ไฟล์ Transcript ต้องเป็น PDF, JPG หรือ PNG เท่านั้น";
                        $msg_type = "error";
                    } else {
                        $transcript_name = "trans_" . $std_id . "_" . time() . "." . $trans_ext;
                        if (!move_uploaded_file($_FILES['transcript']['tmp_name'], $upload_dir . $transcript_name)) {
                            $msg = "ไม่สามารถบันทึกไฟล์ Transcript ได้";
                            $msg_type = "error";
                        }
                    }
                }
            }
        }
        
        // ถ้ามี error ในการอัปโหลด ให้หยุดการทำงาน
        if (isset($msg) && $msg_type === 'error') {
            // Skip insert
        } else {
            // บันทึกลงฐานข้อมูล tb_internship
            $insert_sql = "INSERT INTO tb_internship (std_id, com_id, detail_id, resume_file, transcript_file, note, parent_name, parent_relation, parent_tel, parent_id_card, parent_address, gpax, request_type, contact_name, term, start_date, end_date, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')";
            $stmt_ins = $conn->prepare($insert_sql);
            $stmt_ins->bind_param("siissssssssssssss", $std_id, $com_id, $detail_id, $resume_name, $transcript_name, $note, $parent_name, $parent_relation, $parent_tel, $parent_id_card, $parent_address, $gpax, $request_type, $contact_name, $term, $start_date, $end_date);
            
            if ($stmt_ins->execute()) {
                $msg = "ส่งใบสมัครเรียบร้อยแล้ว! กรุณารอแอดมินตรวจสอบ";
                $msg_type = "success";
                // Set existing_app to prevent re-submit form showing empty
                $existing_app = ['status' => 'pending']; 
            } else {
                $msg = "เกิดข้อผิดพลาด: " . $conn->error;
                $msg_type = "error";
            }
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap');
        
        body { font-family: 'Sarabun', sans-serif; background: #f5f7fa; }
        main { padding: 20px; }
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
</head>
<body>
    <?php include 'includes/header.php'; ?>


<main>

<div class="container">
    <?php if ($msg): ?>
        <div class="alert alert-<?php echo $msg_type; ?>">
            <?php echo $msg; ?>
        </div>
        <?php if ($msg_type == 'success'): ?>
            <p style="text-align: center;"><a href="index.php">กลับหน้าหลัก</a></p>
        <?php endif; ?>
    <?php endif; ?>

<?php if ($msg_type != 'success'): ?>
    <div class="job-header">
        <?php
        $upload_dir = "../uploads/companies/";
        $default_img = $upload_dir . "default.png";
        $img_filename = !empty($job['com_img']) ? basename($job['com_img']) : '';
        $img_path = (!empty($img_filename) && file_exists($upload_dir . $img_filename)) 
                    ? $upload_dir . $img_filename 
                    : $default_img;
        ?>
        <img src="<?php echo htmlspecialchars($img_path); ?>" alt="Logo">
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
            <?php if (!empty($existing_app['resume_file'])): ?>
                <div style="margin-bottom:5px; font-size:14px;">
                    ไฟล์ปัจจุบัน: <a href="../uploads/<?= $existing_app['resume_file'] ?>" target="_blank">ดูไฟล์เดิม</a>
                </div>
            <?php endif; ?>
            <input type="file" name="resume" id="resume" class="form-control" accept=".pdf" <?php echo empty($existing_app['resume_file']) ? 'required' : ''; ?>>
            <div class="file-hint">* จำเป็นต้องมี Resume เพื่อให้ผู้ประกอบการพิจารณา (อัปโหลดใหม่เพื่อเปลี่ยนไฟล์)</div>
        </div>

        <div class="form-group">
            <label for="transcript">📊 อัปโหลดผลการเรียน (Transcript)</label>
            <?php if (!empty($existing_app['transcript_file'])): ?>
                <div style="margin-bottom:5px; font-size:14px;">
                    ไฟล์ปัจจุบัน: <a href="../uploads/<?= $existing_app['transcript_file'] ?>" target="_blank">ดูไฟล์เดิม</a>
                </div>
            <?php endif; ?>
            <input type="file" name="transcript" id="transcript" class="form-control" accept=".pdf, .jpg, .png">
            <div class="file-hint">(อัปโหลดใหม่เพื่อเปลี่ยนไฟล์)</div>
        </div>

        <!-- ส่วนที่ 1: ข้อมูลการฝึกงาน (นักศึกษา) -->
        <div style="background: #E3F2FD; padding: 20px; border-radius: 10px; border: 1px solid #BBDEFB; margin-bottom: 25px;">
            <h5 style="margin-top:0; color:#0D47A1; font-weight:bold; margin-bottom:15px; border-bottom:1px solid #90CAF9; padding-bottom:8px;">
                <i class="fas fa-user-graduate"></i> ข้อมูลการฝึกงาน (นักศึกษา)
            </h5>

            <div class="form-group">
                <label><i class="fas fa-graduation-cap"></i> เกรดเฉลี่ยสะสม (GPAX)</label>
                <input type="text" name="gpax" class="form-control" placeholder="เช่น 3.50" value="<?= htmlspecialchars($existing_app['gpax'] ?? '') ?>" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-calendar-check"></i> ภาคเรียนที่/ปีการศึกษา</label>
                <input type="text" name="term" class="form-control" placeholder="เช่น 2/2566" value="<?= htmlspecialchars($existing_app['term'] ?? '') ?>" required>
            </div>

            <div style="display:flex; gap:15px;">
                <div class="form-group" style="flex:1;">
                    <label>วันที่เริ่มต้น</label>
                    <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($existing_app['start_date'] ?? '') ?>" required>
                </div>
                <div class="form-group" style="flex:1;">
                    <label>วันที่สิ้นสุด</label>
                    <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($existing_app['end_date'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label>ความประสงค์</label>
                <div style="display:flex; gap:20px; padding: 10px; background:white; border-radius:6px; border:1px solid #ddd;">
                    <label style="font-weight:normal; cursor:pointer;">
                        <input type="radio" name="request_type" value="1" <?= (isset($existing_app['request_type']) && $existing_app['request_type'] == '1') ? 'checked' : '' ?>> หาสถานที่เอง
                    </label>
                    <label style="font-weight:normal; cursor:pointer;">
                        <input type="radio" name="request_type" value="2" <?= (!isset($existing_app['request_type']) || $existing_app['request_type'] == '2') ? 'checked' : '' ?>> วิทยาลัยหาให้
                    </label>
                </div>
            </div>

            <div class="form-group" id="contact_name_group" style="<?= (isset($existing_app['request_type']) && $existing_app['request_type'] == '1') ? '' : 'display:none;' ?>">
                <label>ตำแหน่งผู้ที่ติดต่อ (ถ้ามี)</label>
                <input type="text" name="contact_name" class="form-control" placeholder="เช่น ผู้จัดการฝ่ายบุคคล" value="<?= htmlspecialchars($existing_app['contact_name'] ?? '') ?>">
            </div>
        </div>

        <!-- ส่วนที่ 2: ข้อมูลผู้ปกครอง -->
        <div style="background: #FFF3E0; padding: 20px; border-radius: 10px; border: 1px solid #FFE0B2; margin-bottom: 25px;">
            <h5 style="margin-top:0; color:#E65100; font-weight:bold; margin-bottom:15px; border-bottom:1px solid #FFCC80; padding-bottom:8px;">
                <i class="fas fa-user-friends"></i> ข้อมูลผู้ปกครอง (สำหรับหนังสือยินยอม)
            </h5>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="form-group">
                    <label style="font-size:14px;">ชื่อ-นามสกุล</label>
                    <input type="text" name="parent_name" class="form-control" placeholder="ชื่อ-สกุล ผู้ปกครอง" value="<?= htmlspecialchars($existing_app['parent_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label style="font-size:14px;">ความเกี่ยวข้อง</label>
                    <input type="text" name="parent_relation" class="form-control" placeholder="เช่น บิดา, มารดา" value="<?= htmlspecialchars($existing_app['parent_relation'] ?? '') ?>" required>
                </div>
            </div>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                <div class="form-group">
                    <label style="font-size:14px;">เบอร์โทรศัพท์</label>
                    <input type="text" name="parent_tel" class="form-control" placeholder="เช่น 081-234-5678" value="<?= htmlspecialchars($existing_app['parent_tel'] ?? '') ?>" required maxlength="10">
                </div>
                <div class="form-group">
                    <label style="font-size:14px;">เลขบัตรประชาชน</label>
                    <input type="text" name="parent_id_card" class="form-control" placeholder="เลข 13 หลัก" value="<?= htmlspecialchars($existing_app['parent_id_card'] ?? '') ?>" required maxlength="13">
                </div>
            </div>
            
            <div class="form-group">
                <label style="font-size:14px;">ที่อยู่ที่ติดต่อได้</label>
                <textarea name="parent_address" class="form-control" rows="2" placeholder="บ้านเลขที่, หมู่, ซอย, ถนน, ตำบล, อำเภอ, จังหวัด" required><?= htmlspecialchars($existing_app['parent_address'] ?? '') ?></textarea>
            </div>
        </div>

        <script>
            document.querySelectorAll('input[name="request_type"]').forEach(radio => {
                radio.addEventListener('change', function() {
                    const contactGroup = document.getElementById('contact_name_group');
                    contactGroup.style.display = this.value === '1' ? 'block' : 'none';
                });
            });
        </script>

        <div class="form-group">
            <label for="note">📝 ข้อความเพิ่มเติม (ถ้ามี)</label>
            <textarea name="note" id="note" class="form-control" placeholder="แนะนำตัวสั้นๆ หรือบอกเหตุผลที่อยากฝึกงานที่นี่..."><?php echo isset($existing_app['note']) ? htmlspecialchars($existing_app['note']) : ''; ?></textarea>
        </div>

        <button type="submit" class="btn-submit">
            <?php echo !empty($existing_app) ? '💾 บันทึกการแก้ไข' : '🚀 ส่งใบสมัคร'; ?>
        </button>
    </form>
    <?php endif; ?>
</div>

</main>


</body>
</html>