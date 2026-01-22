<?php
session_start();
include '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header("Location: ../login.php");
    exit();
}

$std_id = $_SESSION['user_id'];
$notif_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($notif_id <= 0) {
    header("Location: index.php");
    exit();
}

// Fetch notification detail
$stmt = $conn->prepare("SELECT * FROM tb_notifications WHERE id = ? AND std_id = ?");
$stmt->bind_param("is", $notif_id, $std_id);
$stmt->execute();
$result = $stmt->get_result();
$notif = $result->fetch_assoc();

if (!$notif) {
    die("ไม่พบข้อมูลการแจ้งเตือน");
}

// Mark as read
if ($notif['is_read'] == 0) {
    $upd = $conn->prepare("UPDATE tb_notifications SET is_read = 1 WHERE id = ?");
    $upd->bind_param("i", $notif_id);
    $upd->execute();
}

// Check for approved internship if this is an approval notification
$intern_link_btn = "";
if (strpos($notif['title'], 'อนุมัติ') !== false) {
    $stmt_chk = $conn->prepare("SELECT intern_id FROM tb_internship WHERE std_id = ? AND status = 'approved' ORDER BY intern_id DESC LIMIT 1");
    $stmt_chk->bind_param("s", $std_id);
    $stmt_chk->execute();
    $res_chk = $stmt_chk->get_result();
    if ($row_chk = $res_chk->fetch_assoc()) {
        $intern_link_btn = '
            <a href="print_request.php?id='.$row_chk['intern_id'].'" target="_blank" class="w3-button w3-green w3-round-large"><i class="fas fa-file-pdf"></i> พิมพ์ใบคำร้องขอฝึกงาน</a>
            <a href="../img/หนังสืออนุญาตจากผู้ปกครอง.pdf" target="_blank" class="w3-button w3-orange w3-text-white w3-round-large"><i class="fas fa-file-download"></i> ดาวน์โหลดหนังสืออนุญาตจากผู้ปกครอง</a>
        ';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดการแจ้งเตือน - Internfinder</title>
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
    <style>
        .detail-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin: 20px auto;
            max-width: 800px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .detail-header {
            border-bottom: 2px solid #f0f2f5;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .detail-title {
            color: #2196F3;
            font-weight: 600;
            margin: 0;
        }
        .detail-meta {
            font-size: 0.9rem;
            color: #888;
            margin-top: 5px;
        }
        .detail-body {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #333;
            white-space: pre-wrap;
        }
        .action-bar {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .detail-card { box-shadow: none; margin: 0; max-width: 100%; border: none; }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>


<main class="w3-container">
    <div class="detail-card">
        <div class="detail-header">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <h2 class="detail-title"><?php echo htmlspecialchars($notif['title']); ?></h2>
                <?php if (strpos($notif['title'], 'อนุมัติ') !== false): ?>
                    <span class="w3-tag w3-green w3-round" style="padding: 5px 15px;">อนุมัติแล้ว</span>
                <?php endif; ?>
            </div>
            <div class="detail-meta">
                <i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?> น.
            </div>
        </div>
        
        <div class="detail-body">
            <?php echo htmlspecialchars($notif['message']); ?>
        </div>

        <div class="action-bar no-print">
    
            <?php if ($intern_link_btn) echo $intern_link_btn; ?>
        </div>
    </div>
</main>


</body>
</html>
