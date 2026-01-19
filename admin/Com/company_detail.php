<?php
session_start();
include __DIR__ . '/../../config.php';

// Auth
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$msg = '';

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0) {
        $dstmt = $conn->prepare("DELETE FROM tb_company_detail WHERE id = ?");
        if ($dstmt) {
            $dstmt->bind_param('i', $id);
            $dstmt->execute();
            $dstmt->close();
            header('Location: company_detail.php?msg=deleted');
            exit;
        }
    }
}

// Handle bulk delete for a company
if (isset($_GET['delete_all']) && isset($_GET['com'])) {
    $del_com = (int)$_GET['com'];
    if ($del_com > 0) {
        $dstmt2 = $conn->prepare("DELETE FROM tb_company_detail WHERE com_id = ?");
        if ($dstmt2) {
            $dstmt2->bind_param('i', $del_com);
            $dstmt2->execute();
            $affected = $dstmt2->affected_rows;
            $dstmt2->close();
            header('Location: company_detail.php?com=' . $del_com . '&msg=deleted_all');
            exit;
        }
    }
}

// Handle add/edit save
if (isset($_POST['save_job'])) {
    $job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
    $com_id = isset($_POST['com_id']) ? (int)$_POST['com_id'] : 0;
    $job_title = trim($_POST['job_title'] ?? '');
    $job_type = trim($_POST['job_type'] ?? '');
    $job_description = trim($_POST['job_description'] ?? '');
    $job_qualification = trim($_POST['job_qualification'] ?? '');
    $job_welfare = trim($_POST['job_welfare'] ?? '');
    $allowance = trim($_POST['allowance'] ?? '');
    $work_time = trim($_POST['work_time'] ?? '');
    $work_day = trim($_POST['work_day'] ?? '');
    $job_capacity = isset($_POST['job_capacity']) ? (int)$_POST['job_capacity'] : 0;

    if ($com_id <= 0 || $job_title === '') {
        $msg = 'กรุณาเลือกบริษัทและกรอกชื่อตำแหน่ง';
    } else {
        if ($job_id > 0) {
            $ustmt = $conn->prepare("UPDATE tb_company_detail SET job_title=?, job_type=?, job_description=?, job_qualification=?, job_welfare=?, allowance=?, work_time=?, work_day=?, job_capacity=? WHERE id=?");
            if ($ustmt) {
                $ustmt->bind_param('ssssssssii', $job_title, $job_type, $job_description, $job_qualification, $job_welfare, $allowance, $work_time, $work_day, $job_capacity, $job_id);
                $ustmt->execute();
                $ustmt->close();
                header('Location: company_detail.php?com=' . $com_id . '&msg=updated');
                exit;
            }
        } else {
            $istmt = $conn->prepare("INSERT INTO tb_company_detail (com_id, job_title, job_type, job_description, job_qualification, job_welfare, allowance, work_time, work_day, job_capacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($istmt) {
                $istmt->bind_param('issssssssi', $com_id, $job_title, $job_type, $job_description, $job_qualification, $job_welfare, $allowance, $work_time, $work_day, $job_capacity);
                $istmt->execute();
                $istmt->close();
                header('Location: company_detail.php?com=' . $com_id . '&msg=added');
                exit;
            }
        }
    }
}

