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

    if ($com_id <= 0 || $job_title === '') {
        $msg = 'กรุณาเลือกบริษัทและกรอกชื่อตำแหน่ง';
    } else {
        if ($job_id > 0) {
            $ustmt = $conn->prepare("UPDATE tb_company_detail SET job_title=?, job_type=?, job_description=?, job_qualification=?, job_welfare=?, allowance=?, work_time=?, work_day=? WHERE id=?");
            if ($ustmt) {
                $ustmt->bind_param('ssssssssi', $job_title, $job_type, $job_description, $job_qualification, $job_welfare, $allowance, $work_time, $work_day, $job_id);
                $ustmt->execute();
                $ustmt->close();
                header('Location: company_detail.php?com=' . $com_id . '&msg=updated');
                exit;
            }
        } else {
            $istmt = $conn->prepare("INSERT INTO tb_company_detail (com_id, job_title, job_type, job_description, job_qualification, job_welfare, allowance, work_time, work_day) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            if ($istmt) {
                $istmt->bind_param('issssssss', $com_id, $job_title, $job_type, $job_description, $job_qualification, $job_welfare, $allowance, $work_time, $work_day);
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
<meta charset="utf-8">
<title>จัดการรายละเอียดตำแหน่ง - Admin</title>
<style>
body{font-family:sans-serif;background:#f4f6f9;margin:0;padding:20px}
.container{max-width:900px;margin:auto}
.card{background:#fff;padding:16px;border-radius:8px;box-shadow:0 6px 20px rgba(0,0,0,.06);margin-bottom:16px}
.row{display:flex;gap:12px;align-items:center}
.select{padding:8px;border-radius:8px;border:1px solid #ddd}
.btn{padding:8px 12px;border-radius:8px;background:#2196F3;color:#fff;text-decoration:none;border:0}
.btn.red{background:#e53935}
.tbl{width:100%;border-collapse:collapse}
.tbl th,.tbl td{padding:8px;border-bottom:1px solid #eee;text-align:left}
.form-group{margin-bottom:10px}
input,textarea,select{width:100%;padding:8px;border-radius:6px;border:1px solid #ddd}
.job-actions a{margin-right:6px}
.msg{padding:8px;background:#e8f5e9;border-radius:6px;color:#2e7d32;margin-bottom:10px}
</style>
</head>
<body>
<div class="container">
    <h2>จัดการรายละเอียดตำแหน่ง (tb_company_detail)</h2>

    <?php if(isset($_GET['msg'])): ?>
        <?php
            $map = [
                'deleted' => 'ลบรายการเรียบร้อยแล้ว',
                'deleted_all' => 'ลบตำแหน่งทั้งหมดเรียบร้อยแล้ว',
                'added' => 'เพิ่มรายการเรียบร้อยแล้ว',
                'updated' => 'แก้ไขรายการเรียบร้อยแล้ว'
            ];
            $m = $_GET['msg'];
            $displayMsg = isset($map[$m]) ? $map[$m] : htmlspecialchars($m, ENT_QUOTES, 'UTF-8');
        ?>
        <div class="msg"><?php echo $displayMsg; ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="get" action="company_detail.php" class="row">
            <label for="com">บริษัท</label>
            <select id="com" name="com" class="select">
                <?php foreach($companies as $c): ?>
                    <option value="<?= $c['com_id'] ?>" <?= $c['com_id']==$sel_com? 'selected' : '' ?>><?= htmlspecialchars($c['com_name']) ?> <?= $c['details_count'] > 0 ? ' — มี '.$c['details_count'].' รายการ' : ' — ยังไม่มีข้อมูล' ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn" type="submit">เลือก</button>
            <a class="btn" href="company_list.php" style="margin-left:auto;">กลับไปบริษัท</a>
        </form>
    </div>

    <!-- Summary: which companies have details -->
    <div style="margin-bottom:10px;color:#333">
        มีรายละเอียดแล้ว: <strong><?= $companiesWithDetails ?></strong> / <?= $totalCompanies ?> บริษัท
        &nbsp; &nbsp;|&nbsp; &nbsp;
        <small style="color:#666">(ชื่อตัวเลือกจะแสดงจำนวนตำแหน่งที่บันทึกไว้)</small>
    </div>

    <div class="card">
        <h3><?= $editing ? 'แก้ไขตำแหน่ง' : 'เพิ่มตำแหน่ง' ?></h3>
        <?php if (!empty($msg)) echo '<div class="msg">'.htmlspecialchars($msg).'</div>'; ?>
        <form method="post">
            <input type="hidden" name="job_id" value="<?= $editing ? (int)$editing['id'] : 0 ?>">
            <div class="form-group">
                <label>บริษัท</label>
                <select name="com_id">
                    <?php foreach($companies as $c): ?>
                        <option value="<?= $c['com_id'] ?>" <?= $c['com_id']==($editing? (int)$editing['com_id'] : $sel_com) ? 'selected' : '' ?>><?= htmlspecialchars($c['com_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>ชื่อตำแหน่ง</label>
                <input name="job_title" value="<?= $editing ? htmlspecialchars($editing['job_title']) : '' ?>">
            </div>
            <div class="form-group">
                <label>ประเภทงาน</label>
                <input name="job_type" value="<?= $editing ? htmlspecialchars($editing['job_type']) : '' ?>">
            </div>
            <div class="form-group">
                <label>รายละเอียด</label>
                <textarea name="job_description" rows="4"><?= $editing ? htmlspecialchars($editing['job_description']) : '' ?></textarea>
            </div>
            <div class="form-group">
                <label>คุณสมบัติ</label>
                <textarea name="job_qualification" rows="2"><?= $editing ? htmlspecialchars($editing['job_qualification']) : '' ?></textarea>
            </div>
            <div class="form-group">
                <label>สวัสดิการ</label>
                <input name="job_welfare" value="<?= $editing ? htmlspecialchars($editing['job_welfare']) : '' ?>">
            </div>
            <div class="form-group">
                <label>เบี้ยเลี้ยง</label>
                <input name="allowance" value="<?= $editing ? htmlspecialchars($editing['allowance']) : '' ?>">
            </div>
            <div class="form-group">
                <label>เวลา</label>
                <input name="work_time" value="<?= $editing ? htmlspecialchars($editing['work_time']) : '' ?>">
            </div>
            <div class="form-group">
                <label>วันทำงาน</label>
                <input name="work_day" value="<?= $editing ? htmlspecialchars($editing['work_day']) : '' ?>">
            </div>
            <button class="btn" type="submit" name="save_job"><?= $editing ? 'บันทึกการแก้ไข' : 'เพิ่มตำแหน่ง' ?></button>
            <?php if ($editing): ?>
                <a class="btn" href="company_detail.php?com=<?= $sel_com ?>" style="margin-left:8px;background:#607D8B">ยกเลิก</a>
            <?php endif; ?>
        </form>
    </div>

    <?php
    // find selected company details
    $sel_company = null;
    foreach ($companies as $cc) { if ($cc['com_id'] == $sel_com) { $sel_company = $cc; break; } }
    $sel_company_name = $sel_company ? $sel_company['com_name'] : '—';
    $sel_company_count = $sel_company ? (int)$sel_company['details_count'] : 0;
    ?>
    <div class="card">
        <h3 style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <span>รายการตำแหน่งสำหรับบริษัท: <?= htmlspecialchars($sel_company_name) ?> <?php if ($sel_company_count>0) echo '<span style="color:#2e7d32">('.$sel_company_count.' รายการ)</span>'; else echo '<span style="color:#999">(ยังไม่มีตำแหน่ง)</span>'; ?></span>
            <?php if ($sel_company_count > 0): ?>
                <a class="btn red" href="company_detail.php?com=<?= $sel_com ?>&delete_all=1" onclick="return confirm('คุณแน่ใจว่าต้องการลบตำแหน่งทั้งหมดของบริษัทนี้?')" style="margin-left:auto">ลบทั้งหมด</a>
            <?php endif; ?>
        </h3>

        <?php if (empty($jobs)): ?>
            <div class="card">ไม่มีตำแหน่ง</div>
        <?php else: ?>
            <table class="tbl">
                <thead>
                    <tr><th>#</th><th>ชื่อตำแหน่ง</th><th>ประเภท</th><th>เบี้ยเลี้ยง</th><th>เวลา</th><th>จัดการ</th></tr>
                </thead>
                <tbody>
                    <?php foreach($jobs as $j): ?>
                        <tr>
                            <td><?= (int)$j['id'] ?></td>
                            <td><?= htmlspecialchars($j['job_title']) ?></td>
                            <td><?= htmlspecialchars($j['job_type']) ?></td>
                            <td><?= htmlspecialchars($j['allowance']) ?></td>
                            <td><?= htmlspecialchars($j['work_time']) ?> (<?= htmlspecialchars($j['work_day']) ?>)</td>
                            <td class="job-actions">
                                <a class="btn" href="company_detail.php?com=<?= $sel_com ?>&edit=<?= (int)$j['id'] ?>">แก้ไข</a>
                                <a class="btn red" href="company_detail.php?com=<?= $sel_com ?>&delete=<?= (int)$j['id'] ?>" onclick="return confirm('ลบตำแหน่งนี้?')">ลบ</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Bottom summary & actions -->
            <div style="margin-top:12px;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
                <div style="color:#333">รวมทั้งหมด <strong><?= $sel_company_count ?></strong> ตำแหน่ง</div>
                <?php if ($sel_company_count > 0): ?>
                    <div style="margin-left:auto">
                        <a class="btn" href="company_detail.php?com=<?= $sel_com ?>" style="background:#607D8B;margin-right:8px">รีเฟรช</a>
                        <a class="btn red" href="company_detail.php?com=<?= $sel_com ?>&delete_all=1" onclick="return confirm('คุณแน่ใจว่าต้องการลบตำแหน่งทั้งหมดของบริษัทนี้?')">ลบทั้งหมด</a>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>

</div>
</body>
</html>