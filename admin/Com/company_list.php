<?php
session_start();
include __DIR__ . '/../../config.php';

/* ===== ตลอดชุดคำสั่ง (Base URL) ===== */
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$base_url .= preg_replace('/(\/admin\/).*/', '/', $_SERVER['SCRIPT_NAME']);

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
} 

// ข้อความแจ้งเตือน
$msg = $_GET['msg'] ?? '';

// Get filter values
$search = $_GET['search'] ?? '';

// Build query with filters
$conditions = [];
$params = [];
$types = "";

if (!empty($search)) {
    $conditions[] = "(com_name LIKE ? OR com_add_no LIKE ? OR com_road LIKE ? OR com_subdistrict LIKE ? OR com_district LIKE ? OR com_province LIKE ? OR com_zipcode LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param; $params[] = $search_param; $params[] = $search_param;
    $params[] = $search_param; $params[] = $search_param; $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sssssss";
}

$where_clause = "";
if (!empty($conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $conditions);
}

$sql = "SELECT * FROM tb_company $where_clause ORDER BY com_created DESC";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสถานประกอบการ | InternFinder</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    
    <!-- Shared Admin Styles -->
    <link rel="stylesheet" href="<?= $base_url ?>admin/assets/css/admin-style.css">
</head>
<body>

    <!-- MOBILE HEADER -->
    <div class="mobile-header">
        <div style="display: flex; align-items: center; gap: 10px; font-weight: 700;">
            <i class="fas fa-graduation-cap text-primary"></i>
            <span>InternFinder</span>
        </div>
        <button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
    </div>

    <!-- SIDEBAR -->
    <?php include '../includes/sidebar.php'; ?>

    <!-- MAIN -->
    <main class="main-content">
        <div class="top-bar">
            <div class="welcome-text">
                <h1>จัดการสถานประกอบการ</h1>
                <p>ข้อมูลบริษัทและสถานประกอบการที่เข้าร่วมโครงการ</p>
            </div>
            <div class="header-tools">
                <a href="company_edit.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> เพิ่มบริษัท
                </a>
            </div>
        </div>

        <?php if($msg): ?>
            <div class="card" style="background: <?= in_array($msg, ['deleted']) ? '#DCFCE7' : '#FEE2E2' ?>; color: <?= in_array($msg, ['deleted']) ? '#166534' : '#991B1B' ?>; padding: 16px; border: 1px solid <?= in_array($msg, ['deleted']) ? '#BBF7D0' : '#FECACA' ?>; margin-bottom: 24px;">
                <i class="fas <?= in_array($msg, ['deleted']) ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i> 
                <?php
                    if ($msg == 'has_internship') echo "ไม่สามารถลบได้ เนื่องจากมีประวัติฝึกงานที่อ้างอิงบริษัทนี้";
                    elseif ($msg == 'deleted') echo "ลบข้อมูลเรียบร้อยแล้ว";
                    elseif ($msg == 'error') echo "เกิดข้อผิดพลาดในการดำเนินการ";
                    elseif ($msg == 'notfound') echo "ไม่พบบริษัทที่ต้องการจัดการ";
                    else echo htmlspecialchars($msg);
                ?>
            </div>
        <?php endif; ?>

        <!-- Search Area -->
        <div class="card">
            <form method="GET">
                <div style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 300px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">ค้นหา</label>
                        <input type="text" name="search" placeholder="ชื่อบริษัท หรือ ที่อยู่..." value="<?= htmlspecialchars($search) ?>" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 24px;">
                        <i class="fas fa-search"></i> ค้นหา
                    </button>
                    <a href="company_list.php" class="btn btn-secondary">ล้าง</a>
                </div>
            </form>
        </div>

        <div style="margin-bottom: 15px; font-size: 14px; color: var(--text-muted);">
            พบข้อมูลทั้งหมด <strong><?= number_format($result->num_rows) ?></strong> รายการ
        </div>

        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 80px; text-align: center;">รูป</th>
                            <th>สถานประกอบการ</th>
                            <th>ข้อมูลติดต่อ</th>
                            <th>วันที่สร้าง</th>
                            <th style="text-align: center;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 60px; color: var(--text-muted);">
                                    <i class="fas fa-building" style="font-size: 40px; margin-bottom: 16px; opacity: 0.2;"></i>
                                    <p>ไม่พบข้อมูลสถานประกอบการ</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td style="text-align: center;" data-label="รูป">
                                        <?php 
                                        $img_p = '../../uploads/companies/' . $row['com_img'];
                                        if (!empty($row['com_img']) && file_exists(__DIR__ . '/../../' . $img_p)): ?>
                                            <img src="<?= $img_p ?>" alt="Logo" style="width: 48px; height: 48px; object-fit: cover; border-radius: 10px; border: 1px solid var(--border);">
                                        <?php else: ?>
                                            <div style="width: 48px; height: 48px; display:flex; align-items:center; justify-content:center; background:#f1f1f1; color:#ccc; border-radius: 10px; margin: 0 auto;">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td data-label="สถานประกอบการ">
                                        <div style="font-weight: 700;"><?= htmlspecialchars($row['com_name']) ?></div>
                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">ID: <?= $row['com_id'] ?></div>
                                    </td>
                                    <td data-label="ข้อมูลติดต่อ">
                                        <div style="font-size: 13px;"><i class="fas fa-phone-alt me-2" style="opacity: 0.5;"></i> <?= htmlspecialchars($row['com_tel'] ?: '-') ?></div>
                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;"><i class="fas fa-envelope me-2" style="opacity: 0.5;"></i> <?= htmlspecialchars($row['com_email'] ?: '-') ?></div>
                                        <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px; max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                            <i class="fas fa-map-marker-alt me-2" style="opacity: 0.5;"></i>
                                            <?= htmlspecialchars(($row['com_add_no'] ? $row['com_add_no'] . ' ' : '') . ($row['com_road'] ? 'ถ.' . $row['com_road'] . ' ' : '') . ($row['com_subdistrict'] ? 'ต.' . $row['com_subdistrict'] . ' ' : '') . ($row['com_district'] ? 'อ.' . $row['com_district'] . ' ' : '') . ($row['com_province'] ? 'จ.' . $row['com_province'] . ' ' : '') . ($row['com_zipcode'] ?? '')) ?: '-' ?>
                                        </div>
                                    </td>
                                    <td data-label="วันที่สร้าง">
                                        <div style="font-size: 13px;"><?= date('d/m/Y', strtotime($row['com_created'])) ?></div>
                                    </td>
                                    <td style="text-align: center;" data-label="จัดการ">
                                        <div style="display: flex; gap: 8px; justify-content: center;">
                                            <a href="company_detail.php?com=<?= $row['com_id'] ?>" class="btn btn-sm" style="background: #EEF2FF; color: var(--primary);" title="รายละเอียด">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="company_edit.php?id=<?= $row['com_id'] ?>" class="btn btn-sm" style="background: #FEF3C7; color: #D97706;" title="แก้ไข">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="company_delete.php?id=<?= $row['com_id'] ?>" class="btn btn-sm btn-danger" title="ลบ" onclick="return confirm('ยืนยันการลบสถานประกอบการ?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            document.getElementById('adminSidebar').classList.toggle('show');
        }
    </script>
</body>
</html>
