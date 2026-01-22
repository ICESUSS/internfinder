<?php
session_start();
include '../config.php';

// ตรวจสอบการ login
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

$std_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';

// Student data fetching removed

// ดึงบริษัทที่บันทึกไว้
$savedCompanies = [];
if ($std_id) {
    $sql = "SELECT s.save_id, s.com_id, s.created_at, c.* FROM tb_saved s INNER JOIN tb_company c ON s.com_id = c.com_id WHERE s.std_id = ? ORDER BY s.created_at DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('i', $std_id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($r = $res->fetch_assoc()) $savedCompanies[] = $r;
        }
        $stmt->close();
    }
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internfider - ที่บันทึก</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/student-dashboard.css">
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <main style="padding-bottom: 60px;">
        <div class="page-title">ที่บันทึก</div>

        <div class="company-list" style="padding: 0 15px;">
            <?php if (count($savedCompanies) === 0): ?>
                <div class="card">
                    <div class="card-body">
                        <div style="flex:1;">
                            <div class="empty-state">ยังไม่มีบริษัทที่บันทึกไว้</div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($savedCompanies as $row): ?>
                    <div class="card" data-save-id="<?php echo (int)$row['save_id']; ?>" id="card-<?php echo (int)$row['com_id']; ?>">
                        <div class="card-body">
                            <div class="company-logo"><i class="fas fa-building"></i></div>
                            <div class="company-info">
                                <h3><?php echo htmlspecialchars($row['com_name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($row['com_tel'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <p class="address">
                                    <?php 
                                        $full_addr = ($row['com_add_no'] ? $row['com_add_no'] . ' ' : '') . ($row['com_road'] ? 'ถ.' . $row['com_road'] . ' ' : '') . ($row['com_subdistrict'] ? 'ต.' . $row['com_subdistrict'] . ' ' : '') . ($row['com_district'] ? 'อ.' . $row['com_district'] . ' ' : '') . ($row['com_province'] ? 'จ.' . $row['com_province'] . ' ' : '') . ($row['com_zipcode'] ?? '');
                                        echo htmlspecialchars(mb_strimwidth($full_addr ?: '-', 0, 50, "..."), ENT_QUOTES, 'UTF-8'); 
                                    ?>
                                </p>
                            </div>
                            <button class="save-btn" type="button" data-com-id="<?php echo (int)$row['com_id']; ?>" aria-label="บันทึกบริษัท">
                                <i class="fas fa-bookmark"></i>
                            </button>
                        </div>
                        <div class="card-footer">
                            <span class="tag">บันทึกเมื่อ: <?php echo htmlspecialchars($row['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                            <a href="details.php?id=<?php echo (int)$row['com_id']; ?>" class="view-more">ดูรายละเอียด</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>


</body>
</html>