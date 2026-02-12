<?php
session_start();
include __DIR__ . '/../../config.php';

/* ===== ตลอดชุดคำสั่ง (Base URL) ===== */
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$base_url .= preg_replace('/(\/admin\/).*/', '/', $_SERVER['SCRIPT_NAME']);

/* ===== auth ===== */
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
} 

/* ===== Get filter values from URL ===== */
$status = $_GET['status'] ?? 'all';
$filter_level = $_GET['level'] ?? '';
$filter_dep = $_GET['dep'] ?? '';
$filter_room = $_GET['room'] ?? '';

/* ===== Build WHERE conditions ===== */
$conditions = [];
$params = [];
$types = "";

// Level filter
if (!empty($filter_level)) {
    $conditions[] = "s.std_level = ?";
    $params[] = $filter_level;
    $types .= "s";
}

// Department filter
if (!empty($filter_dep)) {
    $conditions[] = "s.dep_id = ?";
    $params[] = $filter_dep;
    $types .= "i";
}

// Room filter
if (!empty($filter_room)) {
    $conditions[] = "s.std_room = ?";
    $params[] = $filter_room;
    $types .= "s";
}

$where_clause = "";
if (!empty($conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $conditions);
}

/* ===== Fetch filter options ===== */
// Levels
$levels = ['ปวช. 1', 'ปวช. 2', 'ปวช. 3', 'ปวส. 1', 'ปวส. 2'];

// Departments
$departments = [];
$dep_result = $conn->query("SELECT dep_id, dep_name FROM tb_department ORDER BY dep_name ASC");
while ($dep = $dep_result->fetch_assoc()) {
    $departments[] = $dep;
}

// Rooms
$rooms = [];
$room_result = $conn->query("SELECT DISTINCT std_room FROM tb_student WHERE std_room IS NOT NULL AND std_room != '' ORDER BY std_room ASC");
while ($room = $room_result->fetch_assoc()) {
    $rooms[] = $room['std_room'];
}

/* ===== Main query ===== */
// Build the base query
$base_sql = "
SELECT 
    s.std_id,
    s.std_name,
    s.std_lastname,
    s.std_level,
    s.std_room,
    s.std_tel,
    s.std_gmail,
    d.dep_name,
    (SELECT c.com_name 
     FROM tb_internship i 
     JOIN tb_company c ON i.com_id = c.com_id 
     WHERE i.std_id = s.std_id AND i.status = 'approved' 
     LIMIT 1) as com_name
FROM tb_student s
LEFT JOIN tb_department d ON s.dep_id = d.dep_id
$where_clause
ORDER BY s.std_id DESC
";

// Wrap in subquery if we need to filter by internship status
if ($status === 'no') {
    $sql = "SELECT * FROM ($base_sql) AS students WHERE com_name IS NULL";
} elseif ($status === 'yes') {
    $sql = "SELECT * FROM ($base_sql) AS students WHERE com_name IS NOT NULL";
} else {
    $sql = $base_sql;
}

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

/* ===== Helper function to build filter URL ===== */
function buildFilterUrl($key, $value) {
    $params = $_GET;
    $params[$key] = $value;
    // Remove empty params
    $params = array_filter($params, fn($v) => $v !== '');
    return '?' . http_build_query($params);
}

