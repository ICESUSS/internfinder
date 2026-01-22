<?php
session_start();
include __DIR__ . '/../../config.php';

// ตรวจสอบสิทธิ์ admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
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

    $conditions[] = "(com_name LIKE ? OR com_add_no LIKE ? OR com_road LIKE ? OR com_subdistrict LIKE ? OR com_district LIKE ? OR com_province LIKE ? OR com_zipcode LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param; $params[] = $search_param; $params[] = $search_param;
    $params[] = $search_param; $params[] = $search_param; $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sssssss";

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
    <title>จัดการสถานประกอบการ | Admin</title>
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
        .btn-view { background: #EEF2FF; color: var(--primary); }
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
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .filter-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.2s;
        }

        .filter-group input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .btn-search {
            background: var(--primary);
            color: white;
            padding: 10px 20px;
        }

        .btn-reset {
            background: #FEE2E2;
            color: var(--danger);
            padding: 10px 16px;
        }

        .btn-reset:hover {
            background: #FCA5A5;
        }

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

        .img-thumb { width: 48px; height: 48px; object-fit: cover; border-radius: 10px; border: 1px solid var(--border); }

        .alert-float {
            background: #fff;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            border-left: 4px solid var(--primary);
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
    <?php if($msg): ?>
        <div class="alert-float" style="border-left-color: <?= in_array($msg, ['deleted']) ? 'var(--success)' : 'var(--danger)' ?>">
            <?php
                if ($msg == 'has_internship') echo "❌ ไม่สามารถลบได้ เนื่องจากมีประวัติฝึกงานที่อ้างอิงบริษัทนี้";
                elseif ($msg == 'deleted') echo "✅ ลบข้อมูลเรียบร้อยแล้ว";
                elseif ($msg == 'error') echo "❌ เกิดข้อผิดพลาดในการดำเนินการ";
                elseif ($msg == 'notfound') echo "❌ ไม่พบบริษัทที่ต้องการจัดการ";
                else echo htmlspecialchars($msg);
            ?>
        </div>
    <?php endif; ?>

    <header>
        <h1><i class="fas fa-building text-primary"></i> จัดการสถานประกอบการ</h1>
        <div style="display:flex; gap:10px;">
            <a href="../index.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> กลับ</a>
            <a href="company_edit.php" class="btn btn-primary"><i class="fas fa-plus"></i> เพิ่มบริษัท</a>
        </div>
    </header>

    <div class="filter-bar">
        <form method="GET">
            <div class="filter-row">
                <div class="filter-group" style="flex: 2;">
                    <label><i class="fas fa-search"></i> ค้นหา</label>
                    <input type="text" name="search" placeholder="ค้นหาชื่อบริษัท หรือ ที่อยู่..." value="<?= htmlspecialchars($search) ?>">
                </div>

                <button type="submit" class="btn btn-search"><i class="fas fa-search"></i> ค้นหา</button>
                
                <?php if (!empty($search)): ?>
                    <a href="company_list.php" class="btn btn-reset"><i class="fas fa-times"></i> ล้าง</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="result-count">
        พบ <strong><?= $result->num_rows ?></strong> รายการ
        <?php if (!empty($search)): ?>
            สำหรับ "<strong><?= htmlspecialchars($search) ?></strong>"
        <?php endif; ?>
    </div>

    <div class="content-card">
        <table class="table-responsive-stack">
            <thead>
                <tr>
                    <th>รูป</th>
                    <th>สถานประกอบการ</th>
                    <th>โทรศัพท์ / ที่อยู่</th>
                    <th>วันที่สร้าง</th>
                    <th>จัดการ</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($result->num_rows === 0): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px;">
                        <i class="fas fa-building" style="font-size: 48px; color: var(--text-muted); opacity: 0.3;"></i>
                        <p style="margin-top: 15px; color: var(--text-muted);">ไม่พบสถานประกอบการ</p>
                        <?php if (!empty($search)): ?>
                            <a href="company_list.php" class="btn btn-primary" style="margin-top: 10px;">ล้างตัวกรอง</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php else: ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td data-label="รูป">
                    <?php 
                    $img_p = '../../uploads/companies/' . $row['com_img'];
                    if (!empty($row['com_img']) && file_exists(__DIR__ . '/../../' . $img_p)): ?>
                        <img class="img-thumb" src="<?= $img_p ?>" alt="Logo">
                    <?php else: ?>
                        <div class="img-thumb" style="display:flex; align-items:center; justify-content:center; background:#f1f1f1; color:#ccc;">
                            <i class="fas fa-image"></i>
                        </div>
                    <?php endif; ?>
                </td>
                <td data-label="สถานประกอบการ">
                    <div style="font-weight:600"><?= htmlspecialchars($row['com_name']) ?></div>
                    <div style="font-size:12px; color:var(--text-muted)">ID: <?= $row['com_id'] ?></div>
                </td>
                <td data-label="ข้อมูลติดต่อ">
                    <div style="font-size:13px"><i class="fas fa-phone-alt" style="width:16px"></i> <?= htmlspecialchars($row['com_tel'] ?? '-') ?></div>
                    <div style="font-size:12px; color:var(--text-muted); max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        <i class="fas fa-map-marker-alt" style="width:16px"></i> 
                        <?= htmlspecialchars(($row['com_add_no'] ? $row['com_add_no'] . ' ' : '') . ($row['com_road'] ? 'ถ.' . $row['com_road'] . ' ' : '') . ($row['com_subdistrict'] ? 'ต.' . $row['com_subdistrict'] . ' ' : '') . ($row['com_district'] ? 'อ.' . $row['com_district'] . ' ' : '') . ($row['com_province'] ? 'จ.' . $row['com_province'] . ' ' : '') . ($row['com_zipcode'] ?? '')) ?: '-' ?>
                    </div>
                </td>
                <td data-label="วันที่สร้าง">
                    <div style="font-size:13px"><?= date('d/m/Y', strtotime($row['com_created'])) ?></div>
                </td>
                <td data-label="จัดการ">
                    <div style="display:flex; gap:5px;">
                        <a class="btn btn-sm btn-view" title="ดูรายละเอียด" href="../Com/company_detail.php?id=<?= $row['com_id'] ?>"><i class="fas fa-eye"></i></a>
                        <a class="btn btn-sm btn-edit" title="แก้ไข" href="../Com/company_edit.php?id=<?= $row['com_id'] ?>"><i class="fas fa-edit"></i></a>
                        <a class="btn btn-sm btn-del" title="ลบ" href="../Com/company_delete.php?id=<?= $row['com_id'] ?>" onclick="return confirm('ยืนยันการลบสถานประกอบการนี้?')"><i class="fas fa-trash"></i></a>
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
