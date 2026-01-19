<?php
session_start();
include '../config.php';

// ตรวจสอบการ login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$std_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

// ดึงข้อมูลนักศึกษาเพื่อแสดงใน sidebar
$student = null;
if ($std_id) {
    $stmt = $conn->prepare("SELECT * FROM tb_student WHERE std_id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $std_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) $student = $res->fetch_assoc();
        $stmt->close();
    }
}

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

<header>
    <div id="leftMenu" class="sidebar w3-animate-left">
        <div class="sidebar-footer"></div>

        <?php if (!empty($student)): ?>
            <a href="profile.php" class="sidebar-profile-link" style="display:block; padding:12px 16px; border-bottom:1px solid #f1f1f1; text-decoration:none; color:inherit;">
                <div class="sidebar-profile" style="padding:0; margin:0;">
                    <div style="display:flex; gap:10px; align-items:center;">
                        <div style="width:50px;height:50px;border-radius:50%;background:#eef2f7;display:flex;align-items:center;justify-content:center;color:#2196F3;font-weight:700;">
                            <?php
                                $initials = '';
                                if (!empty($student['std_name'])) {
                                    $parts = preg_split('/\s+/', trim($student['std_name']));
                                    foreach ($parts as $p) { $initials .= mb_substr($p,0,1,'UTF-8'); if (mb_strlen($initials) >= 2) break; }
                                    $initials = mb_strtoupper($initials, 'UTF-8');
                                }
                                echo htmlspecialchars($initials ?: 'U', ENT_QUOTES, 'UTF-8');
                            ?>
                        </div>
                        <div style="font-size:0.95rem;">
                            <div style="font-weight:600;color:#222"><?php echo htmlspecialchars($student['std_name'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></div>
                            <div style="font-size:0.82rem;color:#666;margin-top:4px;">รหัส: <?php echo htmlspecialchars($student['std_id'] ?? $std_id, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div style="font-size:0.82rem;color:#666;"><?php echo htmlspecialchars($student['std_gmail'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>
                </div>
            </a>
        <?php endif; ?>

        <a href="index.php" class="nav-item"><i class="fas fa-home"></i> หน้าแรก</a>
        <a href="saved.php" class="nav-item"><i class="fas fa-bookmark"></i> ที่บันทึก</a>
         <a href="#" class="nav-item"><i class="fas fa-info-circle"></i> สถานะ</a>
        <a href="report.php" class="nav-item" style="background:#f3f8ff; color:#2196F3;"><i class="fas fa-bug"></i> รายงานปัญหา</a>
        <div class="logout-area">
            <a href="../logout.php?logout=true" class="logout-btn"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
        </div>
    </div>

    <div id="sideOverlay" class="overlay-side"></div>

    <i class="fas fa-bars menu-icon" onclick="toggleMenu()"></i>
    <div class="logo">Internfinder</div>
</header>

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
// Sidebar toggle (copied)
(() => {
    const sidebar = document.getElementById('leftMenu');
    const overlay = document.getElementById('sideOverlay');
    const menuButtons = document.querySelectorAll('.menu-icon');

    function openSidebar() {
        if (!sidebar.classList.contains('open')) {
            sidebar.classList.add('open');
            overlay.classList.add('show');
            document.body.classList.add('scroll-lock');
        }
    }

    function closeSidebar() {
        if (sidebar.classList.contains('open')) {
            sidebar.classList.remove('open');
            overlay.classList.remove('show');
            document.body.classList.remove('scroll-lock');
        }
    }

    window.toggleMenu = function() {
        if (sidebar.classList.contains('open')) closeSidebar(); else openSidebar();
    }

    if (overlay) overlay.addEventListener('click', closeSidebar);
    menuButtons.forEach(btn => btn.addEventListener('click', openSidebar));
})();

// Submit report
document.getElementById('submitBtn').addEventListener('click', async function(){
    const subject = document.getElementById('subject').value.trim();
    const message = document.getElementById('message').value.trim();
    const msgEl = document.getElementById('formMsg');
    msgEl.textContent = '';

    if (!subject || !message) { msgEl.textContent = 'โปรดกรอกหัวข้อและรายละเอียด'; return; }

    try {
        const form = new FormData();
        form.append('subject', subject);
        form.append('message', message);
        const res = await fetch('../api/api_report_issue.php', { method: 'POST', body: form, credentials: 'same-origin' });
        const data = await res.json();
        if (data && data.success) {
            // prepend new item in the list
            const list = document.getElementById('reportList');
            const div = document.createElement('div');
            div.className = 'report-item';
            div.setAttribute('data-id', data.report_id);
            const now = data.created_at || new Date().toISOString().slice(0,19).replace('T',' ');
            div.innerHTML = `<div class="report-meta">#${data.report_id} — <strong>${escapeHtml(subject)}</strong><span style="float:right;" class="status-open">open</span></div><div>${nl2br(escapeHtml(message))}</div><div class="report-meta">ส่งเมื่อ: ${escapeHtml(now)}</div>`;
            list.insertBefore(div, list.firstChild);
            document.getElementById('subject').value = '';
            document.getElementById('message').value = '';
            msgEl.textContent = 'ส่งเรียบร้อยแล้ว';
            setTimeout(()=> msgEl.textContent = '', 3000);
        } else {
            msgEl.textContent = 'ไม่สามารถส่งรายงานได้';
        }
    } catch (err) {
        console.error(err);
        document.getElementById('formMsg').textContent = 'เกิดข้อผิดพลาดขณะส่ง';
    }
});

function escapeHtml(s){
    return String(s).replace(/[&<>"']/g, function(c){
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'   
        }[c];
    });
}

function nl2br(s){ return s.replace(/\n/g, '<br>'); }
</script>


</body>
</html>