// Load companies for selector with job counts (robust fallback if JOIN query fails)
$companies = [];
$totalCompanies = 0;
$companiesWithDetails = 0;
$cres = mysqli_query($conn, "SELECT c.com_id, c.com_name, COUNT(d.id) AS details_count FROM tb_company c LEFT JOIN tb_company_detail d ON c.com_id = d.com_id GROUP BY c.com_id ORDER BY c.com_name ASC");
if ($cres !== false) {
    while ($crow = mysqli_fetch_assoc($cres)) {
        $crow['details_count'] = (int)($crow['details_count'] ?? 0);
        $companies[] = $crow;
        $totalCompanies++;
        if ($crow['details_count'] > 0) $companiesWithDetails++;
    }
} else {
    // Fallback: run simple company list and aggregate counts separately
    $countsMap = [];
    $cntRes = mysqli_query($conn, "SELECT com_id, COUNT(*) AS details_count FROM tb_company_detail GROUP BY com_id");
    if ($cntRes !== false) {
        while ($r = mysqli_fetch_assoc($cntRes)) {
            $countsMap[(int)$r['com_id']] = (int)$r['details_count'];
        }
    }

    $cres2 = mysqli_query($conn, "SELECT com_id, com_name FROM tb_company ORDER BY com_name ASC");
    if ($cres2 !== false) {
        while ($crow = mysqli_fetch_assoc($cres2)) {
            $crow['details_count'] = $countsMap[(int)$crow['com_id']] ?? 0;
            $companies[] = $crow;
            $totalCompanies++;
            if ($crow['details_count'] > 0) $companiesWithDetails++;
        }
    } else {
        // As last resort, keep companies empty and set message
        $msg = 'ไม่สามารถดึงรายชื่อบริษัทได้ (Error)';
    }
}

// Selected company (default to first)
$sel_com = isset($_GET['com']) ? (int)$_GET['com'] : (count($companies) ? (int)$companies[0]['com_id'] : 0);

// If editing, load job
$editing = null;
if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
    $eid = (int)$_GET['edit'];
    $est = $conn->prepare("SELECT * FROM tb_company_detail WHERE id = ? LIMIT 1");
    if ($est) {
        $est->bind_param('i', $eid);
        $est->execute();
        $eres = $est->get_result();
        $editing = $eres->fetch_assoc();
        $est->close();
        if ($editing) $sel_com = (int)$editing['com_id'];
    }
}

