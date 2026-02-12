<?php
session_start();
include '../config.php';

/* ===== ตลอดชุดคำสั่ง (Base URL) ===== */
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$base_url .= preg_replace('/(\/admin\/).*/', '/', $_SERVER['SCRIPT_NAME']);

// ตรวจสอบสิทธิ์ (Admin Only)
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

/* ===== ดึงข้อมูลสถิติ ===== */

// นักศึกษา
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tb_student");
$stmt->execute();
$count_students = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(DISTINCT std_id) as total FROM tb_internship WHERE status = 'approved'");
$stmt->execute();
$students_with_internship = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$students_without_internship = $count_students - $students_with_internship;

// บริษัท
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tb_company");
$stmt->execute();
$count_companies = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// คำขอฝึกงาน
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tb_internship");
$stmt->execute();
$total_requests = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tb_internship WHERE status = 'pending'");
$stmt->execute();
$pending_requests = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

// รายงานปัญหา
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tb_reports");
$stmt->execute();
$count_reports = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();

$status_open = 'open';
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tb_reports WHERE status = ?");
$stmt->bind_param("s", $status_open);
$stmt->execute();
$pending_reports = $stmt->get_result()->fetch_assoc()['total'];
$stmt->close();
$resolved_reports = $count_reports - $pending_reports;

