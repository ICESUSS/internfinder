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