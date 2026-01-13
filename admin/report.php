<?php
session_start();
include '../config.php';

// ตรวจสอบสิทธิ์ (Admin Only)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

/* 1. ดึงข้อมูลสถิติรวม */
// นับรายงาน
$count_reports = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_reports"))['total'];
$pending_reports = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_reports WHERE status='open'"))['total'];

// นับจำนวนนักศึกษา
$count_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_student"))['total'];

/* 2. ดึงรายงานล่าสุด 5 รายการ */
$recent_reports = mysqli_query($conn, "
    SELECT r.*, s.std_name 
    FROM tb_reports r 
    LEFT JOIN tb_student s ON r.std_id = s.std_id 
    ORDER BY r.created_at DESC LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Dashboard - ระบบจัดการส่วนกลาง</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #2196F3;
            --bg-body: #f4f6f9;
            --text-muted: #666;
        }
        body { font-family: 'Sarabun', sans-serif; background-color: var(--bg-body); }
        
        /* สไตล์ Card ตาม Prompt */
        .stat-card {
            background: #fff;
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
            transition: transform 0.3s ease;
            padding: 25px;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-icon { font-size: 32px; color: var(--primary-color); margin-bottom: 15px; }
        .stat-value { font-size: 28px; font-weight: 700; color: #333; }
        .stat-label { color: var(--text-muted); font-size: 14px; }
        
        .action-card {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,.08);
        }
        .btn-custom {
            background-color: var(--primary-color);
            color: white;
            border-radius: 8px;
            padding: 10px 20px;
            border: none;
        }
        .btn-custom:hover { background-color: #1976D2; color: white; }
    </style>
</head>
<body>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold m-0">📊 ระบบจัดการส่วนกลาง (Admin)</h3>
            <p class="text-muted small">ยินดีต้อนรับคุณ Admin, นี่คือภาพรวมของระบบวันนี้</p>
        </div>
        <a href="../logout.php" class="btn btn-outline-danger btn-sm">ออกจากระบบ</a>
        <a href="dashboard.php">กลับ</a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <i class="fa-solid fa-users stat-icon"></i>
                <div class="stat-value"><?= number_format($count_students) ?></div>
                <div class="stat-label">นักศึกษาทั้งหมด</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <i class="fa-solid fa-file-invoice stat-icon text-warning"></i>
                <div class="stat-value"><?= number_format($count_reports) ?></div>
                <div class="stat-label">รายงานทั้งหมด</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <i class="fa-solid fa-circle-exclamation stat-icon text-danger"></i>
                <div class="stat-value text-danger"><?= number_format($pending_reports) ?></div>
                <div class="stat-label">รอดำเนินการ</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <i class="fa-solid fa-circle-check stat-icon text-success"></i>
                <div class="stat-value text-success"><?= number_format($count_reports - $pending_reports) ?></div>
                <div class="stat-label">แก้ไขเสร็จสิ้น</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card action-card">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">📝 รายงานล่าสุด</h5>
                    <a href="manage_reports.php" class="btn btn-link btn-sm text-decoration-none">ดูทั้งหมด</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">ผู้แจ้ง</th>
                                    <th>หัวข้อ</th>
                                    <th>สถานะ</th>
                                    <th class="text-end pe-4">เวลา</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = mysqli_fetch_assoc($recent_reports)): ?>
                                <tr>
                                    <td class="ps-4 fw-semibold"><?= htmlspecialchars($row['std_name']) ?></td>
                                    <td><?= htmlspecialchars($row['subject']) ?></td>
                                    <td>
                                        <span class="badge <?= $row['status'] == 'open' ? 'bg-danger' : 'bg-success' ?>">
                                            <?= $row['status'] == 'open' ? 'รอดำเนินการ' : 'เสร็จสิ้น' ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4 text-muted small">
                                        <?= date('H:i', strtotime($row['created_at'])) ?> น.
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card action-card mb-4">

            <div class="card action-card">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">สัดส่วนสถานะงาน</h6>
                    <canvas id="miniChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // กราฟสัดส่วนงาน (Mini Doughnut)
    const ctx = document.getElementById('miniChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['ค้างอยู่', 'เสร็จแล้ว'],
            datasets: [{
                data: [<?= $pending_reports ?>, <?= $count_reports - $pending_reports ?>],
                backgroundColor: ['#ff4d4d', '#2196F3'],
                borderWidth: 0
            }]
        },
        options: {
            plugins: { legend: { position: 'bottom' } },
            cutout: '70%'
        }
    });
</script>
</body>
</html>