<?php
session_start();
include __DIR__ . '/../../config.php';

// Auth
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../../login.php");
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$company = null;

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM tb_company WHERE com_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $company = $stmt->get_result()->fetch_assoc();
}

// Fallback for new company
if (!$company) {
    $company = [
        'com_id' => 0,
        'com_name' => '',
        'com_tel' => '',
        'com_contact' => '',
        'com_contact_pos' => '',
        'com_detail' => '',
        'com_add_no' => '',
        'com_road' => '',
        'com_subdistrict' => '',
        'com_district' => '',
        'com_province' => '',
        'com_zipcode' => '',
        'com_detail' => '',
        'com_img' => ''
    ];
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id > 0 ? 'แก้ไข' : 'เพิ่ม' ?>สถานประกอบการ | Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338CA;
            --bg-main: #F8FAFC;
            --bg-card: #FFFFFF;
            --text-main: #1E293B;
            --text-muted: #64748B;
            --border: #E2E8F0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', 'Sarabun', sans-serif; background: var(--bg-main); color: var(--text-main); padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .content-card { background: var(--bg-card); padding: 30px; border-radius: 16px; box-shadow: var(--shadow); border: 1px solid var(--border); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full { grid-column: span 2; }
        label { display: block; font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 8px; }
        input, select, textarea { width: 100%; padding: 12px; border-radius: 10px; border: 1px solid var(--border); background: #F8FAFC; font-family: inherit; font-size: 14px; transition: all 0.2s; }
        input:focus, textarea:focus { outline: none; border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        .btn { padding: 12px 24px; border-radius: 10px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; transition: 0.2s; }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-back { background: #F1F5F9; color: var(--text-muted); }
        .section-title { font-size: 16px; font-weight: 700; margin-bottom: 20px; color: var(--primary); border-bottom: 2px solid #EEF2FF; padding-bottom: 10px; }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h2><i class="fas fa-building text-primary"></i> <?= $id > 0 ? 'แก้ไข' : 'เพิ่ม' ?>สถานประกอบการ</h2>
        <a href="company_list.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> ยกเลิก</a>
    </header>

    <div class="content-card">
        <form action="company_save.php" method="post" enctype="multipart/form-data">
            <input type="hidden" name="com_id" value="<?= $company['com_id'] ?>">

            <div class="section-title">ข้อมูลทั่วไป</div>
            <div class="form-grid">
                <div class="form-group full">
                    <label>ชื่อสถานประกอบการ</label>
                    <input type="text" name="com_name" value="<?= htmlspecialchars($company['com_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>เบอร์โทรศัพท์ติดต่อ</label>
                    <input type="text" name="com_tel" value="<?= htmlspecialchars($company['com_tel']) ?>">
                </div>
                <div class="form-group">
                    <label>ชื่อผู้ประสานงาน</label>
                    <input type="text" name="com_contact" value="<?= htmlspecialchars($company['com_contact']) ?>">
                </div>
                <div class="form-group">
                    <label>ตำแหน่งผู้ประสานงาน</label>
                    <input type="text" name="com_contact_pos" value="<?= htmlspecialchars($company['com_contact_pos'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>โลโก้บริษัท</label>
                    <input type="file" name="com_img" accept="image/*">
                </div>
            </div>

            <div class="section-title">ที่อยู่ตามระเบียบ (สำหรับใช้ในใบขอฝึกงาน)</div>
            <div class="form-grid">
                <div class="form-group">
                    <label>บ้านเลขที่ / เลขที่</label>
                    <input type="text" name="com_add_no" value="<?= htmlspecialchars($company['com_add_no'] ?? '') ?>" placeholder="เช่น 123/45">
                </div>
                <div class="form-group">
                    <label>ถนน</label>
                    <input type="text" name="com_road" value="<?= htmlspecialchars($company['com_road'] ?? '') ?>" placeholder="เช่น กาญจนาภิเษก">
                </div>
                <div class="form-group">
                    <label>ตำบล / แขวง</label>
                    <input type="text" name="com_subdistrict" value="<?= htmlspecialchars($company['com_subdistrict'] ?? '') ?>" placeholder="ตำบล">
                </div>
                <div class="form-group">
                    <label>อำเภอ / เขต</label>
                    <input type="text" name="com_district" value="<?= htmlspecialchars($company['com_district'] ?? '') ?>" placeholder="อำเภอ">
                </div>
                <div class="form-group">
                    <label>จังหวัด</label>
                    <input type="text" name="com_province" value="<?= htmlspecialchars($company['com_province'] ?? '') ?>" placeholder="จังหวัด">
                </div>
                <div class="form-group">
                    <label>รหัสไปรษณีย์</label>
                    <input type="text" name="com_zipcode" value="<?= htmlspecialchars($company['com_zipcode'] ?? '') ?>" placeholder="10xxx">
                </div>
            </div>

            <div class="section-title">รายละเอียดอื่นๆ</div>
            <div class="form-group full">
                <textarea name="com_detail" rows="4"><?= htmlspecialchars($company['com_detail']) ?></textarea>
            </div>

            <div style="margin-top: 20px; text-align: right;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> บันทึกข้อมูล</button>
            </div>
        </form>
    </div>
</div>

</body>
</html>
