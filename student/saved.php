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

// ดึงบริษัทที่บันทึกไว้
$savedCompanies = [];
if ($std_id) {
    $sql = "SELECT s.save_id, s.com_id, s.created_at, c.* FROM tb_saved s INNER JOIN tb_company c ON s.com_id = c.com_id WHERE s.std_id = ? ORDER BY s.created_at DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $std_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) $savedCompanies[] = $r;
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
    <title>Internfider - ที่บันทึก</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
</head>
<body>
    <header>
        <div id="leftMenu" class="sidebar w3-animate-left">
            <div class="sidebar-footer">
            </div>

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
            <a href="saved.php" class="nav-item" style="background:#f3f8ff; color:#2196F3;"><i class="fas fa-bookmark"></i> ที่บันทึก</a>
            <a href="#" class="nav-item"><i class="fas fa-info-circle"></i> สถานะ</a>
            <a href="report.php" class="nav-item"><i class="fas fa-user"></i> รายงานปัญหา</a>
            <div class="logout-area">
                <a href="../logout.php?logout=true" class="logout-btn"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
            </div>
        </div>

        <div id="sideOverlay" class="overlay-side"></div>

        <i class="fas fa-bars menu-icon" onclick="toggleMenu()"></i>
        <div class="logo">Internfinder</div>
    </header>

    <main style="padding-bottom: 60px;">
        <div class="page-title">ที่บันทึก</div>

        <div class="company-list" style="padding: 0 15px;">
            <?php if (count($savedCompanies) === 0): ?>
                <div class="card">
                    <div class="card-body">
                        <div style="flex:1;">
                            <div class="empty-state">ยังไม่มีบริษัทที่บันทึกไว้</div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($savedCompanies as $row): ?>
                    <div class="card" data-save-id="<?php echo (int)$row['save_id']; ?>" id="card-<?php echo (int)$row['com_id']; ?>">
                        <div class="card-body">
                            <div class="company-logo"><i class="fas fa-building"></i></div>
                            <div class="company-info">
                                <h3><?php echo htmlspecialchars($row['com_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['com_tel'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="address"><?php echo htmlspecialchars(mb_strimwidth($row['com_address'], 0, 50, "..."), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <button class="save-btn" type="button" data-com-id="<?php echo (int)$row['com_id']; ?>" aria-label="บันทึกบริษัท">
                                <i class="fas fa-bookmark"></i>
                            </button>
                        </div>
                        <div class="card-footer">
                            <span class="tag">บันทึกเมื่อ: <?php echo htmlspecialchars($row['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                            <a href="details.php?id=<?php echo (int)$row['com_id']; ?>" class="view-more">ดูรายละเอียด</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

<script>
// Sidebar toggle (copied from dashboard, minimal)
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

// Save toggle using existing API; on unsave remove the card from the list
(function(){
    async function toggleSave(comId, btn){
        try {
            const form = new FormData();
            form.append('com_id', comId);
            const res = await fetch('../api/api_save_company.php', { method: 'POST', body: form, credentials: 'same-origin' });
            const data = await res.json();
            if (data && data.success) {
                const icon = btn.querySelector('i');
                if (data.saved) {
                    icon.classList.remove('far'); icon.classList.add('fas');
                } else {
                    // removed: update UI by removing card
                    const card = btn.closest('.card');
                    if (card) {
                        card.parentNode.removeChild(card);
                    }
                }
            } else {
                alert('ไม่สามารถบันทึกได้ในขณะนี้');
            }
        } catch (err) {
            console.error(err);
            alert('เกิดข้อผิดพลาดขณะบันทึก');
        }
    }

    document.addEventListener('click', function(e){
        const btn = e.target.closest('.save-btn');
        if (!btn) return;
        const comId = btn.getAttribute('data-com-id');
        if (!comId) return;
        toggleSave(comId, btn);
    });
})();
</script>

</body>
</html>