<?php
session_start();
include __DIR__ . '/../../config.php';

/* ===== auth ===== */
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
} 

/* ===== filter ===== */
$status = $_GET['status'] ?? 'all';
$where = "";

if ($status === 'no') {
    $where = "WHERE s.com_id IS NULL";
} elseif ($status === 'yes') {
    $where = "WHERE s.com_id IS NOT NULL";
}

/* ===== query ===== */
$sql = "
SELECT 
    s.std_id,
    s.std_name,
    s.std_level,
    s.std_tel,
    s.std_gmail,
    d.dep_name,
    c.com_name
FROM tb_student s
LEFT JOIN tb_department d ON s.dep_id = d.dep_id
LEFT JOIN tb_company c ON s.com_id = c.com_id
$where
ORDER BY s.std_id DESC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>จัดการนักศึกษา</title>
<link rel="stylesheet" href="../../assets/css/mobile-responsive.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body{font-family:sans-serif;background:#f4f6f9;margin:0}
.container{padding:30px}
h1{margin-bottom:20px}

.actions{
    display:flex;
    gap:10px;
    margin-bottom:20px;
}

.actions a{
    padding:10px 16px;
    background:#2196F3;
    color:#fff;
    text-decoration:none;
    border-radius:8px;
}

.actions a.gray{background:#607D8B}
.actions a.green{background:#4CAF50}

table{
    width:100%;
    border-collapse:collapse;
    background:#fff;
    border-radius:12px;
    overflow:hidden;
    box-shadow:0 4px 12px rgba(0,0,0,.08);
}

th,td{
    padding:12px;
    border-bottom:1px solid #eee;
    text-align:left;
}

th{background:#f0f2f5}

.badge{
    padding:6px 12px;
    border-radius:20px;
    font-size:13px;
}

.yes{background:#e8f5e9;color:#2e7d32}
.no{background:#ffebee;color:#c62828}

.btn{
    padding:6px 10px;
    border-radius:6px;
    text-decoration:none;
    font-size:14px;
}

.edit{background:#FFC107;color:#000}
.del{background:#E53935;color:#fff}
</style>
</head>

<body>
<?php if(isset($_GET['msg'])) { ?>
    <p style="color:red; ">
    <?php
        if ($_GET['msg'] == 'has_internship') echo "❌ ไม่สามารถลบได้ เนื่องจากมีประวัติฝึกงาน";
        if ($_GET['msg'] == 'deleted') echo "✅ ลบข้อมูลเรียบร้อยแล้ว";
        if ($_GET['msg'] == 'error') echo "❌ เกิดข้อผิดพลาด";
        if ($_GET['msg'] == 'notfound') echo "❌ ไม่พบนักศึกษา";
    ?>
    </p>
<?php } ?>

<div class="container">
    <h1>👨‍🎓 จัดการข้อมูลนักศึกษา</h1>

    <!-- FILTER -->
    <div class="actions">
        <a href="../dashboard.php">กลับ</a>
        <a href="?status=all" class="gray">ทั้งหมด</a>
        <a href="?status=no">ยังไม่มีที่ฝึกงาน</a>
        <a href="?status=yes" class="green">มีที่ฝึกงานแล้ว</a>
        <a href="../Student/student_add.php">➕ เพิ่มนักศึกษา</a>
    </div>

    <!-- TABLE -->
    <table>
        <tr>
            <th>รหัส</th>
            <th>ชื่อ-สกุล</th>
            <th>ระดับชั้น</th>
            <th>แผนก</th>
            <th>โทร</th>
            <th>สถานประกอบการ</th>
            <th>สถานะ</th>
            <th>จัดการ</th>
        </tr>

        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['std_id'] ?></td>
            <td><?= $row['std_name'] ?></td>
            <td><?= $row['std_level'] ?? '-' ?></td>
            <td><?= $row['dep_name'] ?></td>
            <td><?= $row['std_tel'] ?></td>
            <td><?= $row['com_name'] ?? '-' ?></td>
            <td>
                <?php if($row['com_name']): ?>
                    <span class="badge yes">มีที่ฝึกงาน</span>
                <?php else: ?>
                    <span class="badge no">ยังไม่มี</span>
                <?php endif; ?>
            </td>
            <td>
                <a class="btn edit" href="../Student/student_edit.php?id=<?= $row['std_id'] ?>">แก้ไข</a>
                <a class="btn del" href="student_delete.php?id=<?= $row['std_id'] ?>"
                   onclick="return confirm('ลบข้อมูลนักศึกษาคนนี้?')">
                   ลบ
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
