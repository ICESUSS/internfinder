<?php
session_start();
include '../config.php';

// 1. ตรวจสอบสิทธิ์ (ต้องเป็นนักศึกษาเท่านั้น)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$std_id = $_SESSION['user_id']; // ใช้ค่าเดิมจาก session

// 2. ดึงข้อมูลโปรไฟล์แบบ Join Table
$sql_profile = "SELECT s.*, d.dep_name, c.com_name 
                FROM tb_student s 
                LEFT JOIN tb_department d ON s.dep_id = d.dep_id 
                LEFT JOIN tb_company c ON s.com_id = c.com_id 
                WHERE s.std_id = ? LIMIT 1";
$stmt = $conn->prepare($sql_profile);
$stmt->bind_param("s", $std_id); // เปลี่ยนเป็น "s" เพราะ std_id ใน DB เป็น varchar
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    echo "ไม่พบข้อมูลนักศึกษา";
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ของฉัน - Internfinder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
    <style>
        body { background-color: #f4f7f6; font-family: 'Prompt', sans-serif; }
        .profile-header { background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%); height: 180px; border-radius: 0 0 50px 50px; }
        .profile-card { margin-top: -100px; border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .avatar-circle { width: 120px; height: 120px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: bold; color: #2196F3; margin: 0 auto; box-shadow: 0 5px 15px rgba(0,0,0,0.1); border: 5px solid #fff; overflow: hidden; }
        .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }
        .info-label { font-size: 0.85rem; color: #888; margin-bottom: 2px; }
        .info-value { font-weight: 600; color: #333; margin-bottom: 15px; padding-bottom: 5px; border-bottom: 1px solid #eee; }
        .info-box { background-color: #f8f9fa; border-radius: 15px; padding: 20px; }
    </style>
</head>
<body>

<div class="profile-header"></div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card profile-card overflow-hidden">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <div class="col-md-4 bg-light p-4 text-center border-end">
                            <div class="avatar-circle mb-3">
                                <?php if($student['std_img']): ?>
                                    <img src="../uploads/<?= $student['std_img'] ?>" alt="Profile">
                                <?php else: ?>
                                    <?= mb_strtoupper(mb_substr($student['std_name'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <h4 class="fw-bold mb-1"><?= htmlspecialchars($student['std_name']) ?></h4>
                            <p class="text-muted small mb-4">รหัสนักศึกษา: <?= htmlspecialchars($student['std_id']) ?></p>
                            
                            <div class="info-box text-start">
                                <div class="mb-3">
                                    <label class="small text-muted d-block">แผนกวิชา</label>
                                    <span class="fw-bold"><i class="fas fa-university me-2 text-primary"></i><?= htmlspecialchars($student['dep_name'] ?? 'ไม่ระบุ') ?></span>
                                </div>
                                <div class="mb-0">
                                    <label class="small text-muted d-block">สถานประกอบการฝึกงาน</label>
                                    <span class="fw-bold text-success"><i class="fas fa-building me-2"></i><?= htmlspecialchars($student['com_name'] ?? 'ยังไม่มีที่ฝึกงาน') ?></span>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <a href="dashboard.php" class="btn btn-outline-primary rounded-pill w-100 mb-2">
                                    <i class="fas fa-home me-1"></i> หน้าหลัก
                                </a>
                               
                            </div>
                        </div>

                        <div class="col-md-8 p-4 p-md-5 bg-white">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h3 class="fw-bold m-0"><i class="fas fa-user-circle me-2 text-primary"></i>ข้อมูลส่วนตัว</h3>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-label">ชื่อ-นามสกุล</div>
                                    <div class="info-value"><?= htmlspecialchars($student['std_name']) ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">ระดับชั้น</div>
                                    <div class="info-value"><?= htmlspecialchars($student['std_level'] ?? 'ไม่ระบุ') ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">อีเมลติดต่อ (Gmail)</div>
                                    <div class="info-value"><?= htmlspecialchars($student['std_gmail']) ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">เบอร์โทรศัพท์</div>
                                    <div class="info-value"><?= htmlspecialchars($student['std_tel'] ?: 'ไม่ได้ระบุ') ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">เลขบัตรประจำตัวประชาชน / พาสเวิร์ด (เบื้องต้น)</div>
                                    <div class="info-value text-muted">********</div>
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="info-label">ที่อยู่ปัจจุบัน</div>
                                    <div class="info-value"><?= nl2br(htmlspecialchars($student['std_add'] ?: 'ไม่ได้ระบุข้อมูลที่อยู่')) ?></div>
                                </div>
                            </div>

                            <div class="mt-5 p-3 border-start border-4 border-info bg-light">
                                <small class="text-muted d-block"><i class="fas fa-info-circle me-1"></i> หมายเหตุ:</small>
                                <small class="text-secondary">หากต้องการแก้ไขข้อมูลส่วนตัว กรุณาติดต่อฝ่ายทะเบียนหรือแอดมินระบบเพื่อดำเนินการแก้ไขข้อมูลให้ถูกต้อง</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>