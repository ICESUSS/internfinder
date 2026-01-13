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

// คิวรีบริษัท
$sql = "SELECT * FROM tb_company ORDER BY com_created DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>จัดการสถานประกอบการ</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
body{font-family:sans-serif;background:#f4f6f9;margin:0}
.container{padding:30px}
h1{margin-bottom:20px}

.actions{display:flex;gap:10px;margin-bottom:20px}
.actions a{padding:10px 16px;background:#2196F3;color:#fff;text-decoration:none;border-radius:8px}
.actions a.gray{background:#607D8B}

table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.08)}
th,td{padding:12px;border-bottom:1px solid #eee;text-align:left}
th{background:#f0f2f5}

.img-thumb{width:72px;height:48px;object-fit:cover;border-radius:8px}

.btn{padding:6px 10px;border-radius:6px;text-decoration:none;font-size:14px}
.edit{background:#FFC107;color:#000}
.del{background:#E53935;color:#fff}
.view{background:#4CAF50;color:#fff}
</style>
</head>

<body>
<?php if($msg): ?>
    <p style="color:red;">
    <?php
        if ($msg == 'has_internship') echo "❌ ไม่สามารถลบได้ เนื่องจากมีประวัติฝึกงานหรือมีนักศึกษา/การฝึกงานที่อ้างอิงบริษัทนี้";
        if ($msg == 'deleted') echo "✅ ลบข้อมูลเรียบร้อยแล้ว";
        if ($msg == 'error') echo "❌ เกิดข้อผิดพลาด";
        if ($msg == 'notfound') echo "❌ ไม่พบบริษัท";
    ?>
    </p>
<?php endif; ?>

<div class="container">
    <h1>🏢 จัดการข้อมูลสถานประกอบการ</h1>

    <div class="actions">
        <a href="../dashboard.php">กลับ</a>
        <a href="company_edit.php">➕ เพิ่มสถานประกอบการ</a>
    </div>

    <table>
        <tr>
            <th>รหัส</th>
            <th>รูป</th>
            <th>ชื่อสถานประกอบการ</th>
            <th>โทร</th>
            <th>ที่อยู่</th>
            <th>วันที่สร้าง</th>
            <th>จัดการ</th>
        </tr>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['com_id']) ?></td>
            <td>
                <?php if (!empty($row['com_img']) && file_exists(__DIR__ . '/../../uploads/companies/' . $row['com_img'])): ?>
                    <img class="img-thumb" src="../../uploads/companies/<?= htmlspecialchars($row['com_img']) ?>" alt="">
                <?php else: ?>
                    -
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($row['com_name']) ?></td>
            <td><?= htmlspecialchars($row['com_tel'] ?? '-') ?></td>
            <td style="max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($row['com_address'] ?? '-') ?></td>
            <td><?= htmlspecialchars($row['com_created'] ?? '-') ?></td>
            <td>
                <a class="btn view" href="../Com/company_detail.php?id=<?= $row['com_id'] ?>">ดู</a>
                <a class="btn edit" href="../Com/company_edit.php?id=<?= $row['com_id'] ?>">แก้ไข</a>
                <a class="btn del" href="../Com/company_delete.php?id=<?= $row['com_id'] ?>" onclick="return confirm('ลบสถานประกอบการนี้?')">ลบ</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

</body>
</html>
