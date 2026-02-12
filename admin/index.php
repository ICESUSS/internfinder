<?php
session_start();
include __DIR__ . '/../config.php';
include __DIR__ . '/../includes/mail_helper.php';

/* ===== ตลอดชุดคำสั่ง (Base URL) ===== */
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$base_url .= preg_replace('/(\/admin\/).*/', '/', $_SERVER['SCRIPT_NAME']);

/* ===== ตรวจสอบสิทธิ์ ===== */
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

/* ===== ดึงข้อมูลสถิติ ===== */

// นักศึกษาทั้งหมด
$std_all = $conn->query("SELECT COUNT(*) AS total FROM tb_student")
                ->fetch_assoc()['total'];

// นักศึกษายังไม่มีที่ฝึกงาน
$std_no_intern = $conn->query("
    SELECT COUNT(*) AS total 
    FROM tb_student 
    WHERE std_id NOT IN (SELECT std_id FROM tb_internship WHERE status = 'approved')
")->fetch_assoc()['total'];

// นักศึกษามีที่ฝึกงานแล้ว
$std_have_intern = $conn->query("
    SELECT COUNT(*) AS total 
    FROM tb_student 
    WHERE std_id IN (SELECT std_id FROM tb_internship WHERE status = 'approved')
")->fetch_assoc()['total'];

// สถานประกอบการ
$company_total = $conn->query("
    SELECT COUNT(*) AS total FROM tb_company
")->fetch_assoc()['total'];

/* ===== จัดการคำอนุมัติแบบกลุ่ม (Bulk Approval) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_approve'])) {
    if (!empty($_POST['selected_ids'])) {
        $selected_ids = array_map('intval', $_POST['selected_ids']);
        $ids_str = implode(',', $selected_ids);
        
        // 1. อัปเดตสถานะเป็น approved สำหรับที่เลือกทั้งหมด
        $conn->query("UPDATE tb_internship SET status = 'approved' WHERE intern_id IN ($ids_str)");
        
        // 2. ดึงข้อมูลและจัดกลุ่มตาม Position (Detail ID)
        $sql_grouped = "SELECT i.*, s.std_name, s.std_lastname, c.com_name, c.com_email, d.job_title 
                        FROM tb_internship i
                        JOIN tb_student s ON i.std_id = s.std_id
                        JOIN tb_company c ON i.com_id = c.com_id
                        JOIN tb_company_detail d ON i.detail_id = d.detail_id
                        WHERE i.intern_id IN ($ids_str)
                        ORDER BY i.detail_id";
        $res_grouped = $conn->query($sql_grouped);
        
        $groups = [];
        while ($row = $res_grouped->fetch_assoc()) {
            $groups[$row['detail_id']][] = $row;
        }
        
        $total_sent = 0;
        foreach ($groups as $detail_id => $students) {
            $first = $students[0];
            $to = $first['com_email'];
            
            if (!empty($to)) {
                $subject = "แจ้งเตือน: มีนักศึกษา " . count($students) . " คน สนใจฝึกงาน (" . $first['job_title'] . ")";
                $message = "เรียน " . $first['com_name'] . "\n\n";
                $message .= "มีนักศึกษาสนใจสมัครงานในตำแหน่ง " . $first['job_title'] . " จำนวน " . count($students) . " คน ดังรายชื่อต่อไปนี้:\n\n";
                
                $attachments = [];
                $upload_path = __DIR__ . '/../uploads/';
                
                foreach ($students as $index => $std) {
                    $num = $index + 1;
                    $message .= "$num. " . $std['std_name'] . " " . $std['std_lastname'] . "\n";
                    if (!empty($std['note'])) {
                        $message .= "   เหตุผล: " . $std['note'] . "\n";
                    }
                    $message .= "\n";
                    
                    // รวมไฟล์แนบ
                    if (!empty($std['resume_file']) && file_exists($upload_path . $std['resume_file'])) {
                        $attachments[] = $upload_path . $std['resume_file'];
                    }
                    if (!empty($std['transcript_file']) && file_exists($upload_path . $std['transcript_file'])) {
                        $attachments[] = $upload_path . $std['transcript_file'];
                    }
                }
                
                $message .= "ผ่านระบบ Internfinder (ไฟล์ Resume และ Transcript แนบมาพร้อมเมลนี้)\n\n";
                $message .= "ขอบคุณครับ\nInternfinder System";
                
                if (send_internship_mail($to, $subject, $message, $attachments)) {
                    $id_list = implode(',', array_column($students, 'intern_id'));
                    $conn->query("UPDATE tb_internship SET com_email_sent = 1 WHERE intern_id IN ($id_list)");
                    $total_sent += count($students);
                }
            }
        }
        
        $msg = "ดำเนินการอนุมัติแบบกลุ่มเรียบร้อยแล้ว (ส่งเมลสำเร็จ $total_sent ราย)";
        $msg_type = "success";
    } else {
        $msg = "กรุณาเลือกรายการที่ต้องการอนุมัติ";
        $msg_type = "error";
    }
}

/* ===== จัดการคำอนุมัติจากหน้า Dashboard ===== */
$msg = '';
$msg_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $intern_id = 0;
    $send_mail = false;
    $is_send_only = false;

    if (isset($_POST['approve_with_mail'])) {
        $intern_id = (int)$_POST['approve_with_mail'];
        $send_mail = true;
    } elseif (isset($_POST['approve_only'])) {
        $intern_id = (int)$_POST['approve_only'];
        $send_mail = false;
    } elseif (isset($_POST['send_mail_only'])) {
        $intern_id = (int)$_POST['send_mail_only'];
        $send_mail = true;
        $is_send_only = true;
    }

    if ($intern_id > 0) {
        $send_mail = $send_mail; 
        
        // อัปเดตสถานะ (ถ้าเป็นการกดปุ่มส่งเมลอย่างเดียว จะไม่อัปเดตสถานะอีกรอบ)
        if (!$is_send_only) {
            $stmt = $conn->prepare("UPDATE tb_internship SET status = 'approved' WHERE intern_id = ?");
            $stmt->bind_param("i", $intern_id);
            $stmt->execute();
        }
    
    // ดึงข้อมูลเพื่อส่งเมลและแจ้งเตือน
    $sql_info = "SELECT i.*, s.std_name, s.std_lastname, c.com_name, c.com_email, d.job_title, i.resume_file, i.transcript_file, i.note 
                  FROM tb_internship i
                 JOIN tb_student s ON i.std_id = s.std_id
                 JOIN tb_company c ON i.com_id = c.com_id
                 JOIN tb_company_detail d ON i.detail_id = d.detail_id
                 WHERE i.intern_id = ?";
    $stmt_info = $conn->prepare($sql_info);
    $stmt_info->bind_param("i", $intern_id);
    $stmt_info->execute();
    $res = $stmt_info->get_result();
    
    if ($row = $res->fetch_assoc()) {
        $mail_sent_msg = "";
        $is_sent = 0;
        
        if ($send_mail) {
            $to = $row['com_email'];
            if (!empty($to)) {
                $subject = "แจ้งเตือน: มีนักศึกษาสนใจฝึกงาน (" . $row['std_name'] . ")";
                $message = "เรียน " . $row['com_name'] . "\n\n";
                $message .= "มีนักศึกษาชื่อ " . $row['std_name'] . " " . $row['std_lastname'] . " สนใจสมัครงานในตำแหน่ง " . $row['job_title'] . "\n";
                if (!empty($row['note'])) {
                    $message .= "เหตุผลที่อยากฝึกงาน: " . $row['note'] . "\n";
                }
                $message .= "ผ่านระบบ Internfinder (ไฟล์ Resume และ Transcript แนบมาพร้อมเมลนี้)\n\n";
                $message .= "ขอบคุณครับ\nInternfinder System";

                // เตรียมไฟล์แนบ
                $attachments = [];
                $upload_path = __DIR__ . '/../uploads/';
                if (!empty($row['resume_file']) && file_exists($upload_path . $row['resume_file'])) {
                    $attachments[] = $upload_path . $row['resume_file'];
                }
                if (!empty($row['transcript_file']) && file_exists($upload_path . $row['transcript_file'])) {
                    $attachments[] = $upload_path . $row['transcript_file'];
                }

                if (send_internship_mail($to, $subject, $message, $attachments)) {
                    $mail_sent_msg = " และส่งอีเมลแจ้งสถานประกอบการแล้ว";
                    $is_sent = 1;
                } else {
                    $mail_sent_msg = " (แต่ส่งอีเมลไม่สำเร็จ)";
                }
            } else {
                $mail_sent_msg = " (ไม่พบอีเมลของสถานประกอบการ)";
            }
            
            // อัปเดตสถานะการส่งอีเมล
            if ($is_sent) {
                $stmt_upd = $conn->prepare("UPDATE tb_internship SET com_email_sent = 1 WHERE intern_id = ?");
                $stmt_upd->bind_param("i", $intern_id);
                $stmt_upd->execute();
            }
        }
        
        // แจ้งเตือนนักศึกษา (ถ้ายังไม่ได้แจ้ง)
        if (!$is_send_only) {
            $stmt_notif = $conn->prepare("INSERT INTO tb_notifications (std_id, title, message) VALUES (?, ?, ?)");
            $n_title = "อนุมัติฝึกงาน: " . $row['com_name'];
            $n_msg = "คำขอฝึกงานของคุณได้รับการอนุมัติแล้ว";
            $stmt_notif->bind_param("sss", $row['std_id'], $n_title, $n_msg);
            $stmt_notif->execute();
        }
    }
    $msg = ($is_send_only ? "ดำเนินการส่งเมลเรียบร้อยแล้ว" : "อนุมัติเรียบร้อยแล้ว") . $mail_sent_msg;
    $msg_type = "success";
    }
}

/* ===== ดึงคำร้องที่รอการอนุมัติ หรือ อนุมัติแล้วแต่ยังไม่ได้ส่งเมล ===== */
$pending_requests = $conn->query("
    SELECT i.*, s.std_name, s.std_lastname, c.com_name, d.job_title 
    FROM tb_internship i
    JOIN tb_student s ON i.std_id = s.std_id
    JOIN tb_company c ON i.com_id = c.com_id
    JOIN tb_company_detail d ON i.detail_id = d.detail_id
    WHERE i.status = 'pending' 
       OR (i.status = 'approved' AND i.com_email_sent = 0)
    ORDER BY i.status DESC, i.intern_id DESC
");
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แผงควบคุมแอดมิน | InternFinder</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <?php if ($msg): ?>
            <div style="padding:15px; background:<?= $msg_type=='success'?'#dcfce7':'#fee2e2' ?>; color:<?= $msg_type=='success'?'#166534':'#991b1b' ?>; border-radius:15px; margin-bottom:24px; font-size:14px; box-shadow: var(--shadow-sm); border: 1px solid <?= $msg_type=='success'?'#bbf7d0':'#fecaca' ?>;">
                <i class="fas <?= $msg_type=='success'?'fa-check-circle':'fa-exclamation-circle' ?> me-2"></i> <?= $msg ?>
                <?php if (isset($_SESSION['mail_error'])): ?>
                    <div style="font-size: 11px; margin-top: 10px; color: #991b1b; background: rgba(0,0,0,0.03); padding: 10px; border-radius: 8px; border: 1px dashed rgba(255,0,0,0.1);">
                        <strong>Debug:</strong> <?= htmlspecialchars($_SESSION['mail_error']) ?>
                    </div>
                    <?php unset($_SESSION['mail_error']); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="top-bar">
            <div class="welcome-text">
                <h1>ยินดีต้อนรับ, แอดมิน 👋</h1>
                <p>รายงานภาพรวมระบบ InternFinder วันนี้</p>
            </div>
            <div class="header-tools">
                <span style="font-size: 13px; color: var(--text-muted); font-weight: 500;">
                    <i class="far fa-calendar-alt me-1"></i> <?= date('d M Y') ?>
                </span>
            </div>
        </div>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: #EEF2FF; color: #4F46E5;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <p>นักศึกษาทั้งหมด</p>
                    <h2><?= number_format($std_all) ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #FFF7ED; color: #F59E0B;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-info">
                    <p>รอที่ฝึกงาน</p>
                    <h2><?= number_format($std_no_intern) ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #ECFDF5; color: #10B981;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <p>ฝึกงานแล้ว</p>
                    <h2><?= number_format($std_have_intern) ?></h2>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #F8FAFC; color: #64748B;">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-info">
                    <p>บริษัท</p>
                    <h2><?= number_format($company_total) ?></h2>
                </div>
            </div>
        </div>

        <div class="dashboard-grid">
            <!-- PENDING TABLE -->
            <div class="card" style="margin-bottom: 0;">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-clock text-warning"></i> คำร้องที่รอดำเนินการ</h3>
                    <a href="internship_requests.php" class="btn" style="color: var(--primary);">ดูทั้งหมด <i class="fas fa-chevron-right"></i></a>
                </div>

                <?php if ($pending_requests->num_rows === 0): ?>
                    <div style="text-align: center; padding: 60px; color: var(--text-muted);">
                        <i class="fas fa-check-double" style="font-size: 40px; margin-bottom: 16px; opacity: 0.2;"></i>
                        <p>จัดการคำร้องทั้งหมดครบถ้วนแล้ว</p>
                    </div>
                <?php else: ?>
                    <form method="POST" id="bulkForm">
                        <div style="overflow-x: auto;">
                            <table>
                                <thead>
                                    <tr>
                                        <th style="width: 40px; text-align: center;">
                                            <input type="checkbox" id="selectAll" onclick="toggleAll(this)" style="cursor: pointer;">
                                        </th>
                                        <th style="width: 200px;">นักศึกษา</th>
                                        <th>สถานประกอบการ / ตำแหน่ง</th>
                                        <th style="text-align: center;">จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $pending_requests->fetch_assoc()): ?>
                                        <tr>
                                            <td style="text-align: center;" data-label="เลือก">
                                                <input type="checkbox" name="selected_ids[]" value="<?= $row['intern_id'] ?>" class="item-checkbox">
                                            </td>
                                            <td data-label="นักศึกษา">
                                                <div style="font-weight: 700; font-size: 14px;"><?= htmlspecialchars($row['std_name'] . ' ' . $row['std_lastname']) ?></div>
                                                <div style="font-size: 11px; margin-top: 3px;">
                                                    <?php if ($row['status'] == 'pending'): ?>
                                                        <span style="color: #D97706; background: #FFFBEB; padding: 2px 6px; border-radius: 4px;">รออนุมัติ</span>
                                                    <?php else: ?>
                                                        <span style="color: #059669; background: #ECFDF5; padding: 2px 6px; border-radius: 4px;">อนุมัติแล้ว (รอเมล)</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td data-label="สถานประกอบการ / ตำแหน่ง">
                                                <div style="font-size: 13px; font-weight: 600;"><?= htmlspecialchars($row['com_name']) ?></div>
                                                <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;"><?= htmlspecialchars($row['job_title']) ?></div>
                                                <?php if (!empty($row['com_email'])): ?>
                                                    <div style="font-size: 11px; color: var(--primary); font-weight: 500; margin-top: 4px;">
                                                        <i class="far fa-envelope"></i> <?= htmlspecialchars($row['com_email']) ?>
                                                    </div>
                                                <?php else: ?>
                                                    <div style="font-size: 11px; color: var(--danger); font-weight: 500; margin-top: 4px;">
                                                        <i class="fas fa-exclamation-circle"></i> ไม่มีอีเมล
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td data-label="จัดการ">
                                                <div style="display: flex; gap: 6px; justify-content: center;">
                                                    <?php if ($row['status'] == 'pending'): ?>
                                                        <button type="submit" name="approve_with_mail" value="<?= $row['intern_id'] ?>" class="btn btn-primary" title="อนุมัติและส่งเมล" onclick="return confirm('อนุมัติและส่งอีเมลแจ้งบริษัท?')">
                                                            <i class="fas fa-paper-plane"></i>
                                                        </button>
                                                        <button type="submit" name="approve_only" value="<?= $row['intern_id'] ?>" class="btn btn-secondary" title="อนุมัติเท่านั้น" onclick="return confirm('อนุมัติโดยไม่ส่งอีเมล?')">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" name="send_mail_only" value="<?= $row['intern_id'] ?>" class="btn btn-indigo" title="ส่งอีเมลแจ้งบริษัท" onclick="return confirm('ส่งอีเมลแจ้งสถานประกอบการ?')">
                                                            <i class="fas fa-envelope"></i> แจ้งบริษัท
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div style="margin-top: 20px; display: flex; justify-content: flex-end;">
                            <button type="submit" name="bulk_approve" class="btn" style="background: var(--bg-sidebar); color: white; padding: 10px 20px;" onclick="return confirm('อนุมัติและส่งเมลแบบกลุ่มสำหรับบริษัทที่เลือก?')">
                                <i class="fas fa-check-double"></i> อนุมัติและส่งเมลแบบกลุ่ม
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <!-- CHART & QUICK -->
            <div>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📊 ความคืบหน้า</h3>
                    </div>
                    <div class="chart-container">
                        <canvas id="internshipChart"></canvas>
                    </div>
                    <div style="margin-top: 24px; font-size: 13px; text-align: center; color: var(--text-muted); line-height: 1.6;">
                        เป้าหมายปีนี้: นักศึกษาทุกคนมีที่ฝึกงาน<br>
                        <span style="font-weight: 600; color: var(--success); text-decoration: underline;">เข้าใกล้เป้าหมาย 100% แล้ว</span>
                    </div>
                </div>

                <div class="card" style="margin-bottom:0; background: linear-gradient(135deg, #4F46E5 0%, #312E81 100%); color: white; border: none;">
                    <h3 style="font-size: 16px; margin-bottom: 8px;">ศูนย์ช่วยเหลือแอดมิน</h3>
                    <p style="font-size: 12px; opacity: 0.8; margin-bottom: 20px;">หากพบปัญหาการใช้งานระบบหรือต้องการความช่วยเหลือเร่งด่วน</p>
                    <a href="#" class="btn" style="background: rgba(255,255,255,0.2); color: white; width: 100%; justify-content: center; backdrop-filter: blur(5px);">
                        <i class="fas fa-headset"></i> ติดต่อฝ่ายไอที
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleAll(source) {
            const checkboxes = document.getElementsByClassName('item-checkbox');
            for(let i=0; i<checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }

        // CHART
        const ctx = document.getElementById('internshipChart').getContext('2d');
        const centerTextPlugin = {
            id: 'centerText',
            afterDraw: (chart) => {
                if (chart.config.type !== 'doughnut') return;
                const { ctx, chartArea: { top, bottom, left, right, width, height } } = chart;
                ctx.save();
                const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                const haveIntern = chart.data.datasets[0].data[1];
                const percentage = total > 0 ? Math.round((haveIntern / total) * 100) : 0;

                ctx.font = 'bold 32px Inter';
                ctx.fillStyle = '#1E293B';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(`${percentage}%`, left + width / 2, top + height / 2 - 8);
                
                ctx.font = '600 11px Inter';
                ctx.fillStyle = '#64748B';
                ctx.fillText('สถิติล่าสุด', left + width / 2, top + height / 2 + 22);
                ctx.restore();
            }
        };

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['ยังไม่มีที่ฝึกงาน', 'มีที่ฝึกงานแล้ว'],
                datasets: [{
                    data: [<?= $std_no_intern ?>, <?= $std_have_intern ?>],
                    backgroundColor: ['#FCA5A5', '#34D399'],
                    hoverBackgroundColor: ['#EF4444', '#10B981'],
                    borderWidth: 0,
                    borderRadius: 10,
                    spacing: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '80%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        backgroundColor: '#1E293B',
                        padding: 12,
                        cornerRadius: 10,
                        titleFont: { family: 'Inter', size: 13 },
                        bodyFont: { family: 'Inter', size: 12 }
                    }
                }
            },
            plugins: [centerTextPlugin]
        });
    </script>
</body>
</html>