// สถิติตามแผนก
$stats_by_dept_stmt = $conn->prepare("
    SELECT d.dep_name, 
           COUNT(s.std_id) as total_students,
           COUNT(DISTINCT i.std_id) as students_with_internship
    FROM tb_department d
    LEFT JOIN tb_student s ON d.dep_id = s.dep_id
    LEFT JOIN tb_internship i ON s.std_id = i.std_id AND i.status = 'approved'
    GROUP BY d.dep_id, d.dep_name
    ORDER BY total_students DESC
");
$stats_by_dept_stmt->execute();
$stats_by_dept = $stats_by_dept_stmt->get_result();

// สถิติตามระดับชั้น
$stats_by_level_stmt = $conn->prepare("
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
$stats_by_level_stmt->execute();
$stats_by_level = $stats_by_level_stmt->get_result();

// รายงานปัญหาทั้งหมด
$all_reports_stmt = $conn->prepare("
    SELECT r.*, s.std_name, s.std_lastname 
    FROM tb_reports r 
    LEFT JOIN tb_student s ON r.std_id = s.std_id 
    ORDER BY r.status ASC, r.created_at DESC
");
$all_reports_stmt->execute();
$all_reports = $all_reports_stmt->get_result();

// คำขอล่าสุด
$recent_requests_stmt = $conn->prepare("
    SELECT i.*, s.std_name, s.std_lastname, c.com_name
    FROM tb_internship i
    JOIN tb_student s ON i.std_id = s.std_id
    JOIN tb_company c ON i.com_id = c.com_id
    ORDER BY i.intern_id DESC
    LIMIT 5
");
$recent_requests_stmt->execute();
$recent_requests = $recent_requests_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานสรุปภาพรวม | InternFinder</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    
    <!-- Shared Admin Styles -->
    <link rel="stylesheet" href="<?= $base_url ?>admin/assets/css/admin-style.css">
</head>
<body>

    <!-- MOBILE HEADER -->
    <div class="mobile-header">
        <div style="display: flex; align-items: center; gap: 10px; font-weight: 700;">
            <i class="fas fa-graduation-cap text-primary"></i>
            <span>InternFinder</span>
        </div>
        <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
    </div>

    <!-- SIDEBAR -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- MAIN -->
    <main class="main-content">
        <div class="top-bar">
            <div class="welcome-text">
                <h1>รายงานสรุปภาพรวม</h1>
                <p>สรุปข้อมูลระบบฝึกงาน ณ วันที่ <?= date('d/m/Y') ?></p>
            </div>
            <div class="header-tools">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> กลับแผงควบคุม
                </a>
            </div>
        </div>

        <?php 
        $msg = $_GET['msg'] ?? '';
        if ($msg): 
        ?>
        <div class="card" style="background: <?= in_array($msg, ['resolved', 'deleted']) ? '#DCFCE7' : '#FEE2E2' ?>; color: <?= in_array($msg, ['resolved', 'deleted']) ? '#166534' : '#991B1B' ?>; padding: 16px; border: 1px solid <?= in_array($msg, ['resolved', 'deleted']) ? '#BBF7D0' : '#FECACA' ?>; margin-bottom: 24px;">
            <i class="fas <?= in_array($msg, ['resolved', 'deleted']) ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i> 
            <?php if($msg == 'resolved'): ?>
                แก้ไขปัญหาเรียบร้อยแล้ว สถานะถูกเปลี่ยนเป็น "เสร็จสิ้น"
            <?php elseif($msg == 'deleted'): ?>
                ลบรายงานสำเร็จ
            <?php elseif($msg == 'notfound'): ?>
                ไม่พบรายงานที่ต้องการ
            <?php elseif($msg == 'error'): ?>
                เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="card stat-card" style="border-left: 4px solid var(--primary);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">นักศึกษาทั้งหมด</div>
                        <div style="font-size: 28px; font-weight: 800; margin: 8px 0;"><?= number_format($count_students) ?></div>
                        <div style="font-size: 12px; color: var(--text-muted);">
                            <span style="color: var(--success); font-weight: 700;"><?= $students_with_internship ?> มีที่ฝึกงาน</span> · 
                            <span style="color: var(--danger); font-weight: 700;"><?= $students_without_internship ?> ยังไม่มี</span>
                        </div>
                    </div>
                    <div style="width: 48px; height: 48px; background: var(--bg-main); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary); font-size: 20px;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                </div>
            </div>

            <div class="card stat-card" style="border-left: 4px solid var(--success);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">สถานประกอบการ</div>
                        <div style="font-size: 28px; font-weight: 800; margin: 8px 0;"><?= number_format($count_companies) ?></div>
                        <div style="font-size: 12px; color: var(--text-muted);">รองรับนักศึกษาฝึกงาน</div>
                    </div>
                    <div style="width: 48px; height: 48px; background: var(--bg-main); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--success); font-size: 20px;">
                        <i class="fas fa-building"></i>
                    </div>
                </div>
            </div>

            <div class="card stat-card" style="border-left: 4px solid var(--warning);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">คำขอรอพิจารณา</div>
                        <div style="font-size: 28px; font-weight: 800; margin: 8px 0;"><?= number_format($pending_requests) ?></div>
                        <div style="font-size: 12px; color: var(--text-muted);">จากทั้งหมด <?= $total_requests ?> คำขอ</div>
                    </div>
                    <div style="width: 48px; height: 48px; background: var(--bg-main); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--warning); font-size: 20px;">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
            </div>

            <div class="card stat-card" style="border-left: 4px solid var(--danger);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 13px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">รายงานปัญหารอแก้ไข</div>
                        <div style="font-size: 28px; font-weight: 800; margin: 8px 0;"><?= number_format($pending_reports) ?></div>
                        <div style="font-size: 12px; color: var(--text-muted);">แก้ไขแล้ว <?= $resolved_reports ?> รายการ</div>
                    </div>
                    <div style="width: 48px; height: 48px; background: var(--bg-main); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--danger); font-size: 20px;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
            <!-- Department Stats -->
            <div class="card" style="padding: 0;">
                <div style="padding: 20px; border-bottom: 1px solid var(--border); font-weight: 700;">
                    <i class="fas fa-building-columns me-2 color-primary"></i> สถิติตามแผนกวิชา
                </div>
                <div style="padding: 20px;">
                    <?php 
                    $stats_by_dept->data_seek(0);
                    while($dept = $stats_by_dept->fetch_assoc()): 
                        $percentage = $dept['total_students'] > 0 ? round(($dept['students_with_internship'] / $dept['total_students']) * 100) : 0;
                    ?>
                    <div style="margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                            <span style="font-weight: 600;"><?= htmlspecialchars($dept['dep_name']) ?></span>
                            <span style="color: var(--text-muted);"><?= $dept['students_with_internship'] ?>/<?= $dept['total_students'] ?> คน (<?= $percentage ?>%)</span>
                        </div>
                        <div style="height: 8px; background: #EEF2FF; border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: <?= $percentage ?>%; background: var(--primary); border-radius: 4px; transition: width 0.5s;"></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Level Stats -->
            <div class="card" style="padding: 0;">
                <div style="padding: 20px; border-bottom: 1px solid var(--border); font-weight: 700;">
                    <i class="fas fa-layer-group me-2" style="color: var(--success);"></i> สถิติตามระดับชั้น
                </div>
                <div style="padding: 20px;">
                    <?php 
                    $stats_by_level->data_seek(0);
                    while($level = $stats_by_level->fetch_assoc()): 
                        $percentage = $level['total'] > 0 ? round(($level['with_internship'] / $level['total']) * 100) : 0;
                    ?>
                    <div style="margin-bottom: 16px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 13px;">
                            <span style="font-weight: 600;"><?= htmlspecialchars($level['std_level']) ?></span>
                            <span style="color: var(--text-muted);"><?= $level['with_internship'] ?>/<?= $level['total'] ?> คน (<?= $percentage ?>%)</span>
                        </div>
                        <div style="height: 8px; background: #DCFCE7; border-radius: 4px; overflow: hidden;">
                            <div style="height: 100%; width: <?= $percentage ?>%; background: var(--success); border-radius: 4px; transition: width 0.5s;"></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>

        <!-- Problem Reports -->
        <div class="card" style="padding: 0; margin-bottom: 24px;">
            <div style="padding: 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <div style="font-weight: 700;"><i class="fas fa-bug me-2" style="color: var(--danger);"></i> รายงานปัญหาจากนักศึกษา</div>
                <span style="font-size: 12px; color: var(--text-muted);">ทั้งหมด <?= $count_reports ?> รายการ</span>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 60px; text-align: center;">#</th>
                            <th>ผู้แจ้ง</th>
                            <th>หัวข้อ / รายละเอียด</th>
                            <th style="text-align: center;">สถานะ</th>
                            <th>วันที่แจ้ง</th>
                            <th style="text-align: center;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($all_reports) > 0): ?>
                            <?php while($row = $all_reports->fetch_assoc()): ?>
                                <tr>
                                    <td style="text-align: center; color: var(--text-muted); font-weight: 600;"><?= $row['report_id'] ?></td>
                                    <td data-label="ผู้แจ้ง">
                                        <div style="font-weight: 600;"><?= htmlspecialchars($row['std_name'] . ' ' . $row['std_lastname']) ?></div>
                                        <div style="font-size: 11px; color: var(--text-muted);"><?= htmlspecialchars($row['std_id']) ?></div>
                                    </td>
                                    <td data-label="หัวข้อ">
                                        <div style="font-weight: 600;"><?= htmlspecialchars($row['subject']) ?></div>
                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px; max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <?= htmlspecialchars($row['message']) ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;" data-label="สถานะ">
                                        <?php if($row['status'] == 'open'): ?>
                                            <span class="btn btn-sm" style="background: #FEE2E2; color: #EF4444; cursor: default; padding: 4px 10px; font-size: 11px;"><i class="fas fa-clock me-1"></i> รอดำเนินการ</span>
                                        <?php else: ?>
                                            <span class="btn btn-sm btn-success" style="cursor: default; padding: 4px 10px; font-size: 11px;"><i class="fas fa-check me-1"></i> เสร็จสิ้น</span>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="วันที่แจ้ง">
                                        <div style="font-size: 13px; color: var(--text-muted);">
                                            <?= date('d/m/Y', strtotime($row['created_at'])) ?><br>
                                            <span style="font-size: 11px;"><?= date('H:i', strtotime($row['created_at'])) ?> น.</span>
                                        </div>
                                    </td>
                                    <td style="text-align: center;" data-label="จัดการ">
                                        <?php if($row['status'] == 'open'): ?>
                                            <a href="resolve_report.php?id=<?= $row['report_id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('ยืนยันว่าแก้ไขปัญหานี้แล้ว?')">
                                                แก้ไขแล้ว
                                            </a>
                                        <?php else: ?>
                                            <i class="fas fa-check-circle color-success"></i>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: var(--text-muted);">
                                    <p>ยังไม่มีรายงานปัญหา</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Requests -->
        <div class="card" style="padding: 0;">
            <div style="padding: 20px; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
                <div style="font-weight: 700;"><i class="fas fa-history me-2" style="color: var(--primary);"></i> คำขอฝึกงานล่าสุด</div>
                <a href="internship_requests.php" style="font-size: 12px; color: var(--primary); font-weight: 700; text-decoration: none;">ดูทั้งหมด <i class="fas fa-chevron-right"></i></a>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>นักศึกษา</th>
                        <th>สถานประกอบการ</th>
                        <th style="text-align: center;">สถานะ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($recent_requests->num_rows > 0): ?>
                        <?php while($row = $recent_requests->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?= htmlspecialchars($row['std_name'] . ' ' . $row['std_lastname']) ?></div>
                            </td>
                            <td style="color: var(--text-muted); font-size: 13px;"><?= htmlspecialchars($row['com_name']) ?></td>
                            <td style="text-align: center;">
                                <?php 
                                $s_style = 'background: #FFF7ED; color: #D97706;';
                                $s_text = 'รอพิจารณา';
                                if ($row['status'] == 'approved') { $s_style = 'background: #ECFDF5; color: #059669;'; $s_text = 'อนุมัติ'; }
                                elseif ($row['status'] == 'rejected') { $s_style = 'background: #FEE2E2; color: #EF4444;'; $s_text = 'ปฏิเสธ'; }
                                ?>
                                <span class="btn btn-sm" style="<?= $s_style ?> cursor: default; padding: 2px 10px; font-size: 11px;"><?= $s_text ?></span>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align: center; padding: 40px; color: var(--text-muted);">ไม่มีข้อมูล</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('show');
        }
    </script>
</body>
</html>
