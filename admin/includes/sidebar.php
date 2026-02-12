<?php
// Get current page to set active class
$current_page = basename($_SERVER['PHP_SELF']);
// Special case for subdirectories like Student/student_list.php
$request_uri = $_SERVER['REQUEST_URI'];

// Fallback for base_url if not defined in the parent page
if (!isset($base_url)) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
    $host = $_SERVER['HTTP_HOST'];
    $script = $_SERVER['SCRIPT_NAME'];
    // Find the part before '/admin/' and ensure it ends with a slash
    $project_path = preg_replace('/(\/admin\/).*/', '/', $script);
    $base_url = $protocol . "://" . $host . $project_path;
}
?>
<aside class="sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <i class="fas fa-graduation-cap"></i>
        <span>InternFinder</span>
        <button class="mobile-close-btn" onclick="toggleSidebar()"><i class="fas fa-times"></i></button>
    </div>
    <nav class="sidebar-menu">
        <a href="<?= $base_url ?>admin/index.php" class="menu-item <?= $current_page == 'index.php' ? 'active' : '' ?>">
            <i class="fas fa-home"></i> แผงควบคุม
        </a>
        <a href="<?= $base_url ?>admin/internship_requests.php" class="menu-item <?= $current_page == 'internship_requests.php' ? 'active' : '' ?>">
            <i class="fas fa-file-signature"></i> คำร้องฝึกงาน
        </a>
        <a href="<?= $base_url ?>admin/Student/student_list.php" class="menu-item <?= strpos($request_uri, 'Student/') !== false ? 'active' : '' ?>">
            <i class="fas fa-user-graduate"></i> นักศึกษา
        </a>
        <a href="<?= $base_url ?>admin/Com/company_list.php" class="menu-item <?= strpos($request_uri, 'Com/') !== false ? 'active' : '' ?>">
            <i class="fas fa-building"></i> สถานประกอบการ
        </a>
        <a href="<?= $base_url ?>admin/report.php" class="menu-item <?= $current_page == 'report.php' ? 'active' : '' ?>">
            <i class="fas fa-bug"></i> รายงานปัญหา
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= $base_url ?>logout.php" class="btn-logout-sidebar" onclick="return confirm('ออกจากระบบ?')">
            <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
        </a>
    </div>
</aside>

<script>
    function toggleSidebar() {
        document.getElementById('adminSidebar').classList.toggle('show');
    }
</script>
