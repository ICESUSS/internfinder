// Sidebar Toggle - Click outside to close
(() => {
    const sidebar = document.getElementById('leftMenu');
    const menuIcon = document.querySelector('.menu-icon');
    const navItems = document.querySelectorAll('.nav-item');

    function openSidebar() {
        if (!sidebar.classList.contains('show')) {
            sidebar.classList.add('show');
        }
    }

    function closeSidebar() {
        if (sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
    }

    window.toggleMenu = function() {
        if (sidebar.classList.contains('show')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    }

    // Click on nav items closes sidebar on mobile
    navItems.forEach(item => {
        item.addEventListener('click', function() {
            if (window.innerWidth < 768) {
                closeSidebar();
            }
        });
    });

    // ESC key closes sidebar
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });

    // Close when clicking outside on mobile
    document.addEventListener('click', function(e) {
        if (sidebar.classList.contains('show') && 
            !sidebar.contains(e.target) && 
            !menuIcon.contains(e.target) &&
            window.innerWidth < 768) {
            closeSidebar();
        }
    });

    // Toggle notification section in sidebar
    window.toggleSidebarNotif = function() {
        const notifSection = document.querySelector('.sidebar-notification-section');
        if (notifSection) {
            notifSection.style.display = notifSection.style.display === 'none' ? 'block' : 'none';
            openSidebar(); // Open sidebar when clicking notification icon
        }
    };
})();
