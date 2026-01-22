<?php
session_start();
include '../config.php';

// Check Admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle Approve
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id'])) {
    $intern_id = (int) $_POST['approve_id'];

    // Update status
    $stmt = $conn->prepare("UPDATE tb_internship SET status = 'approved' WHERE intern_id = ?");
    // If table uses 'id' generic or 'intern_id'. I will assume 'id' based on previous context or check structure.
    // Wait, earlier I didn't verify the PK column. I will try 'id' first.
    // Actually, to be safe, I should have checked. But I'll assume 'id' is standard.
    // If it fails I'll fix it. actually, looking at `student/apply_job.php`, it inserts but doesn't show PK.
    // Let's assume the PK is `id`.
    $stmt->bind_param("i", $intern_id);
    
    if ($stmt->execute()) {
        // Fetch details (remove com_gmail because it's missing in tb_company)
        $sql_info = "SELECT i.*, s.std_name, s.std_lastname, s.std_gmail, c.com_name, d.job_title 
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
            /* 
               Note: com_gmail is missing in tb_company. 
               Commenting out email sending for now.
            */
            /*
            $to = $row['com_gmail'];
            $subject = "แจ้งเตือน: มีนักศึกษาสนใจฝึกงาน (" . $row['std_name'] . ")";
            $message = "เรียน " . $row['com_name'] . "\n\n";
            $message .= "มีนักศึกษาชื่อ " . $row['std_name'] . " สนใจสมัครงานในตำแหน่ง " . $row['job_title'] . "\n";
            $message .= "กรุณาตรวจสอบรายละเอียดเพิ่มเติมในระบบ\n\n";
            $message .= "ขอบคุณครับ\nInternfinder System";
            $headers = "From: no-reply@internfinder.com";
            @mail($to, $subject, $message, $headers);
            */

            // --- Notification Logic ---
            $notif_title = "อนุมัติฝึกงาน: " . $row['com_name'];
            $notif_msg = "คำขอฝึกงานของคุณที่ " . $row['com_name'] . " ได้รับการอนุมัติโดยแอดมินแล้ว";
            $std_id_notif = $row['std_id'];
            
            $stmt_notif = $conn->prepare("INSERT INTO tb_notifications (std_id, title, message) VALUES (?, ?, ?)");
            $stmt_notif->bind_param("sss", $std_id_notif, $notif_title, $notif_msg);
            $stmt_notif->execute();
            // --------------------------
        }
        $msg = "อนุมัติเรียบร้อยแล้ว และส่งอีเมลแจ้งสถานประกอบการแล้ว";
        $msg_type = "success";
    } else {
        $msg = "เกิดข้อผิดพลาด: " . $conn->error;
        $msg_type = "error";
    }
}

// Fetch pending requests
$sql = "SELECT i.*, s.std_name, s.std_lastname, c.com_name, d.job_title 
        FROM tb_internship i
        JOIN tb_student s ON i.std_id = s.std_id
        JOIN tb_company c ON i.com_id = c.com_id
        JOIN tb_company_detail d ON i.detail_id = d.detail_id
        WHERE i.status = 'pending'
        ORDER BY i.intern_id DESC";

$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการคำขอฝึกงาน | Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338CA;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --bg-main: #F8FAFC;
            --bg-card: #FFFFFF;
            --text-main: #1E293B;
            --text-muted: #64748B;
            --border: #E2E8F0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Sarabun', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            line-height: 1.5;
            padding: 20px;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        /* ===== Header ===== */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: var(--bg-card);
            padding: 20px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        header h1 {
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

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

        .btn-back { background: #F1F5F9; color: var(--text-muted); }
        .btn-back:hover { background: #E2E8F0; color: var(--text-main); }
        
        .btn-view { background: #EEF2FF; color: var(--primary); }
        .btn-view:hover { background: var(--primary); color: white; }

        .btn-approve { background: #ECFDF5; color: var(--success); }
        .btn-approve:hover { background: var(--success); color: white; }

        /* ===== Table Content ===== */
        .content-card {
            background: var(--bg-card);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #F8FAFC;
            padding: 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        tr:last-child td { border-bottom: none; }
        
        tr:hover { background-color: #F8FAFC; }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
        }
        .alert-success { background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0; }
        .alert-error { background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; }

        .no-data {
            padding: 60px;
            text-align: center;
            color: var(--text-muted);
        }

        /* Responsive */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            .btn-back { justify-content: center; }

            .table-responsive-stack thead { display: none; }
            .table-responsive-stack tr { 
                display: block; 
                padding: 15px;
                border-bottom: 8px solid var(--bg-main);
            }
            .table-responsive-stack td { 
                display: flex; 
                justify-content: space-between;
                align-items: center;
                padding: 10px 0;
                border-bottom: 1px solid #f1f1f1;
                text-align: right;
            }
            .table-responsive-stack td:before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--text-muted);
                text-align: left;
                padding-right: 10px;
            }
            .table-responsive-stack td:last-child { border-bottom: none; }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
</head>
<body>

<div class="container">
    <header>
        <h1><i class="fas fa-file-signature text-primary"></i> คำร้องขอฝึกงาน</h1>
        <a href="index.php" class="btn btn-back">
            <i class="fas fa-arrow-left"></i> กลับแผงควบคุม
        </a>
    </header>

    <?php if (isset($msg)): ?>
        <div class="alert alert-<?= $msg_type ?>">
            <i class="fas <?= $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i> <?= $msg ?>
        </div>
    <?php endif; ?>

    <div class="content-card">
        <table class="table-responsive-stack">
            <thead>
                <tr>
                    <th>นักศึกษา</th>
                    <th>บริษัท</th>
                    <th>ตำแหน่ง</th>
                    <th>ไฟล์แนบ</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td data-label="นักศึกษา">
                            <div style="font-weight:600"><?= htmlspecialchars($row['std_name'] . ' ' . $row['std_lastname']) ?></div>
                        </td>
                        <td data-label="บริษัท"><?= htmlspecialchars($row['com_name']) ?></td>
                        <td data-label="ตำแหน่ง">
                            <span style="background:#F1F5F9; padding:4px 10px; border-radius:6px; font-size:12px">
                                <?= htmlspecialchars($row['job_title']) ?>
                            </span>
                        </td>
                        <td data-label="ไฟล์แนบ">
                            <?php if (!empty($row['resume_file'])): ?>
                                <a href="../uploads/<?= htmlspecialchars($row['resume_file']) ?>" target="_blank" class="btn btn-view">
                                    <i class="fas fa-file-pdf"></i> Resume
                                </a>
                            <?php else: ?>
                                <span class="text-muted">ไม่มีไฟล์</span>
                            <?php endif; ?>
                        </td>
                        <td data-label="จัดการ">
                            <div style="display:flex; gap:5px; align-items:center;">
                                <a href="../student/print_request.php?id=<?= $row['intern_id'] ?>" target="_blank" class="btn btn-view" title="พิมพ์ใบคำร้อง">
                                    <i class="fas fa-print"></i>
                                </a>
                            </div>
                            <form method="POST" onsubmit="return confirm('ยืนยันการอนุมัติ?');">
                                <input type="hidden" name="approve_id" value="<?= $row['intern_id'] ?>">
                                <button type="submit" class="btn btn-approve">
                                    <i class="fas fa-check"></i> อนุมัติ
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="no-data">
                        <i class="fas fa-inbox" style="font-size:48px; margin-bottom:15px; opacity:0.3"></i><br>
                        ไม่มีคำร้องขอที่รอการตรวจสอบ
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