function resetFilterUrl() {
    return '?status=all';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข้อมูลนักศึกษา | InternFinder</title>
    
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
                <h1>จัดการข้อมูลนักศึกษา</h1>
                <p>เพิ่ม แก้ไข หรือลบข้อมูลนักศึกษาในระบบ</p>
            </div>
            <div class="header-tools">
                <a href="student_add.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> เพิ่มนักศึกษา
                </a>
            </div>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="card" style="background: <?= $_GET['msg'] == 'deleted' ? '#DCFCE7' : '#FEE2E2' ?>; color: <?= $_GET['msg'] == 'deleted' ? '#166534' : '#991B1B' ?>; padding: 16px; border: 1px solid <?= $_GET['msg'] == 'deleted' ? '#BBF7D0' : '#FECACA' ?>; margin-bottom: 24px;">
                <i class="fas <?= $_GET['msg'] == 'deleted' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> me-2"></i> 
                <?php
                    if ($_GET['msg'] == 'has_internship') echo "ไม่สามารถลบได้ เนื่องจากมีประวัติฝึกงาน";
                    elseif ($_GET['msg'] == 'deleted') echo "ลบข้อมูลนักศึกษาเรียบร้อยแล้ว";
                    elseif ($_GET['msg'] == 'error') echo "เกิดข้อผิดพลาดในการดำเนินการ";
                    elseif ($_GET['msg'] == 'notfound') echo "ไม่พบข้อมูลนักศึกษา";
                ?>
            </div>
        <?php endif; ?>

        <!-- Filter Area -->
        <div class="card">
            <form method="GET" id="filterForm">
                <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
                <div style="display: flex; gap: 15px; align-items: flex-end; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 150px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">ระดับชั้น</label>
                        <select name="level" onchange="this.form.submit()" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($levels as $lvl): ?>
                                <option value="<?= $lvl ?>" <?= $filter_level === $lvl ? 'selected' : '' ?>><?= $lvl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 200px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">แผนกวิชา</label>
                        <select name="dep" onchange="this.form.submit()" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($departments as $dep): ?>
                                <option value="<?= $dep['dep_id'] ?>" <?= $filter_dep == $dep['dep_id'] ? 'selected' : '' ?>><?= htmlspecialchars($dep['dep_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 100px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: var(--text-muted); margin-bottom: 8px;">ห้อง</label>
                        <select name="room" onchange="this.form.submit()" style="width: 100%; padding: 10px; border: 1px solid var(--border); border-radius: 8px;">
                            <option value="">ทั้งหมด</option>
                            <?php foreach ($rooms as $rm): ?>
                                <option value="<?= htmlspecialchars($rm) ?>" <?= $filter_room === $rm ? 'selected' : '' ?>><?= htmlspecialchars($rm) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <a href="<?= resetFilterUrl() ?>" class="btn btn-secondary">ล้างตัวกรอง</a>
                </div>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border); display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                    <span style="font-size:13px; font-weight:700; color: var(--text-muted);">สถานะฝึกงาน:</span>
                    <a href="<?= buildFilterUrl('status', 'all') ?>" class="btn btn-sm <?= $status == 'all' ? 'btn-primary' : 'btn-secondary' ?>">ทั้งหมด</a>
                    <a href="<?= buildFilterUrl('status', 'no') ?>" class="btn btn-sm <?= $status == 'no' ? 'btn-primary' : 'btn-secondary' ?>">ยังไม่มีที่ฝึกงาน</a>
                    <a href="<?= buildFilterUrl('status', 'yes') ?>" class="btn btn-sm <?= $status == 'yes' ? 'btn-primary' : 'btn-secondary' ?>">มีที่ฝึกงานแล้ว</a>
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
                            <th>นักศึกษา</th>
                            <th>ระดับชั้น / แผนก</th>
                            <th>การติดต่อ</th>
                            <th>สถานะการฝึกงาน</th>
                            <th style="text-align: center;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 60px; color: var(--text-muted);">
                                    <i class="fas fa-user-slash" style="font-size: 40px; margin-bottom: 16px; opacity: 0.2;"></i>
                                    <p>ไม่พบข้อมูลนักศึกษา</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td data-label="นักศึกษา">
                                        <div style="font-weight: 700;"><?= htmlspecialchars($row['std_name'] . ' ' . $row['std_lastname']) ?></div>
                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">ID: <?= $row['std_id'] ?></div>
                                    </td>
                                    <td data-label="ระดับชั้น/แผนก">
                                        <div style="font-size: 13px; font-weight: 600;"><?= htmlspecialchars($row['std_level']) ?> ห้อง <?= htmlspecialchars($row['std_room']) ?></div>
                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;"><?= htmlspecialchars($row['dep_name']) ?></div>
                                    </td>
                                    <td data-label="การติดต่อ">
                                        <div style="font-size: 13px;"><i class="fas fa-phone-alt me-2" style="opacity: 0.5;"></i> <?= htmlspecialchars($row['std_tel'] ?: '-') ?></div>
                                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;"><i class="fas fa-envelope me-2" style="opacity: 0.5;"></i> <?= htmlspecialchars($row['std_gmail'] ?: '-') ?></div>
                                    </td>
                                    <td data-label="สถานะ">
                                        <?php if($row['com_name']): ?>
                                            <span class="btn btn-sm btn-success" style="cursor: default; padding: 4px 10px;">มีที่ฝึกงานแล้ว</span>
                                            <div style="font-size: 11px; color: var(--primary); font-weight: 600; margin-top: 6px;">
                                                <i class="fas fa-building me-1"></i> <?= htmlspecialchars($row['com_name']) ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="btn btn-sm" style="background: #FEE2E2; color: #EF4444; cursor: default; padding: 4px 10px;">ยังไม่มีที่ฝึกงาน</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align: center;" data-label="จัดการ">
                                        <div style="display: flex; gap: 8px; justify-content: center;">
                                            <a href="student_edit.php?id=<?= $row['std_id'] ?>" class="btn btn-sm btn-warning" title="แก้ไข">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="student_delete.php?id=<?= $row['std_id'] ?>" class="btn btn-sm btn-danger" title="ลบ" onclick="return confirm('ยืนยันการลบข้อมูลนักศึกษา?')">
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
