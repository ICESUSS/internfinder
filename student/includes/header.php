<header class="student-header">
    <div class="header-container">
        <a href="index.php" class="logo">
            <i class="fas fa-graduation-cap"></i>
            <span>InternFinder</span>
        </a>
        
        <nav class="nav-links">
            <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> <span>หน้าแรก</span>
            </a>
            <a href="saved.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'saved.php' ? 'active' : ''; ?>">
                <i class="fas fa-bookmark"></i> <span>ที่บันทึกไว้</span>
            </a>
            <a href="report.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active' : ''; ?>">
                <i class="fas fa-file-alt"></i> <span>รายงานผล</span>
            </a>
            <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <i class="fas fa-user"></i> <span>โปรไฟล์</span>
            </a>
            <div class="notification-wrapper">
                <a href="javascript:void(0)" class="notification-icon" id="notif-icon">
                    <i class="fas fa-bell"></i>
                    <span class="badge" id="notif-badge" style="display:none;">0</span>
                </a>
                <div class="notification-dropdown" id="notif-dropdown">
                    <div class="notif-header">การแจ้งเตือน</div>
                    <div class="notif-list" id="notif-list">
                        <div class="notif-item empty">ไม่มีการแจ้งเตือนใหม่</div>
                    </div>
                    <div class="notif-footer">
                        <a href="notifications.php">ดูทั้งหมด</a>
                    </div>
                </div>
            </div>
            <a href="../logout.php" class="logout-link">
                <i class="fas fa-sign-out-alt"></i> <span>ออกจากระบบ</span>
            </a>
        </nav>

        <div class="mobile-toggle" onclick="toggleMenu()">
            <i class="fas fa-bars"></i>
        </div>
    </div>
</header>

<style>
:root {
    --primary-color: #3366FF;
    --header-height: 70px;
}

.student-header {
    background-color: var(--primary-color);
    height: var(--header-height);
    position: sticky;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    color: white;
}

.header-container {
    max-width: 1200px;
    width: 100%;
    margin: 0 auto;
    padding: 0 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.logo {
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none;
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
}

.nav-links {
    display: flex;
    gap: 20px;
}

.nav-links a {
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1rem;
    padding: 8px 12px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.nav-links a:hover, .nav-links a.active {
    color: white;
    background: rgba(255, 255, 255, 0.15);
}

.logout-link:hover {
    background: rgba(220, 38, 38, 0.2) !important;
}

.mobile-toggle {
    display: none;
    font-size: 1.5rem;
    cursor: pointer;
}

/* Responsive Styles */
@media (max-width: 768px) {
    .nav-links {
        display: none;
        position: absolute;
        top: var(--header-height);
        left: 0;
        right: 0;
        background-color: var(--primary-color);
        flex-direction: column;
        padding: 20px;
        gap: 10px;
        border-top: 1px solid rgba(255,255,255,0.1);
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }

    .nav-links.show {
        display: flex;
    }

    .mobile-toggle {
        display: block;
    }

    .nav-links a span {
        display: inline;
    }

    .notification-wrapper {
        width: 100%;
    }
    .notification-dropdown {
        position: static;
        width: 100%;
        box-shadow: none;
        display: none;
    }
    .notification-dropdown.show {
        display: block;
    }
}

/* Notification Styles */
.notification-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.notification-icon {
    position: relative;
    color: white;
    font-size: 1.2rem;
    padding: 8px;
    border-radius: 50%;
    transition: background 0.3s;
}

.notification-icon:hover {
    background: rgba(255,255,255,0.1);
}

.notification-icon .badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #FF3B30;
    color: white;
    font-size: 0.7rem;
    padding: 2px 6px;
    border-radius: 10px;
    font-weight: bold;
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: 0;
    width: 300px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    display: none;
    flex-direction: column;
    z-index: 1001;
    overflow: hidden;
    color: #333;
    margin-top: 10px;
}

.notification-dropdown.show {
    display: flex;
}

.notif-header {
    padding: 12px 15px;
    background: #f8f9fa;
    font-weight: bold;
    border-bottom: 1px solid #eee;
}

.notif-list {
    max-height: 400px;
    overflow-y: auto;
}

.notif-item {
    padding: 12px 15px;
    border-bottom: 1px solid #f0f0f0;
    text-decoration: none;
    color: #333;
    display: block;
    transition: background 0.2s;
}

.notif-item:hover {
    background: #f0f7ff;
}

.notif-item.unread {
    background: #f5f9ff;
    border-left: 3px solid var(--primary-color);
}

.notif-item.empty {
    text-align: center;
    color: #888;
    padding: 20px;
}

.notif-item .title {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 3px;
    display: block;
}

.notif-item .time {
    font-size: 0.8rem;
    color: #888;
}

.notif-footer {
    padding: 10px;
    text-align: center;
    border-top: 1px solid #eee;
}

.notif-footer a {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
}

/* Adjust main content spacing */
main {
    padding-top: 20px;
}
</style>

<script>
function toggleMenu() {
    const navLinks = document.querySelector('.nav-links');
    navLinks.classList.toggle('show');
}

// Notification Logic
document.addEventListener('DOMContentLoaded', function() {
    const notifIcon = document.getElementById('notif-icon');
    const notifDropdown = document.getElementById('notif-dropdown');
    const notifBadge = document.getElementById('notif-badge');
    const notifList = document.getElementById('notif-list');

    notifIcon.addEventListener('click', function(e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('show');
        if (notifDropdown.classList.contains('show')) {
            fetchNotifications();
        }
    });

    document.addEventListener('click', function() {
        notifDropdown.classList.remove('show');
    });

    notifDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    function fetchNotifications() {
        fetch('../api/get_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update Badge
                    if (data.unread_count > 0) {
                        notifBadge.textContent = data.unread_count;
                        notifBadge.style.display = 'block';
                    } else {
                        notifBadge.style.display = 'none';
                    }

                    // Populate List
                    if (data.notifications.length > 0) {
                        notifList.innerHTML = '';
                            data.notifications.slice(0, 5).forEach(notif => {
                                const item = document.createElement('a');
                                item.href = 'notification_detail.php?id=' + notif.id;
                                item.className = 'notif-item' + (notif.is_read == 0 ? ' unread' : '');
                                
                                const date = new Date(notif.created_at);
                                const timeStr = date.toLocaleDateString('th-TH') + ' ' + date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0');
                                
                                // Fallback title if empty
                                const displayTitle = notif.title || 'แจ้งเตือนใหม่';
                                // Message snippet
                                const msgSnippet = notif.message ? notif.message.substring(0, 45) + (notif.message.length > 45 ? '...' : '') : '';

                                item.innerHTML = `
                                    <span class="title">${displayTitle}</span>
                                    ${msgSnippet ? `<span class="snippet" style="font-size: 0.85rem; color: #666; display: block; margin-bottom: 3px;">${msgSnippet}</span>` : ''}
                                    <span class="time">${timeStr} น.</span>
                                `;
                                notifList.appendChild(item);
                            });
                    } else {
                        notifList.innerHTML = '<div class="notif-item empty">ไม่มีการแจ้งเตือน</div>';
                    }
                }
            });
    }

    // Initial check for unread count
    fetchNotifications();
    // Refresh every 30 seconds
    setInterval(fetchNotifications, 30000);
});
</script>
