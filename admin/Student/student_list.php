<?php
session_start();
include __DIR__ . '/../../config.php';

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

/* ===== Internship status is now handled via subquery wrapping below ===== */

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
    <title>จัดการนักศึกษา | Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338CA;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --bg-main: #F8FAFC;
            --bg-card: #FFFFFF;
            --text-main: #1E293B;
            --text-muted: #64748B;
            --border: #E2E8F0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', 'Sarabun', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            line-height: 1.5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* ===== Header ===== */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: var(--bg-card);
            padding: 20px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        header h1 {
            font-size: 20px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 14px;
            border: none;
            cursor: pointer;
        }

        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); }

        .btn-back { background: #F1F5F9; color: var(--text-muted); }
        .btn-back:hover { background: #E2E8F0; color: var(--text-main); }
        
        .btn-sm { padding: 6px 12px; border-radius: 8px; font-size: 13px; }
        .btn-edit { background: #FEF3C7; color: #92400E; }
        .btn-del { background: #FEE2E2; color: var(--danger); }

        /* ===== Filter Bar ===== */
        .filter-bar {
            background: var(--bg-card);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 150px;
        }

        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .filter-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            background: white;
            cursor: pointer;
            transition: border-color 0.2s;
        }

        .filter-group select:focus {
            outline: none;
            border-color: var(--primary);
        }

        .filter-btn {
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-muted);
            background: #F1F5F9;
            transition: all 0.2s;
        }

        .filter-btn:hover { background: #E2E8F0; }
        .filter-btn.active { background: var(--primary); color: white; }

        .filter-status-row {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border);
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .btn-reset {
            background: #FEE2E2;
            color: var(--danger);
            padding: 10px 16px;
        }

        .btn-reset:hover {
            background: #FCA5A5;
        }

        /* ===== Status Badges ===== */
        .badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-yes { background: #DCFCE7; color: #166534; }
        .badge-no { background: #FEE2E2; color: #991B1B; }

        /* ===== Table Content ===== */
        .content-card {
            background: var(--bg-card);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #F8FAFC;
            padding: 16px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            border-bottom: 1px solid var(--border);
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        .alert-float {
            background: #fff;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            border-left: 4px solid var(--danger);
            font-size: 14px;
        }

        .result-count {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 15px;
        }

        .result-count strong {
            color: var(--text-main);
        }

        /* Responsive */
        @media (max-width: 768px) {
            header {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }

            .filter-row {
                flex-direction: column;
            }

            .filter-group {
                min-width: 100%;
            }

            .table-responsive-stack thead { display: none; }
            .table-responsive-stack tr { 
                display: block; 
                padding: 15px;
                border-bottom: 8px solid var(--bg-main);
            }
            .table-responsive-stack td { 
                display: flex; 
                justify-content: space-between;
                align-items: center;
                padding: 10px 0;
                border-bottom: 1px solid #f1f1f1;
            }
            .table-responsive-stack td:before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--text-muted);
                padding-right: 10px;
            }
            .table-responsive-stack td:last-child { border-bottom: none; }
        }
    </style>
    <link rel="stylesheet" href="../../assets/css/mobile-responsive.css">
</head>
<body>

<div class="container">
    <?php if(isset($_GET['msg'])): ?>
        <div class="alert-float" style="border-left-color: <?= $_GET['msg'] == 'deleted' ? 'var(--success)' : 'var(--danger)' ?>">
            <?php
                if ($_GET['msg'] == 'has_internship') echo "❌ ไม่สามารถลบได้ เนื่องจากมีประวัติฝึกงาน";
                elseif ($_GET['msg'] == 'deleted') echo "✅ ลบข้อมูลนักศึกษาเรียบร้อยแล้ว";
                elseif ($_GET['msg'] == 'error') echo "❌ เกิดข้อผิดพลาดในการดำเนินการ";
                elseif ($_GET['msg'] == 'notfound') echo "❌ ไม่พบข้อมูลนักศึกษา";
            ?>
        </div>
    <?php endif; ?>

    <header>
        <h1><i class="fas fa-user-graduate text-primary"></i> จัดการข้อมูลนักศึกษา</h1>
        <div style="display:flex; gap:10px;">
            <a href="../index.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> กลับ</a>
            <a href="../Student/student_add.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> เพิ่มนักศึกษา</a>
        </div>
    </header>

    <div class="filter-bar">
        <form method="GET" id="filterForm">
            <!-- Hidden field to preserve status when changing other filters -->
            <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
            
            <div class="filter-row">
                <div class="filter-group">
                    <label><i class="fas fa-layer-group"></i> ระดับชั้น</label>
                    <select name="level" onchange="this.form.submit()">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($levels as $lvl): ?>
                            <option value="<?= $lvl ?>" <?= $filter_level === $lvl ? 'selected' : '' ?>><?= $lvl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-building-columns"></i> แผนกวิชา</label>
                    <select name="dep" onchange="this.form.submit()">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($departments as $dep): ?>
                            <option value="<?= $dep['dep_id'] ?>" <?= $filter_dep == $dep['dep_id'] ? 'selected' : '' ?>><?= htmlspecialchars($dep['dep_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label><i class="fas fa-door-open"></i> ห้อง</label>
                    <select name="room" onchange="this.form.submit()">
                        <option value="">-- ทั้งหมด --</option>
                        <?php foreach ($rooms as $rm): ?>
                            <option value="<?= htmlspecialchars($rm) ?>" <?= $filter_room === $rm ? 'selected' : '' ?>><?= htmlspecialchars($rm) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <a href="<?= resetFilterUrl() ?>" class="btn btn-reset"><i class="fas fa-times"></i> ล้างตัวกรอง</a>
            </div>

            <div class="filter-status-row">
                <span style="font-size:14px; font-weight:600; margin-right:10px;"><i class="fas fa-briefcase"></i> สถานะฝึกงาน:</span>
                <a href="<?= buildFilterUrl('status', 'all') ?>" class="filter-btn <?= $status == 'all' ? 'active' : '' ?>">ทั้งหมด</a>
                <a href="<?= buildFilterUrl('status', 'no') ?>" class="filter-btn <?= $status == 'no' ? 'active' : '' ?>">ยังไม่มีที่ฝึกงาน</a>
                <a href="<?= buildFilterUrl('status', 'yes') ?>" class="filter-btn <?= $status == 'yes' ? 'active' : '' ?>">มีที่ฝึกงานแล้ว</a>
            </div>
        </form>
    </div>

    <div class="result-count">
        พบ <strong><?= $result->num_rows ?></strong> รายการ
        <?php if (!empty($filter_level) || !empty($filter_dep) || !empty($filter_room) || $status !== 'all'): ?>
            (กรองแล้ว)
        <?php endif; ?>
    </div>

    <div class="content-card">
        <table class="table-responsive-stack">
            <thead>
                <tr>
                    <th>ข้อมูลพื้นฐาน</th>
                    <th>ระดับชั้น / แผนก</th>
                    <th>ติดต่อ</th>
                    <th>การฝึกงาน</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows === 0): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px;">
                        <i class="fas fa-user-slash" style="font-size: 48px; color: var(--text-muted); opacity: 0.3;"></i>
                        <p style="margin-top: 15px; color: var(--text-muted);">ไม่พบนักศึกษาตามเงื่อนไขที่กรอง</p>
                        <a href="<?= resetFilterUrl() ?>" class="btn btn-primary" style="margin-top: 10px;">ล้างตัวกรอง</a>
                    </td>
                </tr>
            <?php else: ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td data-label="นักศึกษา">
                    <div style="font-weight:600"><?= htmlspecialchars($row['std_name'] . ' ' . $row['std_lastname']) ?></div>
                    <div style="font-size:12px; color:var(--text-muted)"><?= $row['std_id'] ?></div>
                </td>
                <td data-label="ระดับชั้น / แผนก">
                    <div style="font-size:13px"><?= htmlspecialchars($row['std_level'] ?? '-') ?> ห้อง <?= htmlspecialchars($row['std_room'] ?? '-') ?></div>
                    <div style="font-size:12px; color:var(--text-muted)"><?= htmlspecialchars($row['dep_name'] ?? '-') ?></div>
                </td>
                <td data-label="การติดต่อ">
                    <div style="font-size:13px"><i class="fas fa-phone-alt" style="width:16px; opacity:0.5"></i> <?= htmlspecialchars($row['std_tel'] ?? '-') ?></div>
                    <div style="font-size:12px; color:var(--text-muted)"><i class="fas fa-envelope" style="width:16px; opacity:0.5"></i> <?= htmlspecialchars($row['std_gmail'] ?? '-') ?></div>
                </td>
                <td data-label="สถานะการฝึกงาน">
                    <?php if($row['com_name']): ?>
                        <div class="badge badge-yes">มีที่ฝึกงานแล้ว</div>
                        <div style="font-size:12px; color:var(--text-muted); margin-top:4px; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                            <i class="fas fa-building"></i> <?= htmlspecialchars($row['com_name']) ?>
                        </div>
                    <?php else: ?>
                        <div class="badge badge-no">ยังไม่มีที่ฝึกงาน</div>
                    <?php endif; ?>
                </td>
                <td data-label="จัดการ">
                    <div style="display:flex; gap:5px;">
                        <a class="btn btn-sm btn-edit" title="แก้ไข" href="../Student/student_edit.php?id=<?= $row['std_id'] ?>"><i class="fas fa-edit"></i></a>
                        <a class="btn btn-sm btn-del" title="ลบ" href="student_delete.php?id=<?= $row['std_id'] ?>" onclick="return confirm('ยืนยันการลบข้อมูลนักศึกษาคนนี้?')"><i class="fas fa-trash"></i></a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
