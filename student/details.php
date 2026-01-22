<?php
session_start();
include '../config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$company = null;

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM tb_company WHERE com_id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $company = $res->fetch_assoc();
    $stmt->close();

    // Load job details from tb_company_detail
    $jobs = [];
    if ($company) {
        $jstmt = $conn->prepare("SELECT * FROM tb_company_detail WHERE com_id = ? ORDER BY job_title ASC");
        if ($jstmt) {
            $jstmt->bind_param('i', $id);
            $jstmt->execute();
            $jres = $jstmt->get_result();
            while ($jr = $jres->fetch_assoc()) {
                $jobs[] = $jr;
            }
            $jstmt->close();
        }
    }
} else {
    $jobs = [];
} 
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>รายละเอียดสถานประกอบการ</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css">
<link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
<link rel="stylesheet" href="../assets/css/student-dashboard.css">
<link rel="stylesheet" href="../assets/css/details.css">
<link rel="stylesheet" href="../assets/css/mobile-responsive.css">
</head>

<body>
    <?php include 'includes/header.php'; ?>


<div class="container">

<?php if(!$company): ?>
    <div class="card">ไม่พบข้อมูลสถานประกอบการ</div>
<?php else: ?>

<div class="grid">

    <!-- 🔹 ซ้าย : รายละเอียด -->
    <div class="card">
        <span class="badge">รับนักศึกษาฝึกงาน</span>
        <h1><?= htmlspecialchars($company['com_name']) ?></h1>
        <p class="info"><i class="fas fa-industry"></i> เทคโนโลยี / Software</p>

        <h3>ข้อมูลการติดต่อ</h3>
        <p class="info">
            <i class="fas fa-user"></i>
            <?= htmlspecialchars($company['com_contact'] ?: '-') ?>
        </p>

        <h3>ที่อยู่</h3>
        <p class="info">
            <?= htmlspecialchars(($company['com_add_no'] ? $company['com_add_no'] . ' ' : '') . ($company['com_road'] ? 'ถ.' . $company['com_road'] . ' ' : '') . ($company['com_subdistrict'] ? 'ต.' . $company['com_subdistrict'] . ' ' : '') . ($company['com_district'] ? 'อ.' . $company['com_district'] . ' ' : '') . ($company['com_province'] ? 'จ.' . $company['com_province'] . ' ' : '') . ($company['com_zipcode'] ?? '')) ?: '-' ?>
        </p>

        <h3>ตำแหน่งฝึกงาน</h3>
        <?php if (!empty($jobs)): ?>
            <?php foreach ($jobs as $job): 
                // Calculate capacity status
                $capacity = isset($job['job_capacity']) ? (int)$job['job_capacity'] : 0;
                $applied_count = 0;
                if ($capacity > 0) {
                    $detail_id = (int)$job['detail_id'];
                    $cap_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM tb_internship WHERE detail_id = ? AND status = 'approved'");
                    if ($cap_stmt) {
                        $cap_stmt->bind_param("i", $detail_id);
                        $cap_stmt->execute();
                        $cap_res = $cap_stmt->get_result();
                        if ($cap_res) {
                            $applied_count = (int)($cap_res->fetch_assoc()['cnt'] ?? 0);
                        }
                        $cap_stmt->close();
                    }
                }
                $is_full = ($capacity > 0 && $applied_count >= $capacity);
            ?>
                <div class="job-card">
                    <h4><?= htmlspecialchars($job['job_title']) ?> <span class="job-type"><?= htmlspecialchars($job['job_type']) ?></span></h4>
                    <p class="info"><strong>รายละเอียด:</strong> <?= nl2br(htmlspecialchars($job['job_description'])) ?></p>
                    <p class="info"><strong>คุณสมบัติ:</strong> <?= nl2br(htmlspecialchars($job['job_qualification'])) ?></p>
                    <p class="info"><strong>สวัสดิการ:</strong> <?= nl2br(htmlspecialchars($job['job_welfare'])) ?> — <strong>เบี้ยเลี้ยง:</strong> <?= htmlspecialchars($job['allowance']) ?></p>
                    <p class="info"><strong>เวลาทำงาน:</strong> <?= htmlspecialchars($job['work_time']) ?> (<?= htmlspecialchars($job['work_day']) ?>)</p>
                    
                    <?php if ($capacity > 0): ?>
                    <p class="info" style="margin-top: 10px;">
                        <strong>จำนวนรับ:</strong> 
                        <span style="font-weight: 600; color: <?= $is_full ? '#e74c3c' : '#27ae60' ?>">
                            <?= $applied_count ?>/<?= $capacity ?> คน
                        </span>
                        <?php if ($is_full): ?>
                            <span style="background: #FEE2E2; color: #991B1B; padding: 3px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-left: 8px;">เต็มแล้ว</span>
                        <?php else: ?>
                            <span style="background: #DCFCE7; color: #166534; padding: 3px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; margin-left: 8px;">เปิดรับ</span>
                        <?php endif; ?>
                    </p>
                    <?php endif; ?>
                    
                    <div style="margin-top: 15px;">
                        <?php if ($is_full): ?>
                            <span class="btn" style="background-color: #95a5a6; padding: 8px 20px; font-size: 14px; cursor: not-allowed;">ตำแหน่งเต็มแล้ว</span>
                        <?php else: ?>
                            <a href="apply_job.php?id=<?= $job['detail_id'] ?>" class="btn" style="background-color: #2ecc71; padding: 8px 20px; font-size: 14px;">สมัครงาน</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="info">ไม่มีตำแหน่งงานที่ประกาศในขณะนี้</p>
        <?php endif; ?>
    </div>

    <!-- 🔹 ขวา : การ์ดบริษัท -->
    <div class="card" style="text-align:center">
        <?php
        // Define paths
        $upload_dir = "../uploads/companies/";
        $default_img = $upload_dir . "default.png";
        $img_filename = !empty($company['com_img']) ? basename($company['com_img']) : '';
        $img_path = !empty($img_filename) ? $upload_dir . $img_filename : $default_img;

        // Check if file actually exists on disk
        if (!file_exists($img_path) || empty($img_filename)) {
            $img_path = $default_img;
        }
        ?>

        <img src="<?= htmlspecialchars($img_path, ENT_QUOTES, 'UTF-8') ?>"
             style="width:80px;height:80px;border-radius:12px;
                    object-fit:cover;border:1px solid #eee;">

        <h3 style="margin-top:12px">
            <?= htmlspecialchars($company['com_name']) ?>
        </h3>

        <p class="info">
            <i class="fas fa-phone"></i>
            <?= htmlspecialchars($company['com_tel']) ?>
        </p>

    </div>

</div>
<?php endif; ?>

</div>


</body>
</html>
