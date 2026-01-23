<?php
session_start();
include '../config.php';

// ตรวจสอบการ login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$std_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

// Student data fetching removed

// initial load reports
$reports = [];
if ($std_id) {
    $stmt = $conn->prepare("SELECT report_id, subject, message, status, created_at FROM tb_reports WHERE std_id = ? ORDER BY created_at DESC");
    if ($stmt) {
        $stmt->bind_param('i', $std_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) $reports[] = $r;
        }
        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internfider - รายงานปัญหา</title>
   <link rel="stylesheet" href="/Internfinder/assets/css/sidebar.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
    <style>
        /* Report page specific styles */
        .report-form { padding: 12px 15px; max-width: 820px; margin: 0 auto 18px; }
        .input, textarea { width: 100%; padding: 10px; border-radius: 8px; border:1px solid #e6eef9; margin-bottom: 8px; font-size: 0.95rem; }
        .btn-primary { background:#2196F3; color:#fff; border:none; padding:10px 14px; border-radius:8px; cursor:pointer; }
        .report-list { max-width: 820px; margin: 0 auto; padding: 0 15px; }
        .report-item { background:#fff; border-radius:10px; padding:12px; margin-bottom:10px; box-shadow:0 1px 6px rgba(0,0,0,0.04); }
        .report-meta { font-size:0.85rem; color:#666; margin-bottom:8px; }
        .status-open { color:#0B79D0; }
        .status-closed { color:#2E7D32; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>


<main>
    <div class="page-title">รายงานปัญหา</div>

    <div class="report-form">
        <label for="subject">หัวข้อ</label>
        <input id="subject" class="input" maxlength="255" placeholder="สรุปปัญหา เช่น " title="หัวข้อ">
        <label for="message">รายละเอียด</label>
        <textarea id="message" class="input" rows="6" placeholder="อธิบายปัญหาและขั้นตอนการทำซ้ำ"></textarea>
        <div style="display:flex; gap:10px; align-items:center;">
            <button id="submitBtn" class="btn-primary">ส่งรายงาน</button>
            <div id="formMsg" style="color:#666; font-size:0.95rem;"></div>
        </div>
    </div>

    <div class="report-list" id="reportList">
        <?php if (count($reports) === 0): ?>
            <div class="report-item">ยังไม่มีรายงาน</div>
        <?php else: ?>
            <?php foreach ($reports as $r): ?>
                <div class="report-item" data-id="<?php echo (int)$r['report_id']; ?>">
                    <div class="report-meta">#<?php echo (int)$r['report_id']; ?> — <strong><?php echo htmlspecialchars($r['subject'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        <span style="float:right;" class="<?php echo $r['status'] === 'closed' ? 'status-closed' : 'status-open'; ?>"><?php echo htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div><?php echo nl2br(htmlspecialchars($r['message'], ENT_QUOTES, 'UTF-8')); ?></div>
                    <div class="report-meta">ส่งเมื่อ: <?php echo htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>


<script>
document.getElementById('submitBtn').addEventListener('click', async function(){
    const subject = document.getElementById('subject').value.trim();
    const message = document.getElementById('message').value.trim();
    const msgEl = document.getElementById('formMsg');
    const submitBtn = this;
    
    msgEl.textContent = '';
    msgEl.style.color = '#666';

    if (!subject || !message) { 
        msgEl.textContent = 'โปรดกรอกหัวข้อและรายละเอียด'; 
        msgEl.style.color = 'red';
        return; 
    }

    try {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังส่ง...';
        
        const formData = new FormData();
        formData.append('subject', subject);
        formData.append('message', message);

        const response = await fetch('../api/api_report_issue.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            msgEl.textContent = 'ส่งรายงานสำเร็จ!';
            msgEl.style.color = 'green';
            document.getElementById('subject').value = '';
            document.getElementById('message').value = '';
            
            // Wait 1.5s then reload to show new report
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            msgEl.textContent = 'เกิดข้อผิดพลาด: ' + (data.error || 'ไม่ทราบสาเหตุ');
            msgEl.style.color = 'red';
            submitBtn.disabled = false;
            submitBtn.textContent = 'ส่งรายงาน';
        }
    } catch (err) { 
        console.error(err); 
        msgEl.textContent = 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
        msgEl.style.color = 'red';
        submitBtn.disabled = false;
        submitBtn.textContent = 'ส่งรายงาน';
    }
});
</script>


</body>
</html>