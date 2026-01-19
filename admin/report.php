<?php
session_start();
include '../config.php';

// ตรวจสอบสิทธิ์ (Admin Only)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

/* ===== ดึงข้อมูลสถิติ ===== */

// นักศึกษา
$count_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_student"))['total'];
$students_with_internship = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT COUNT(DISTINCT std_id) as total FROM tb_internship WHERE status = 'approved'
"))['total'];
$students_without_internship = $count_students - $students_with_internship;

// บริษัท
$count_companies = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_company"))['total'];

// คำขอฝึกงาน
$total_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_internship"))['total'];
$pending_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_internship WHERE status = 'pending'"))['total'];

// รายงานปัญหา
$count_reports = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_reports"))['total'];
$pending_reports = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_reports WHERE status='open'"))['total'];
$resolved_reports = $count_reports - $pending_reports;

// สถิติตามแผนก
$stats_by_dept = mysqli_query($conn, "
    SELECT d.dep_name, 
           COUNT(s.std_id) as total_students,
           COUNT(DISTINCT i.std_id) as students_with_internship
    FROM tb_department d
    LEFT JOIN tb_student s ON d.dep_id = s.dep_id
    LEFT JOIN tb_internship i ON s.std_id = i.std_id AND i.status = 'approved'
    GROUP BY d.dep_id, d.dep_name
    ORDER BY total_students DESC
");

// สถิติตามระดับชั้น
$stats_by_level = mysqli_query($conn, "
    SELECT std_level, 
           COUNT(*) as total,
           (SELECT COUNT(DISTINCT i.std_id) FROM tb_internship i 
            JOIN tb_student s2 ON i.std_id = s2.std_id 
            WHERE i.status = 'approved' AND s2.std_level = s.std_level) as with_internship
    FROM tb_student s
    WHERE std_level IS NOT NULL AND std_level != ''
    GROUP BY std_level
    ORDER BY std_level
");

// รายงานปัญหาทั้งหมด
$all_reports = mysqli_query($conn, "
    SELECT r.*, s.std_name, s.std_lastname 
    FROM tb_reports r 
    LEFT JOIN tb_student s ON r.std_id = s.std_id 
    ORDER BY r.status ASC, r.created_at DESC
");

// คำขอล่าสุด
$recent_requests = mysqli_query($conn, "
    SELECT i.*, s.std_name, s.std_lastname, c.com_name
    FROM tb_internship i
    JOIN tb_student s ON i.std_id = s.std_id
    JOIN tb_company c ON i.com_id = c.com_id
    ORDER BY i.intern_id DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานสรุปภาพรวม | Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-light: #EEF2FF;
            --success: #10B981;
            --success-light: #DCFCE7;
            --danger: #EF4444;
            --danger-light: #FEE2E2;
            --warning: #F59E0B;
            --warning-light: #FEF3C7;
            --bg-main: #F8FAFC;
            --bg-card: #FFFFFF;
            --text-main: #1E293B;
            --text-muted: #64748B;
            --border: #E2E8F0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Sarabun', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            line-height: 1.5;
            padding: 20px;
        }

        .container { max-width: 1400px; margin: 0 auto; }

        /* ===== Header ===== */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: linear-gradient(135deg, var(--primary) 0%, #6366F1 100%);
            padding: 24px 30px;
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            color: white;
        }

        header h1 { font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
        header p { font-size: 13px; opacity: 0.9; margin-top: 4px; }

        .btn {
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }

        .btn-white { background: rgba(255,255,255,0.2); color: white; backdrop-filter: blur(10px); }
        .btn-white:hover { background: rgba(255,255,255,0.3); }
        .btn-sm { padding: 6px 12px; font-size: 12px; }
        .btn-success { background: var(--success); color: white; }
        .btn-success:hover { background: #059669; }

        /* ===== Stats Grid ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 24px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.primary::before { background: var(--primary); }
        .stat-card.success::before { background: var(--success); }
        .stat-card.warning::before { background: var(--warning); }
        .stat-card.danger::before { background: var(--danger); }

        .stat-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .stat-icon.primary { background: var(--primary-light); color: var(--primary); }
        .stat-icon.success { background: var(--success-light); color: var(--success); }
        .stat-icon.warning { background: var(--warning-light); color: var(--warning); }
        .stat-icon.danger { background: var(--danger-light); color: var(--danger); }

        .stat-value { font-size: 32px; font-weight: 700; margin-bottom: 4px; }
        .stat-label { font-size: 14px; color: var(--text-muted); font-weight: 500; }
        .stat-sub { font-size: 12px; color: var(--text-muted); margin-top: 8px; }

        /* ===== Layout Grid ===== */
        .layout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .content-card {
            background: var(--bg-card);
            border-radius: 16px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .content-card.full-width {
            grid-column: 1 / -1;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 { font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .card-header h3 i { color: var(--primary); }

        .card-body { padding: 24px; }

        /* ===== Progress Bars ===== */
        .progress-item { margin-bottom: 20px; }
        .progress-item:last-child { margin-bottom: 0; }
        .progress-label { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; }
        .progress-label .name { font-weight: 500; }
        .progress-label .value { color: var(--text-muted); }
        .progress-bar { height: 10px; background: #E2E8F0; border-radius: 5px; overflow: hidden; }
        .progress-fill { height: 100%; border-radius: 5px; transition: width 0.5s ease; }
        .progress-fill.primary { background: linear-gradient(90deg, var(--primary), #818CF8); }
        .progress-fill.success { background: linear-gradient(90deg, var(--success), #34D399); }

        /* ===== Table ===== */
        table { width: 100%; border-collapse: collapse; }
        th { background: #F8FAFC; padding: 14px 20px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border); }
        td { padding: 16px 20px; border-bottom: 1px solid var(--border); font-size: 14px; }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #F8FAFC; }

        .badge { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .badge-pending { background: var(--warning-light); color: #92400E; }
        .badge-open { background: var(--danger-light); color: #991B1B; }
        .badge-approved { background: var(--success-light); color: #166534; }
        .badge-closed { background: var(--success-light); color: #166534; }
        .badge-rejected { background: var(--danger-light); color: #991B1B; }

        .report-message {
            background: #F8FAFC;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 8px;
            border-left: 3px solid var(--border);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 48px;
            opacity: 0.3;
            margin-bottom: 15px;
        }

        /* ===== Responsive ===== */
        @media (max-width: 1200px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 768px) {
            header { flex-direction: column; text-align: center; gap: 15px; }
            .stats-grid { grid-template-columns: 1fr; }
            .layout-grid { grid-template-columns: 1fr; }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
</head>
<body>

<div class="container">
    <header>
        <div>
            <h1><i class="fas fa-chart-line"></i> รายงานสรุปภาพรวม</h1>
            <p>สรุปข้อมูลระบบฝึกงาน ณ วันที่ <?= date('d/m/Y') ?></p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="index.php" class="btn btn-white"><i class="fas fa-arrow-left"></i> กลับหน้าหลัก</a>
        </div>
    </header>

    <?php 
    $msg = $_GET['msg'] ?? '';
    if ($msg): 
    ?>
    <div style="background: <?= $msg == 'resolved' ? 'var(--success-light)' : 'var(--danger-light)' ?>; 
                color: <?= $msg == 'resolved' ? '#166534' : '#991B1B' ?>; 
                padding: 15px 20px; 
                border-radius: 12px; 
                margin-bottom: 20px; 
                font-weight: 500;
                display: flex;
                align-items: center;
                gap: 10px;">
        <?php if($msg == 'resolved'): ?>
            <i class="fas fa-check-circle"></i> แก้ไขปัญหาเรียบร้อยแล้ว สถานะถูกเปลี่ยนเป็น "เสร็จสิ้น"
        <?php elseif($msg == 'notfound'): ?>
            <i class="fas fa-exclamation-circle"></i> ไม่พบรายงานที่ต้องการ
        <?php elseif($msg == 'error'): ?>
            <i class="fas fa-times-circle"></i> เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-header">
                <div>
                    <div class="stat-value"><?= number_format($count_students) ?></div>
                    <div class="stat-label">นักศึกษาทั้งหมด</div>
                </div>
                <div class="stat-icon primary"><i class="fas fa-user-graduate"></i></div>
            </div>
            <div class="stat-sub">
                <span style="color: var(--success);"><?= $students_with_internship ?> มีที่ฝึกงาน</span> · 
                <span style="color: var(--danger);"><?= $students_without_internship ?> ยังไม่มี</span>
            </div>
        </div>

        <div class="stat-card success">
            <div class="stat-header">
                <div>
                    <div class="stat-value"><?= number_format($count_companies) ?></div>
                    <div class="stat-label">สถานประกอบการ</div>
                </div>
                <div class="stat-icon success"><i class="fas fa-building"></i></div>
            </div>
            <div class="stat-sub">รองรับนักศึกษาฝึกงาน</div>
        </div>

        <div class="stat-card warning">
            <div class="stat-header">
                <div>
                    <div class="stat-value"><?= number_format($pending_requests) ?></div>
                    <div class="stat-label">คำขอรอพิจารณา</div>
                </div>
                <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
            </div>
            <div class="stat-sub">จากทั้งหมด <?= $total_requests ?> คำขอ</div>
        </div>

        <div class="stat-card danger">
            <div class="stat-header">
                <div>
                    <div class="stat-value"><?= number_format($pending_reports) ?></div>
                    <div class="stat-label">รายงานปัญหารอแก้ไข</div>
                </div>
                <div class="stat-icon danger"><i class="fas fa-exclamation-triangle"></i></div>
            </div>
            <div class="stat-sub">แก้ไขแล้ว <?= $resolved_reports ?> รายการ</div>
        </div>
    </div>

    <!-- Problem Reports Section -->
    <div class="content-card full-width" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3><i class="fas fa-bug" style="color: var(--danger);"></i> รายงานปัญหาจากนักศึกษา</h3>
            <span style="font-size: 13px; color: var(--text-muted);">ทั้งหมด <?= $count_reports ?> รายการ</span>
        </div>
        <?php if(mysqli_num_rows($all_reports) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 60px;">#</th>
                    <th>ผู้แจ้ง</th>
                    <th>หัวข้อ</th>
                    <th>รายละเอียด</th>
                    <th style="width: 100px;">สถานะ</th>
                    <th style="width: 140px;">วันที่แจ้ง</th>
                    <th style="width: 100px;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($all_reports)): ?>
                <tr>
                    <td style="font-weight: 600; color: var(--text-muted);"><?= $row['report_id'] ?></td>
                    <td>
                        <div style="font-weight: 600;"><?= htmlspecialchars($row['std_name'] . ' ' . $row['std_lastname']) ?></div>
                        <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($row['std_id']) ?></div>
                    </td>
                    <td style="font-weight: 500;"><?= htmlspecialchars($row['subject']) ?></td>
                    <td>
                        <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--text-muted);">
                            <?= htmlspecialchars($row['message']) ?>
                        </div>
                    </td>
                    <td>
                        <?php if($row['status'] == 'open'): ?>
                            <span class="badge badge-open"><i class="fas fa-clock"></i> รอดำเนินการ</span>
                        <?php else: ?>
                            <span class="badge badge-closed"><i class="fas fa-check"></i> เสร็จสิ้น</span>
                        <?php endif; ?>
                    </td>
                    <td style="color: var(--text-muted); font-size: 13px;">
                        <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($row['created_at'])) ?><br>
                        <i class="fas fa-clock"></i> <?= date('H:i น.', strtotime($row['created_at'])) ?>
                    </td>
                    <td>
                        <?php if($row['status'] == 'open'): ?>
                            <a href="resolve_report.php?id=<?= $row['report_id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('ยืนยันว่าแก้ไขปัญหานี้เรียบร้อยแล้ว?')">
                                <i class="fas fa-check"></i> แก้ไขแล้ว
                            </a>
                        <?php else: ?>
                            <span style="color: var(--success); font-size: 12px;"><i class="fas fa-check-circle"></i> ดำเนินการแล้ว</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <p>ยังไม่มีรายงานปัญหา</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Department & Level Stats -->
    <div class="layout-grid">
        <div class="content-card">
            <div class="card-header">
                <h3><i class="fas fa-building-columns"></i> สถิติตามแผนกวิชา</h3>
            </div>
            <div class="card-body">
                <?php 
                mysqli_data_seek($stats_by_dept, 0);
                while($dept = mysqli_fetch_assoc($stats_by_dept)): 
                    $percentage = $dept['total_students'] > 0 ? round(($dept['students_with_internship'] / $dept['total_students']) * 100) : 0;
                ?>
                <div class="progress-item">
                    <div class="progress-label">
                        <span class="name"><?= htmlspecialchars($dept['dep_name']) ?></span>
                        <span class="value"><?= $dept['students_with_internship'] ?>/<?= $dept['total_students'] ?> คน (<?= $percentage ?>%)</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill primary" style="width: <?= $percentage ?>%"></div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h3><i class="fas fa-layer-group"></i> สถิติตามระดับชั้น</h3>
            </div>
            <div class="card-body">
                <?php 
                mysqli_data_seek($stats_by_level, 0);
                while($level = mysqli_fetch_assoc($stats_by_level)): 
                    $percentage = $level['total'] > 0 ? round(($level['with_internship'] / $level['total']) * 100) : 0;
                ?>
                <div class="progress-item">
                    <div class="progress-label">
                        <span class="name"><?= htmlspecialchars($level['std_level']) ?></span>
                        <span class="value"><?= $level['with_internship'] ?>/<?= $level['total'] ?> คน (<?= $percentage ?>%)</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill success" style="width: <?= $percentage ?>%"></div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Recent Requests -->
    <div class="content-card full-width">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> คำขอฝึกงานล่าสุด</h3>
            <a href="internship_requests.php" style="font-size: 13px; color: var(--primary); text-decoration: none; font-weight: 600;">
                ดูทั้งหมด <i class="fas fa-chevron-right"></i>
            </a>
        </div>
        <table>
            <thead>
                <tr>
                    <th>นักศึกษา</th>
                    <th>สถานประกอบการ</th>
                    <th>สถานะ</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($recent_requests) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($recent_requests)): ?>
                    <tr>
                        <td>
                            <div style="font-weight: 600;"><?= htmlspecialchars($row['std_name'] . ' ' . $row['std_lastname']) ?></div>
                        </td>
                        <td style="color: var(--text-muted);"><?= htmlspecialchars($row['com_name']) ?></td>
                        <td>
                            <?php 
                            $badge_class = 'badge-pending';
                            $status_text = 'รอพิจารณา';
                            if ($row['status'] == 'approved') { $badge_class = 'badge-approved'; $status_text = 'อนุมัติ'; }
                            elseif ($row['status'] == 'rejected') { $badge_class = 'badge-rejected'; $status_text = 'ปฏิเสธ'; }
                            ?>
                            <span class="badge <?= $badge_class ?>"><?= $status_text ?></span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align: center; padding: 40px; color: var(--text-muted);">ไม่มีข้อมูล</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
