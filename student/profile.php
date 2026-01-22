<?php
session_start();
include '../config.php';

// 1. ตรวจสอบสิทธิ์
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$std_id = $_SESSION['user_id'];

// 2. ดึงข้อมูลโปรไฟล์ (ปรับปรุง SQL ให้เข้ากับโครงสร้างใหม่)
// เปลี่ยน s.com_id เป็นการ Join ผ่าน tb_internship แทน
// และเปลี่ยน std_add เป็น std_address
$sql_profile = "SELECT s.*, d.dep_name, c.com_name 
                FROM tb_student s 
                LEFT JOIN tb_department d ON s.dep_id = d.dep_id 
                LEFT JOIN tb_internship i ON s.std_id = i.std_id AND i.status = 'approved'
                LEFT JOIN tb_company c ON i.com_id = c.com_id 
                WHERE s.std_id = ? LIMIT 1";

$stmt = $conn->prepare($sql_profile);

// เพิ่มการเช็ค Error เผื่อ SQL พังในอนาคต
if (!$stmt) {
    die("SQL Prepare Error: " . $conn->error);
}

$stmt->bind_param("s", $std_id);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
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
    <?php include 'includes/header.php'; ?>


<div class="profile-header"></div>

<main>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card profile-card overflow-hidden">
                <div class="card-body p-0">
                    <div class="row g-0">
                        <div class="col-md-4 bg-light p-4 text-center border-end">
                            <div class="avatar-circle mb-3">
                                <?php if(!empty($student['std_img'])): ?>
                                    <img src="../uploads/<?= $student['std_img'] ?>" alt="Profile">
                                <?php else: ?>
                                    <?= mb_strtoupper(mb_substr($student['std_name'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <h4 class="fw-bold mb-1"><?= htmlspecialchars($student['std_name'] . ' ' . $student['std_lastname']) ?></h4>
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
                                <a href="index.php" class="btn btn-outline-primary rounded-pill w-100 mb-2">
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
                                    <div class="info-value"><?= htmlspecialchars($student['std_name'] . ' ' . $student['std_lastname']) ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label">ระดับชั้น</div>
                                    <div class="info-value"><?= htmlspecialchars($student['std_level'] ?? 'ไม่ระบุ') ?> / ห้อง <?= htmlspecialchars($student['std_room'] ?? '-') ?></div>
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
                                    <div class="info-label">รหัสผ่าน</div>
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
    <div class="row justify-content-center mt-4">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white p-4 border-bottom">
                    <h4 class="mb-0 fw-bold"><i class="fas fa-history text-primary me-2"></i>ประวัติการสมัครฝึกงาน</h4>
                </div>
                <div class="card-body p-0">
                    <?php
                    // Fetch applications
                    $sql_apps = "SELECT i.*, c.com_name, d.job_title 
                                 FROM tb_internship i
                                 JOIN tb_company c ON i.com_id = c.com_id
                                 JOIN tb_company_detail d ON i.detail_id = d.detail_id
                                 WHERE i.std_id = ?
                                 ORDER BY i.intern_id DESC";
                    $stmt_apps = $conn->prepare($sql_apps);
                    $stmt_apps->bind_param("s", $std_id);
                    $stmt_apps->execute();
                    $res_apps = $stmt_apps->get_result();
                    ?>
                    
                    <?php if ($res_apps->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">บริษัท</th>
                                        <th>ตำแหน่ง</th>
                                        <th>วันที่สมัคร</th>
                                        <th>สถานะ</th>
                                        <th class="text-end pe-4">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($app = $res_apps->fetch_assoc()): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold text-secondary"><?= htmlspecialchars($app['com_name']) ?></td>
                                            <td><?= htmlspecialchars($app['job_title']) ?></td>
                                            <td class="text-muted small">
                                                No Date Column <!-- Assuming no created_at column in tb_internship based on schema seen, looking at file apply_job.php insert -->
                                                <!-- Checking insert in apply_job.php.. INSERT INTO tb_internship ... logic doesn't insert date. -->
                                                <!-- So I will just show ID or blank -->
                                                #<?= $app['intern_id'] ?>
                                            </td>
                                            <td>
                                                <?php 
                                                $status = $app['status'];
                                                $badgeClass = '';
                                                $statusText = '';
                                                switch($status) {
                                                    case 'pending': $badgeClass='bg-warning'; $statusText='รออนุมัติ'; break;
                                                    case 'approved': $badgeClass='bg-success'; $statusText='อนุมัติแล้ว'; break;
                                                    case 'rejected': $badgeClass='bg-danger'; $statusText='ปฏิเสธ'; break;
                                                    default: $badgeClass='bg-secondary'; $statusText=$status;
                                                }
                                                ?>
                                                <span class="badge rounded-pill <?= $badgeClass ?>"><?= $statusText ?></span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <?php if ($status == 'pending'): ?>
                                                    <a href="apply_job.php?id=<?= $app['detail_id'] ?>&mode=edit" class="btn btn-sm btn-outline-warning">
                                                        <i class="fas fa-edit"></i> แก้ไข
                                                    </a>
                                                <?php elseif ($status == 'approved'): ?>
                                                    <a href="print_request.php?id=<?= $app['intern_id'] ?>" target="_blank" class="btn btn-sm btn-success">
                                                        <i class="fas fa-print"></i> พิมพ์ใบคำร้อง
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="p-5 text-center text-muted">
                            <i class="fas fa-folder-open mb-3" style="font-size: 3rem; opacity: 0.3;"></i>
                            <p>ไม่มีประวัติการสมัครฝึกงาน</p>
                            <a href="index.php" class="btn btn-primary rounded-pill px-4">ค้นหาที่ฝึกงาน</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</main>


</body>
</html>