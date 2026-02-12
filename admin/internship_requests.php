<?php
session_start();
include __DIR__ . '/../config.php';
include __DIR__ . '/../includes/mail_helper.php';

/* ===== ตลอดชุดคำสั่ง (Base URL) ===== */
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$base_url .= preg_replace('/(\/admin\/).*/', '/', $_SERVER['SCRIPT_NAME']);

// Check Admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

/* ===== จัดการคำอนุมัติแบบกลุ่ม (Bulk Approval) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_approve'])) {
    if (!empty($_POST['selected_ids'])) {
        $selected_ids = array_map('intval', $_POST['selected_ids']);
        $ids_str = implode(',', $selected_ids);
        
        // 1. อัปเดตสถานะเป็น approved สำหรับที่เลือกทั้งหมด
        $conn->query("UPDATE tb_internship SET status = 'approved' WHERE intern_id IN ($ids_str)");
        
        // 2. ดึงข้อมูลและจัดกลุ่มตาม Position (Detail ID)
        $sql_grouped = "SELECT i.*, s.std_name, s.std_lastname, c.com_name, c.com_email, d.job_title, i.resume_file, i.transcript_file, i.note 
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

// Handle Approve
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id'])) {
    $intern_id = (int) $_POST['approve_id'];

    // Update status
    $stmt = $conn->prepare("UPDATE tb_internship SET status = 'approved' WHERE intern_id = ?");
    $stmt->bind_param("i", $intern_id);
    
    if ($stmt->execute()) {
        $send_mail = isset($_POST['approve_with_mail']) || isset($_POST['send_mail_only']);
        
        if ($send_mail) {
            // Fetch data for email
            $sql = "SELECT i.*, s.std_name, s.std_lastname, c.com_name, c.com_email, d.job_title 
                    FROM tb_internship i
                    JOIN tb_student s ON i.std_id = s.std_id
                    JOIN tb_company c ON i.com_id = c.com_id
                    JOIN tb_company_detail d ON i.detail_id = d.detail_id
                    WHERE i.intern_id = ?";
            $stmt_mail = $conn->prepare($sql);
            $stmt_mail->bind_param("i", $intern_id);
            $stmt_mail->execute();
            $row = $stmt_mail->get_result()->fetch_assoc();

            if ($row && !empty($row['com_email'])) {
                $to = $row['com_email'];
                $subject = "แจ้งนักศึกษาฝึกงาน: " . $row['std_name'] . " " . $row['std_lastname'];
                $message = "เรียน " . $row['com_name'] . "\n\n";
                $message .= "มีนักศึกษาชื่อ " . $row['std_name'] . " " . $row['std_lastname'] . " สนใจสมัครงานในตำแหน่ง " . $row['job_title'] . "\n";
                if (!empty($row['note'])) {
                    $message .= "เหตุผลที่อยากฝึกงาน: " . $row['note'] . "\n";
                }
                $message .= "ผ่านระบบ Internfinder (ไฟล์ Resume และ Transcript แนบมาพร้อมเมลนี้)\n\n";
                $message .= "ขอบคุณครับ\nInternfinder System";

                $attachments = [];
                $upload_path = __DIR__ . '/../uploads/';
                if (!empty($row['resume_file']) && file_exists($upload_path . $row['resume_file'])) {
                    $attachments[] = $upload_path . $row['resume_file'];
                }
                if (!empty($row['transcript_file']) && file_exists($upload_path . $row['transcript_file'])) {
                    $attachments[] = $upload_path . $row['transcript_file'];
                }

                if (send_internship_mail($to, $subject, $message, $attachments)) {
                    $conn->query("UPDATE tb_internship SET com_email_sent = 1 WHERE intern_id = " . $intern_id);
                    $msg = "อนุมัติคำร้องและส่งเมลแจ้งสถานประกอบการเรียบร้อยแล้ว";
                    $msg_type = "success";
                } else {
                    $msg = "อนุมัติสำเร็จ แต่ส่งอีเมลล้มเหลว (ตรวจสอบ Config)";
                    $msg_type = "warning";
                }
            } else {
                $msg = "อนุมัติสำเร็จ แต่ไม่พบอีเมลบริษัท";
                $msg_type = "warning";
            }
        } else {
            $msg = "อนุมัติคำร้องเรียบร้อยแล้ว";
            $msg_type = "success";
        }
    } else {
        $msg = "เกิดข้อผิดพลาดในการอนุมัติ";
        $msg_type = "error";
    }
}

// Fetch Filters
$filter_dep = $_GET['dep_id'] ?? '';
$filter_level = $_GET['level'] ?? '';
$filter_room = $_GET['room'] ?? '';

// Get Departments
$deps = [];
$res_dep = $conn->query("SELECT * FROM tb_department ORDER BY dep_name");
while($d = $res_dep->fetch_assoc()) $deps[] = $d;

// Get Available Levels
$levels = ['ปวช. 1', 'ปวช. 2', 'ปวช. 3', 'ปวส. 1', 'ปวส. 2'];

// Get Rooms
$rooms = [];
$sql_r = "SELECT DISTINCT std_room FROM tb_student WHERE std_room != '' ORDER BY std_room";
$res_r = $conn->query($sql_r);
while($r = $res_r->fetch_assoc()) $rooms[] = $r['std_room'];

// --- Main Query with Filters ---
$sql = "SELECT i.*, s.std_name, s.std_lastname, s.std_level, s.std_room, dep.dep_name, c.com_name, d.job_title 
        FROM tb_internship i
        JOIN tb_student s ON i.std_id = s.std_id
        LEFT JOIN tb_department dep ON s.dep_id = dep.dep_id
        JOIN tb_company c ON i.com_id = c.com_id
        JOIN tb_company_detail d ON i.detail_id = d.detail_id
        WHERE 1";

$params = [];
$types = "";

if ($filter_dep != '') {
    $sql .= " AND s.dep_id = ?";
    $params[] = $filter_dep;
    $types .= "s";
}
if ($filter_level != '') {
    $sql .= " AND s.std_level = ?";
    $params[] = $filter_level;
    $types .= "s";
}
if ($filter_room != '') {
    $sql .= " AND s.std_room = ?";
    $params[] = $filter_room;
    $types .= "s";
}

$sql .= " ORDER BY CASE WHEN i.status='pending' THEN 0 ELSE 1 END, i.intern_id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำร้องฝึกงาน | InternFinder</title>
    
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
                <h1>พิจารณาคำร้องฝึกงาน</h1>
                <p>ตรวจสอบและอนุมัติใบสมัครฝึกงานของนักศึกษา</p>
            </div>
            <div class="header-tools">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> กลับแผงควบคุม
                </a>
            </div>
        </div>

        <?php if (isset($msg)): ?>
            <div class="card" style="background: <?= $msg_type=='success'?'#DCFCE7':'#FEE2E2' ?>; color: <?= $msg_type=='success'?'#166534':'#991B1B' ?>; padding: 16px; border: 1px solid <?= $msg_type=='success'?'#BBF7D0':'#FECACA' ?>; margin-bottom: 24px;">
                <i class="fas <?= $msg_type=='success'?'fa-check-circle':'fa-exclamation-circle' ?> me-2"></i> <?= $msg ?>
                <?php if (isset($_SESSION['mail_error'])): ?>
                    <div style="font-size: 11px; margin-top: 10px; color: #991b1b; background: rgba(0,0,0,0.03); padding: 10px; border-radius: 8px; border: 1px dashed rgba(255,0,0,0.1);">
                        <strong>Debug:</strong> <?= htmlspecialchars($_SESSION['mail_error']) ?>
                    </div>
                    <?php unset($_SESSION['mail_error']); ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Filter Area -->
        <div class="card">
            <form method="GET" style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 200px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">แผนกวิชา</label>
                    <select name="dep_id" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                        <option value="">ทั้งหมด</option>
                        <?php foreach($deps as $d): ?>
                            <option value="<?= $d['dep_id'] ?>" <?= $filter_dep == $d['dep_id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['dep_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">ระดับชั้น</label>
                    <select name="level" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                        <option value="">ทั้งหมด</option>
                        <?php foreach($levels as $l): ?>
                            <option value="<?= $l ?>" <?= $filter_level == $l ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="flex: 1; min-width: 100px;">
                    <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">ห้อง</label>
                    <select name="room" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                        <option value="">ทั้งหมด</option>
                        <?php foreach($rooms as $r): ?>
                            <option value="<?= $r ?>" <?= $filter_room == $r ? 'selected' : '' ?>><?= $r ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                    <i class="fas fa-filter"></i> กรอง
                </button>
                <a href="internship_requests.php" class="btn btn-secondary">ล้าง</a>
            </form>
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
            <form method="POST" id="bulkForm">
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 50px; text-align: center;">
                                    <input type="checkbox" id="selectAll" onclick="toggleAll(this)" style="cursor: pointer;">
                                </th>
                                <th style="width: 250px;">นักศึกษา</th>
                                <th>บริษัท / ตำแหน่ง</th>
                                <th>ข้อมูลการสมัคร</th>
                                <th style="text-align: center;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows === 0): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 60px; color: var(--text-muted);">
                                        <i class="fas fa-inbox" style="font-size: 40px; margin-bottom: 16px; opacity: 0.2;"></i>
                                        <p>ไม่พบรายการคำร้อง</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td style="text-align: center;" data-label="เลือก">
                                            <input type="checkbox" name="selected_ids[]" value="<?= $row['intern_id'] ?>" class="item-checkbox">
                                        </td>
                                        <td data-label="นักศึกษา">
                                            <div style="font-weight: 700;"><?= htmlspecialchars($row['std_name'] . ' ' . $row['std_lastname']) ?></div>
                                            <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                                                <?= htmlspecialchars($row['std_level']) ?> | <?= htmlspecialchars($row['dep_name']) ?>
                                            </div>
                                            <div style="font-size: 11px; margin-top: 6px;">
                                                <?php if ($row['status'] == 'pending'): ?>
                                                    <span style="color: #D97706; background: #FFFBEB; padding: 2px 6px; border-radius: 4px;">รออนุมัติ</span>
                                                <?php elseif ($row['status'] == 'approved' && !$row['com_email_sent']): ?>
                                                    <span style="color: #059669; background: #ECFDF5; padding: 2px 6px; border-radius: 4px;">อนุมัติแล้ว (รอเมล)</span>
                                                <?php else: ?>
                                                    <span style="color: #1D4ED8; background: #EFF6FF; padding: 2px 6px; border-radius: 4px;">ส่งลิงก์แล้ว</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td data-label="บริษัท/ตำแหน่ง">
                                            <div style="font-weight: 600;"><?= htmlspecialchars($row['com_name']) ?></div>
                                            <div style="font-size: 12px; color: var(--primary); font-weight: 600; margin-top: 2px;"><?= htmlspecialchars($row['job_title']) ?></div>
                                        </td>
                                        <td data-label="ข้อมูลการสมัคร">
                                            <div style="font-size: 12px; max-width: 250px;">
                                                <strong>เหตุผล:</strong> <?= !empty($row['note']) ? htmlspecialchars($row['note']) : '-' ?>
                                                <div style="margin-top: 8px; display: flex; gap: 8px;">
                                                    <?php if(!empty($row['resume_file'])): ?>
                                                        <a href="../uploads/<?= $row['resume_file'] ?>" target="_blank" style="color: var(--primary); text-decoration: none;"><i class="fas fa-file-pdf"></i> Resume</a>
                                                    <?php endif; ?>
                                                    <?php if(!empty($row['transcript_file'])): ?>
                                                        <a href="../uploads/<?= $row['transcript_file'] ?>" target="_blank" style="color: var(--warning); text-decoration: none;"><i class="fas fa-file-alt"></i> Transcript</a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="text-align: center;" data-label="จัดการ">
                                            <form method="POST" style="display: flex; flex-direction: column; gap: 5px; align-items: center;">
                                                <input type="hidden" name="approve_id" value="<?= $row['intern_id'] ?>">
                                                <?php if ($row['status'] == 'pending'): ?>
                                                    <button type="submit" name="approve_with_mail" class="btn btn-sm btn-primary" style="width: 100%; justify-content: center;" onclick="return confirm('อนุมัติและส่งเมล?')">อนุมัติ & ส่งเมล</button>
                                                    <button type="submit" name="approve_only" class="btn btn-sm btn-secondary" style="width: 100%; justify-content: center;" onclick="return confirm('อนุมัติเท่านั้น?')">อนุมัติเท่านั้น</button>
                                                <?php elseif (!$row['com_email_sent']): ?>
                                                    <button type="submit" name="send_mail_only" class="btn btn-sm btn-primary" style="width: 100%; justify-content: center;" onclick="return confirm('ส่งอีเมลแจ้งบริษัท?')">ส่งเมลแจ้งบริษัท</button>
                                                <?php endif; ?>
                                                <a href="print_request.php?id=<?= $row['intern_id'] ?>" class="btn btn-sm btn-secondary" style="width: 100%; justify-content: center;" target="_blank">พิมพ์คำร้อง</a>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div style="padding: 20px; display: flex; justify-content: flex-end; background: #F8FAFC; border-top: 1px solid var(--border);">
                    <button type="submit" name="bulk_approve" class="btn btn-primary" onclick="return confirm('อนุมัติและส่งเมลแบบกลุ่มสำหรับที่เลือก?')">
                        <i class="fas fa-check-double"></i> อนุมัติและส่งเมลแบบกลุ่ม
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        function toggleAll(source) {
            const checkboxes = document.getElementsByClassName('item-checkbox');
            for(let i=0; i<checkboxes.length; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('show');
        }
    </script>
</body>
</html>