// Load jobs for selected company
$jobs = [];
if ($sel_com > 0) {
    $jstmt = $conn->prepare("SELECT * FROM tb_company_detail WHERE com_id = ? ORDER BY id DESC");
    if ($jstmt) {
        $jstmt->bind_param('i', $sel_com);
        $jstmt->execute();
        $jres = $jstmt->get_result();
        while ($jr = $jres->fetch_assoc()) { $jobs[] = $jr; }
        $jstmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการรายละเอียดตำแหน่ง | Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
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
            max-width: 1000px;
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

        header h1 { font-size: 20px; font-weight: 700; display: flex; align-items: center; gap: 12px; }

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
        
        .btn-danger { background: #FEE2E2; color: var(--danger); }
        .btn-danger:hover { background: var(--danger); color: white; }

        .btn-sm { padding: 6px 12px; border-radius: 8px; font-size: 13px; }

        /* ===== Card & Form ===== */
        .content-card {
            background: var(--bg-card);
            padding: 24px;
            border-radius: 16px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            margin-bottom: 30px;
        }

        .section-title { font-size: 16px; font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group { margin-bottom: 15px; }
        .form-group.full { grid-column: span 2; }

        label { display: block; font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; }
        
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid var(--border);
            background: #F8FAFC;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.2s;
        }

        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--primary);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        /* ===== Table ===== */
        .table-container { overflow-x: auto; border-radius: 12px; border: 1px solid var(--border); }
        table { width: 100%; border-collapse: collapse; }
        th { background: #F8FAFC; padding: 12px 16px; text-align: left; font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; border-bottom: 1px solid var(--border); }
        td { padding: 16px; border-bottom: 1px solid var(--border); font-size: 14px; }
        tr:last-child td { border-bottom: none; }

        .alert-float {
            background: #fff;
            padding: 12px 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            border-left: 4px solid var(--success);
            font-size: 14px;
            font-weight: 500;
        }

        /* Responsive */
        @media (max-width: 768px) {
            header { flex-direction: column; align-items: stretch; gap: 15px; }
            .form-grid { grid-template-columns: 1fr; }
            .form-group.full { grid-column: span 1; }
            
            table thead { display: none; }
            table tr { display: block; padding: 15px; border-bottom: 8px solid var(--bg-main); }
            table td { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f1f1f1; text-align: right; }
            table td:before { content: attr(data-label); font-weight: 600; color: var(--text-muted); text-align: left; padding-right: 10px; }
            table td:last-child { border-bottom: none; }
        }
    </style>
</head>
<body>

<div class="container">
    <?php if(isset($_GET['msg'])): ?>
        <div class="alert-float">
            <i class="fas fa-check-circle"></i> 
            <?php
                $map = ['deleted'=>'ลบรายการเรียบร้อยแล้ว','deleted_all'=>'ลบตำแหน่งทั้งหมดเรียบร้อยแล้ว','added'=>'เพิ่มรายการเรียบร้อยแล้ว','updated'=>'แก้ไขรายการเรียบร้อยแล้ว'];
                echo $map[$_GET['msg']] ?? htmlspecialchars($_GET['msg']);
            ?>
        </div>
    <?php endif; ?>

    <header>
        <h1><i class="fas fa-briefcase text-primary"></i> จัดการรายละเอียดตำแหน่ง</h1>
        <div style="display:flex; gap:10px;">
            <a href="company_list.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> กลับไปบริษัท</a>
        </div>
    </header>

    <div class="content-card">
        <div class="section-title"><i class="fas fa-filter"></i> เลือกสถานประกอบการ</div>
        <form method="get" style="display:flex; gap:10px;">
            <select name="com" style="flex:1;">
                <?php foreach($companies as $c): ?>
                    <option value="<?= $c['com_id'] ?>" <?= $c['com_id']==$sel_com? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['com_name']) ?> (<?= $c['details_count'] ?> ตำแหน่ง)
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-primary" type="submit">ดูข้อมูล</button>
        </form>
    </div>

    <div class="content-card">
        <div class="section-title">
            <i class="fas <?= $editing ? 'fa-edit' : 'fa-plus-circle' ?> text-primary"></i> 
            <?= $editing ? 'แก้ไขตำแหน่งงาน' : 'เพิ่มตำแหน่งงานใหม่' ?>
        </div>
        <form method="post">
            <input type="hidden" name="job_id" value="<?= $editing ? (int)$editing['id'] : 0 ?>">
            <input type="hidden" name="com_id" value="<?= $sel_com ?>">
            
            <div class="form-grid">
                <div class="form-group full">
                    <label>ชื่อตำแหน่งงาน</label>
                    <input name="job_title" placeholder="เช่น โปรแกรมเมอร์, ช่างซ่อมบำรุง" value="<?= $editing ? htmlspecialchars($editing['job_title']) : '' ?>" required>
                </div>
                <div class="form-group">
                    <label>ประเภทงาน</label>
                    <input name="job_type" placeholder="เช่น Full-time, Internship" value="<?= $editing ? htmlspecialchars($editing['job_type']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>เบี้ยเลี้ยง / ค่าตอบแทน</label>
                    <input name="allowance" placeholder="เช่น 300 บาท/วัน หรือ - " value="<?= $editing ? htmlspecialchars($editing['allowance']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>เวลาเข้างาน</label>
                    <input name="work_time" placeholder="เช่น 08:30 - 17:30" value="<?= $editing ? htmlspecialchars($editing['work_time']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>วันทำงาน</label>
                    <input name="work_day" placeholder="เช่น จันทร์ - ศุกร์" value="<?= $editing ? htmlspecialchars($editing['work_day']) : '' ?>">
                </div>
                <div class="form-group">
                    <label>จำนวนที่รับ (คน)</label>
                    <input type="number" name="job_capacity" min="0" placeholder="เช่น 5" value="<?= $editing ? (int)($editing['job_capacity'] ?? 0) : '' ?>">
                </div>
                <div class="form-group full">
                    <label>รายละเอียดงาน</label>
                    <textarea name="job_description" rows="3"><?= $editing ? htmlspecialchars($editing['job_description']) : '' ?></textarea>
                </div>
                <div class="form-group full">
                    <label>คุณสมบัติผู้สมัคร</label>
                    <textarea name="job_qualification" rows="2"><?= $editing ? htmlspecialchars($editing['job_qualification']) : '' ?></textarea>
                </div>
                <div class="form-group full">
                    <label>สวัสดิการเพิ่มเติม</label>
                    <input name="job_welfare" value="<?= $editing ? htmlspecialchars($editing['job_welfare']) : '' ?>">
                </div>
            </div>
            
            <div style="margin-top:20px; display:flex; gap:10px;">
                <button class="btn btn-primary" type="submit" name="save_job">
                    <i class="fas fa-save"></i> <?= $editing ? 'บันทึกการแก้ไข' : 'เพิ่มตำแหน่ง' ?>
                </button>
                <?php if ($editing): ?>
                    <a class="btn btn-back" href="company_detail.php?com=<?= $sel_com ?>">ยกเลิก</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <div class="content-card">
        <?php
        $sel_company = null;
        foreach ($companies as $cc) { if ($cc['com_id'] == $sel_com) { $sel_company = $cc; break; } }
        $sel_company_name = $sel_company ? $sel_company['com_name'] : '—';
        ?>
        <div class="section-title" style="justify-content:space-between;">
            <span><i class="fas fa-list"></i> ตำแหน่งงานของ: <?= htmlspecialchars($sel_company_name) ?></span>
            <?php if (!empty($jobs)): ?>
                <a class="btn btn-sm btn-danger" href="company_detail.php?com=<?= $sel_com ?>&delete_all=1" onclick="return confirm('ลบตำแหน่งทั้งหมดของบริษัทนี้?')">ลบทิ้งทั้งหมด</a>
            <?php endif; ?>
        </div>

        <?php if (empty($jobs)): ?>
            <div style="text-align:center; padding:40px; color:var(--text-muted);">
                <i class="fas fa-folder-open" style="font-size:32px; margin-bottom:10px; opacity:0.3"></i><br>
                ยังไม่มีข้อมูลตำแหน่งงานสำหรับบริษัทนี้
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ชื่อตำแหน่ง</th>
                            <th>ประเภท</th>
                            <th>ค่าตอบแทน</th>
                            <th>จำนวนรับ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($jobs as $j): 
                            // Count approved applications for this position
                            $detail_id = (int)$j['detail_id'] ?? (int)$j['id'];
                            $applied_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM tb_internship WHERE detail_id = $detail_id AND status = 'approved'");
                            $applied_count = mysqli_fetch_assoc($applied_res)['cnt'] ?? 0;
                            $capacity = (int)($j['job_capacity'] ?? 0);
                            $is_full = ($capacity > 0 && $applied_count >= $capacity);
                        ?>
                            <tr>
                                <td data-label="ตำแหน่ง">
                                    <div style="font-weight:600"><?= htmlspecialchars($j['job_title']) ?></div>
                                    <div style="font-size:12px; color:var(--text-muted);"><?= htmlspecialchars($j['work_time']) ?></div>
                                </td>
                                <td data-label="ประเภท"><?= htmlspecialchars($j['job_type']) ?></td>
                                <td data-label="เบี้ยเลี้ยง"><?= htmlspecialchars($j['allowance']) ?></td>
                                <td data-label="จำนวนรับ">
                                    <?php if ($capacity > 0): ?>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <span style="font-weight: 600; color: <?= $is_full ? 'var(--danger)' : 'var(--success)' ?>">
                                                <?= $applied_count ?>/<?= $capacity ?>
                                            </span>
                                            <?php if ($is_full): ?>
                                                <span style="background: #FEE2E2; color: #991B1B; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">เต็ม</span>
                                            <?php else: ?>
                                                <span style="background: #DCFCE7; color: #166534; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">เปิดรับ</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted);">ไม่จำกัด</span>
                                    <?php endif; ?>
                                </td>
                                <td data-label="จัดการ">
                                    <div style="display:flex; gap:5px;">
                                        <a class="btn btn-sm" style="background:#EEF2FF; color:var(--primary);" href="company_detail.php?com=<?= $sel_com ?>&edit=<?= (int)$j['id'] ?>"><i class="fas fa-edit"></i></a>
                                        <a class="btn btn-sm btn-danger" href="company_detail.php?com=<?= $sel_com ?>&delete=<?= (int)$j['id'] ?>" onclick="return confirm('ลบตำแหน่งนี้?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
