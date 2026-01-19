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
        $stmt->bind_param('s', $std_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows > 0) $student = $res->fetch_assoc();
        $stmt->close();
    }
}

// ดึงรายการบริษัทที่ถูกบันทึกไว้ของนักศึกษานี้ (เพื่อทำให้ปุ่มแสดงสถานะ)
$savedIds = [];
if ($std_id) {
    $sStmt = $conn->prepare("SELECT com_id FROM tb_saved WHERE std_id = ?");
    if ($sStmt) {
        $sStmt->bind_param('s', $std_id);
        $sStmt->execute();
        $sRes = $sStmt->get_result();
        if ($sRes) {
            while ($r = $sRes->fetch_assoc()) $savedIds[] = (int)$r['com_id'];
        }
        $sStmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Internfider - ระบบค้นหาที่ฝึกงาน</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/5/w3.css">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
</head>



<body>


     <header>
    <!-- NEW Sidebar -->
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
                            <div style="font-weight:600;color:#222"><?php echo htmlspecialchars(($student['std_name'] ?? '-') . ' ' . ($student['std_lastname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                            <div style="font-size:0.82rem;color:#666;margin-top:4px;">รหัส: <?php echo htmlspecialchars($student['std_id'] ?? $std_id, ENT_QUOTES, 'UTF-8'); ?></div>
                            <div style="font-size:0.82rem;color:#666;"><?php echo htmlspecialchars($student['std_gmail'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>
                </div>
            </a>
        <?php endif; ?>

        <a href="index.php" class="nav-item"><i class="fas fa-home"></i> หน้าแรก</a>
        <a href="javascript:void(0)" class="nav-item notif" onclick="toggleSidebarNotif()">
            <i class="fas fa-bell"></i> การแจ้งเตือน
            <span id="sidebar-notif-badge" style="background:red; color:white; font-size:0.7rem; padding:2px 6px; border-radius:10px; margin-left:auto; display:none;">0</span>
        </a>
        <div id="sidebar-notif-container" style="display:none; background:#f9f9f9; border-bottom:1px solid #ddd;">
            <div style="padding:10px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:0.8rem; font-weight:600;">ล่าสุด</span>
                <button onclick="markAllRead()" style="border:none; background:none; color:#3498db; cursor:pointer; font-size:0.75rem;">อ่านทั้งหมด</button>
            </div>
            <div id="sidebar-notif-list" style="max-height:300px; overflow-y:auto;">
                <!-- notifications load here -->
            </div>
        </div>
        <a href="saved.php" class="nav-item"><i class="fas fa-bookmark"></i> ที่บันทึก</a>
        <a href="report.php" class="nav-item"><i class="fas fa-user-shield"></i> รายงานปัญหา</a>
        <div class="logout-area">
            <a href="../logout.php?logout=true" class="logout-btn"><i class="fas fa-sign-out-alt"></i> ออกจากระบบ</a>
        </div>
    </div>

    <!-- overlay -->
    <div id="sideOverlay" class="overlay-side"></div>

    <!-- Topbar -->
    <i class="fas fa-bars menu-icon" onclick="toggleMenu()"></i>
    <div class="logo">Internfinder</div>
</header>


<script>
// Notification Logic
const sidebarNotifBadge = document.getElementById('sidebar-notif-badge');
const sidebarNotifContainer = document.getElementById('sidebar-notif-container');
const sidebarNotifList = document.getElementById('sidebar-notif-list');
let isNotifOpen = false;
let currentNotifications = [];

function toggleSidebarNotif() {
    isNotifOpen = !isNotifOpen;
    sidebarNotifContainer.style.display = isNotifOpen ? 'block' : 'none';
    if (isNotifOpen) {
        fetchNotifications();
    }
}

function openNotifModal(id) {
    window.location.href = 'notification_detail.php?id=' + id;
}

async function fetchNotifications() {
    try {
        const res = await fetch('../api/get_notifications.php');
        const data = await res.json();
        if (data.success) {
            currentNotifications = data.notifications;
            updateBadge(data.unread_count);
            renderList(data.notifications);
        }
    } catch (err) {
        console.error('Error fetching notifications:', err);
    }
}

function updateBadge(count) {
    if (count > 0) {
        sidebarNotifBadge.innerText = count;
        sidebarNotifBadge.style.display = 'inline-block';
    } else {
        sidebarNotifBadge.style.display = 'none';
    }
}

function renderList(items) {
    if (!items || items.length === 0) {
        sidebarNotifList.innerHTML = '<div style="padding:15px; text-align:center; color:#888; font-size:0.85rem;">ไม่มีการแจ้งเตือน</div>';
        return;
    }
    let html = '';
    items.forEach(item => {
        const bg = item.is_read == 0 ? '#eef2f7' : '#fff';
        const date = new Date(item.created_at).toLocaleDateString('th-TH') + ' ' + new Date(item.created_at).toLocaleTimeString('th-TH', {hour: '2-digit', minute:'2-digit'});
        html += `
            <div onclick="openNotifModal(${item.id})" style="padding:10px 15px; border-bottom:1px solid #eee; background:${bg}; cursor:pointer; transition:background 0.2s;">
                <div style="font-weight:600; font-size:0.8rem; color:#333; margin-bottom:2px;">${escapeHtml(item.title)}</div>
                <div style="font-size:0.75rem; color:#666; line-height:1.4; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">${escapeHtml(item.message)}</div>
                <div style="font-size:0.65rem; color:#999; margin-top:4px; text-align:right;">${date}</div>
            </div>
        `;
    });
    sidebarNotifList.innerHTML = html;
}

async function markRead(id) {
    try {
        const form = new FormData();
        form.append('notif_id', id);
        await fetch('../api/read_notifications.php', { method: 'POST', body: form });
        fetchNotifications(); // Reload to update UI
    } catch (err) { console.error(err); }
}

async function markAllRead() {
    try {
        const form = new FormData();
        form.append('read_all', 'true');
        await fetch('../api/read_notifications.php', { method: 'POST', body: form });
        fetchNotifications();
    } catch (err) { console.error(err); }
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// Initial fetch and poll
fetchNotifications();
setInterval(fetchNotifications, 60000); // Poll every 60 seconds
</script>


    <main>
        <div class="banner" id="banner" role="region" aria-roledescription="carousel" tabindex="0">
            <div class="viewport">
                <div class="track" aria-live="polite">
                    <div class="slide" role="group" aria-roledescription="slide" aria-label="Slide 1" data-bg="../img/482325862_4087742598149812_4768157798699933898_n.jpg">
                        <div class="caption">ตัวอย่างแบนเนอร์ 1</div>
                    </div>
                    <div class="slide" role="group" aria-roledescription="slide" aria-label="Slide 2" data-bg="../img/ads68-scaled.jpg">
                        <div class="caption">ตัวอย่างแบนเนอร์ 2</div>
                    </div>
                </div>
            </div>

            <div class="overlay"></div>

            <!-- Controls -->
            <button class="banner-btn prev" aria-label="Previous slide">&#10094;</button>
            <button class="banner-btn next" aria-label="Next slide">&#10095;</button>
            <button class="banner-btn playpause" aria-label="Pause slideshow" data-playing="true">⏸</button>
        </div> 

        <?php
            // Read search/filter GET params and set defaults for the UI
            $q_val = isset($_GET['q']) ? trim($_GET['q']) : '';
            $selected_dep = isset($_GET['dep']) ? (int)$_GET['dep'] : 0;
            $order_val = (isset($_GET['order']) && $_GET['order'] === 'old') ? 'old' : 'new';
        ?>

        <form method="get" class="filter-bar" style="align-items:center;">
            <select name="dep" class="btn-filter">
                <option value="">แผนกทั้งหมด</option>
                <?php
                $dep_sql = "SELECT * FROM tb_department";
                $dep_res = mysqli_query($conn, $dep_sql);
                while($dep = mysqli_fetch_assoc($dep_res)) {
                    $dep_id = (int) $dep['dep_id'];
                    $dep_name = htmlspecialchars($dep['dep_name'], ENT_QUOTES, 'UTF-8');
                    $sel = ($dep_id === $selected_dep) ? ' selected' : '';
                    echo "<option value='".$dep_id."'".$sel.">".$dep_name."</option>";
                }
                ?>
            </select>

            <input type="text" name="q" class="search-input" placeholder="ค้นหาชื่อบริษัท" value="<?php echo htmlspecialchars($q_val, ENT_QUOTES, 'UTF-8'); ?>">

            <select name="order" class="btn-filter" style="width:140px;">
                <option value="new" <?php echo $order_val === 'new' ? 'selected' : ''; ?>>เรียงใหม่สุด</option>
                <option value="old" <?php echo $order_val === 'old' ? 'selected' : ''; ?>>เรียงเก่าสุด</option>
            </select>

            <button type="submit" class="btn-filter">ค้นหา <i class="fas fa-search"></i></button>
        </form>

        <div class="company-list">
            <?php
            // Prepare search/filter values
            $q = isset($_GET['q']) ? trim($_GET['q']) : '';
            $dep = isset($_GET['dep']) ? (int)$_GET['dep'] : 0;
            $order = (isset($_GET['order']) && $_GET['order'] === 'old') ? 'ASC' : 'DESC';

            // Pagination setup: show 5 companies per page
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $perPage = 5;
            $offset = ($page - 1) * $perPage;

            $result = false;
            $total = 0;

            // Build prepared statements and include COUNT + LIMIT/OFFSET
            if ($q !== '' && $dep > 0) {
                $like = "%" . $q . "%";
                $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tb_company WHERE (com_name LIKE ? OR com_address LIKE ? OR com_tel LIKE ?) AND dep_id = ?");
                if ($countStmt) {
                    $countStmt->bind_param('sssi', $like, $like, $like, $dep);
                    $countStmt->execute();
                    $cntRes = $countStmt->get_result();
                    $total = (int)($cntRes->fetch_assoc()['cnt'] ?? 0);
                    $countStmt->close();
                }

                $stmt = $conn->prepare("SELECT * FROM tb_company WHERE (com_name LIKE ? OR com_address LIKE ? OR com_tel LIKE ?) AND dep_id = ? ORDER BY com_created $order LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
                if ($stmt) {
                    $stmt->bind_param('sssi', $like, $like, $like, $dep);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();
                }

            } elseif ($q !== '') {
                $like = "%" . $q . "%";
                $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tb_company WHERE (com_name LIKE ? OR com_address LIKE ? OR com_tel LIKE ?)");
                if ($countStmt) {
                    $countStmt->bind_param('sss', $like, $like, $like);
                    $countStmt->execute();
                    $cntRes = $countStmt->get_result();
                    $total = (int)($cntRes->fetch_assoc()['cnt'] ?? 0);
                    $countStmt->close();
                }

                $stmt = $conn->prepare("SELECT * FROM tb_company WHERE (com_name LIKE ? OR com_address LIKE ? OR com_tel LIKE ?) ORDER BY com_created $order LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
                if ($stmt) {
                    $stmt->bind_param('sss', $like, $like, $like);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();
                }

            } elseif ($dep > 0) {
                $countStmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tb_company WHERE dep_id = ?");
                if ($countStmt) {
                    $countStmt->bind_param('i', $dep);
                    $countStmt->execute();
                    $cntRes = $countStmt->get_result();
                    $total = (int)($cntRes->fetch_assoc()['cnt'] ?? 0);
                    $countStmt->close();
                }

                $stmt = $conn->prepare("SELECT * FROM tb_company WHERE dep_id = ? ORDER BY com_created $order LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
                if ($stmt) {
                    $stmt->bind_param('i', $dep);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $stmt->close();
                }

            }

            // Fallback to a simple query if no prepared result yet
            if ($result === false) {
                $countRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM tb_company");
                $total = (int)($countRes ? mysqli_fetch_assoc($countRes)['cnt'] : 0);

                $sql = "SELECT * FROM tb_company ORDER BY com_created $order LIMIT " . (int)$perPage . " OFFSET " . (int)$offset;
                $result = mysqli_query($conn, $sql);
            }

            if ($result && mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)) {
            ?>
                <div class="card">
                    <div class="card-body">
                        <div class="company-logo">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="company-info">
                            <h3><?php echo htmlspecialchars($row['com_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['com_tel'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="address"><?php echo htmlspecialchars(mb_strimwidth($row['com_address'], 0, 50, "..."), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <button class="save-btn" type="button" data-com-id="<?php echo (int)$row['com_id']; ?>" aria-label="บันทึกบริษัท">
                            <i class="<?php echo in_array((int)$row['com_id'], $savedIds) ? 'fas' : 'far'; ?> fa-bookmark"></i>
                        </button>
                    </div>
                    <div class="card-footer">
                        <span class="tag">รับทุกแผนก</span>
                        <a href="details.php?id=<?php echo (int)$row['com_id']; ?>" class="view-more">ดูรายละเอียด</a>
                    </div>
                </div>
            <?php 
                }
            } else {
                echo "ไม่พบข้อมูลบริษัท";
            }

            // Render pagination UI
            $totalPages = $perPage > 0 ? max(1, (int)ceil($total / $perPage)) : 1;
            ?>

            <?php if ($total > 0 && $totalPages > 1): ?>
                <div style="padding:10px 15px; display:flex; justify-content:center; flex-direction:column; align-items:center;">
                    <div class="page-summary">หน้า <?php echo $page; ?> จาก <?php echo $totalPages; ?> — รวม <?php echo $total; ?> รายการ</div>
                    <nav aria-label="การนำทางหน้า">
                      <ul class="pagination">
                        <?php $build = function($p){ $qs = $_GET; $qs['page'] = $p; return htmlspecialchars('?'.http_build_query($qs)); }; ?>
                        <?php $prev = max(1, $page-1); ?>
                        <li class="page-item"><a class="page-link" href="<?php echo $build($prev); ?>">ก่อนหน้า</a></li>

                        <?php
                            $maxLinks = 10;
                            $start = max(1, $page - intval(floor($maxLinks / 2)));
                            $end = min($totalPages, $start + $maxLinks - 1);
                            if ($end - $start + 1 < $maxLinks) {
                                $start = max(1, $end - $maxLinks + 1);
                            }

                            if ($start > 1) {
                                echo '<li class="page-item"><a class="page-link" href="'.$build(1).'">1</a></li>'; 
                                if ($start > 2) echo '<li class="page-item"><span class="page-link">…</span></li>'; 
                            }

                            for ($p = $start; $p <= $end; $p++):
                        ?>
                            <li class="page-item"><a class="page-link <?php if ($p == $page) echo 'active'; ?>" href="<?php echo $build($p); ?>"><?php echo $p; ?></a></li>
                        <?php endfor;

                            if ($end < $totalPages) {
                                if ($end < $totalPages-1) echo '<li class="page-item"><span class="page-link">…</span></li>'; 
                                echo '<li class="page-item"><a class="page-link" href="'.$build($totalPages).'">'.$totalPages.'</a></li>'; 
                            }
                        ?>

                        <?php $next = min($totalPages, $page+1); ?>
                        <li class="page-item"><a class="page-link" href="<?php echo $build($next); ?>">ถัดไป</a></li>
                      </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </main>

   


<script>
/* Advanced carousel: translate-based sliding, swipe, keyboard, autoplay, play/pause, lazy bg load */
(function(){
  const banner = document.getElementById('banner');
  if (!banner) return;
  const viewport = banner.querySelector('.viewport');
  const track = banner.querySelector('.track');
  const slides = Array.from(banner.querySelectorAll('.slide'));
  const prevBtn = banner.querySelector('.banner-btn.prev');
  const nextBtn = banner.querySelector('.banner-btn.next');
  const playBtn = banner.querySelector('.banner-btn.playpause');
  if (!slides.length) return;

  let index = 0;
  let playing = true;
  let timer = null;
  const delay = 4500;
  let startX = 0, deltaX = 0, isTouch = false;

  // lazy load backgrounds for visible slide and next slide
  function loadBg(i) {
    const s = slides[i];
    if (!s) return;
    if (!s.style.backgroundImage) {
      const bg = s.getAttribute('data-bg');
      if (bg) s.style.backgroundImage = `url('${bg}')`;
    }
  }

  function goTo(i, withAnim = true) {
    if (i < 0) i = slides.length - 1;
    if (i >= slides.length) i = 0;
    index = i;
    // lazy load nearby
    loadBg(index);
    loadBg((index+1)%slides.length);

    if (!withAnim) track.style.transition = 'none'; else track.style.transition = '';
    track.style.transform = `translateX(-${index * 100}%)`;

    // update play/pause aria
    banner.setAttribute('data-current', index+1);
  }

  // controls
  nextBtn && nextBtn.addEventListener('click', ()=> { goTo(index+1); restart(); });
  prevBtn && prevBtn.addEventListener('click', ()=> { goTo(index-1); restart(); });

  // play/pause
  function start() { if (timer) clearInterval(timer); timer = setInterval(()=> goTo(index+1), delay); playing = true; playBtn && playBtn.setAttribute('data-playing','true'); if (playBtn) playBtn.innerText = '⏸'; }
  function stop() { if (timer) { clearInterval(timer); timer = null; } playing = false; playBtn && playBtn.setAttribute('data-playing','false'); if (playBtn) playBtn.innerText = '▶'; }
  if (playBtn) playBtn.addEventListener('click', ()=> { if (playing) stop(); else start(); });

  function restart() { stop(); start(); }

  // pause on hover & focus
  banner.addEventListener('mouseenter', stop);
  banner.addEventListener('mouseleave', ()=> { if (playing) start(); });
  banner.addEventListener('focusin', stop);
  banner.addEventListener('focusout', ()=> { if (playing) start(); });

  // keyboard navigation
  banner.addEventListener('keydown', (e)=>{
    if (e.key === 'ArrowRight') { goTo(index+1); restart(); }
    if (e.key === 'ArrowLeft') { goTo(index-1); restart(); }
    if (e.key === ' ' || e.key === 'Spacebar') { e.preventDefault(); if (playing) stop(); else start(); }
  });

  // touch swipe support
  track.addEventListener('touchstart', (e)=>{
    isTouch = true; startX = e.touches[0].clientX; deltaX = 0; track.style.transition = 'none';
  }, {passive:true});
  track.addEventListener('touchmove', (e)=>{
    if (!isTouch) return; deltaX = e.touches[0].clientX - startX; track.style.transform = `translateX(${ -index*100 + (deltaX / viewport.clientWidth) * 100 }%)`;
  }, {passive:true});
  track.addEventListener('touchend', ()=>{
    if (!isTouch) return; isTouch = false; track.style.transition = ''; if (Math.abs(deltaX) > 40) {
      if (deltaX < 0) goTo(index+1); else goTo(index-1);
    } else {
      goTo(index);
    }
    restart();
  });

  // init
  loadBg(0); loadBg(1);
  goTo(0, false);
  start();
})();

// Sidebar Toggle with overlay, ESC key, and scroll-lock
(() => {
    const sidebar = document.getElementById('leftMenu');
    const overlay = document.getElementById('sideOverlay');
    const menuButtons = document.querySelectorAll('.menu-icon');

    function openSidebar() {
        if (!sidebar.classList.contains('open')) {
            sidebar.classList.add('open');
            overlay.classList.add('show');
            document.body.classList.add('scroll-lock');
            // focus first item for accessibility
            const first = sidebar.querySelector('.nav-item');
            if (first && typeof first.focus === 'function') first.focus();
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

    // Click on overlay closes
    if (overlay) overlay.addEventListener('click', closeSidebar);

    // Menu icons open (in case more than one)
    menuButtons.forEach(btn => btn.addEventListener('click', openSidebar));

    // ESC closes sidebar and modals
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeSidebar();
            // Also close any open modals if present
            const modals = document.querySelectorAll('.modal.show');
            modals.forEach(m => m.classList.remove('show'));
            document.body.classList.remove('scroll-lock');
        }
    });
})();

// Save (bookmark) company
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
                    icon.classList.remove('fas'); icon.classList.add('far');
                }
            } else {
                console.error('Save failed', data);
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