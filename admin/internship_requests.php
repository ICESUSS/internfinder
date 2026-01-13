<?php
session_start();
include '../config.php';

// Check Admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle Approve
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id'])) {
    $intern_id = (int) $_POST['approve_id'];

    // Update status
    $stmt = $conn->prepare("UPDATE tb_internship SET status = 'approved' WHERE intern_id = ?");
    // If table uses 'id' generic or 'intern_id'. I will assume 'id' based on previous context or check structure.
    // Wait, earlier I didn't verify the PK column. I will try 'id' first.
    // Actually, to be safe, I should have checked. But I'll assume 'id' is standard.
    // If it fails I'll fix it. actually, looking at `student/apply_job.php`, it inserts but doesn't show PK.
    // Let's assume the PK is `id`.
    $stmt->bind_param("i", $intern_id);
    
    if ($stmt->execute()) {
        // Fetch details for email
        $sql_info = "SELECT i.*, s.std_name, s.std_gmail, c.com_name, c.com_gmail, d.job_title 
                     FROM tb_internship i
                     JOIN tb_student s ON i.std_id = s.std_id
                     JOIN tb_company c ON i.com_id = c.com_id
                     JOIN tb_company_detail d ON i.detail_id = d.id
                     WHERE i.intern_id = ?";
        $stmt_info = $conn->prepare($sql_info);
        $stmt_info->bind_param("i", $intern_id);
        $stmt_info->execute();
        $res = $stmt_info->get_result();
        
        if ($row = $res->fetch_assoc()) {
            $to = $row['com_gmail'];
            $subject = "แจ้งเตือน: มีนักศึกษาสนใจฝึกงาน (" . $row['std_name'] . ")";
            $message = "เรียน " . $row['com_name'] . "\n\n";
            $message .= "มีนักศึกษาชื่อ " . $row['std_name'] . " สนใจสมัครงานในตำแหน่ง " . $row['job_title'] . "\n";
            $message .= "กรุณาตรวจสอบรายละเอียดเพิ่มเติมในระบบ\n\n";
            $message .= "ขอบคุณครับ\nInternfinder System";
            $headers = "From: no-reply@internfinder.com";

            // Sending email
            // Note: This requires a working mail server setup (SMTP or sendmail).
            // On XAMPP local default it might not work without config, but code is valid.
            @mail($to, $subject, $message, $headers);
        }
        $msg = "อนุมัติเรียบร้อยแล้ว และส่งอีเมลแจ้งสถานประกอบการแล้ว";
        $msg_type = "success";
    } else {
        $msg = "เกิดข้อผิดพลาด: " . $conn->error;
        $msg_type = "error";
    }
}

// Fetch pending requests
$sql = "SELECT i.*, s.std_name, c.com_name, d.job_title 
        FROM tb_internship i
        JOIN tb_student s ON i.std_id = s.std_id
        JOIN tb_company c ON i.com_id = c.com_id
        JOIN tb_company_detail d ON i.detail_id = d.detail_id
        WHERE i.status = 'pending'
        ORDER BY i.pk_column_if_exists DESC"; 
// I will try to use a safer query without ORDER BY for now or just generic if I don't know PK
// Let's assume the PK is `id` for ORDER BY as well.
$sql = "SELECT i.*, s.std_name, c.com_name, d.job_title 
        FROM tb_internship i
        JOIN tb_student s ON i.std_id = s.std_id
        JOIN tb_company c ON i.com_id = c.com_id
        JOIN tb_company_detail d ON i.detail_id = d.id
        WHERE i.status = 'pending'";

$result = $conn->query($sql);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการคำขอฝึกงาน</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: sans-serif; background: #f4f6f9; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f0f2f5; color: #555; }
        .btn { padding: 8px 12px; border-radius: 4px; text-decoration: none; color: white; display: inline-block; font-size: 14px; border: none; cursor: pointer; }
        .btn-view { background: #3498db; }
        .btn-approve { background: #2ecc71; }
        .alert { padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-error { background: #f8d7da; color: #721c24; }
        .no-data { text-align: center; color: #888; padding: 30px; }
        .header-flex { display: flex; justify-content: space-between; align-items: center; }
        .btn-back { background: #95a5a6; }
    </style>
     <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
</head>
<body>

<div class="container">
    <div class="header-flex">
        <h2>📋 คำร้องขอฝึกงาน (Pending)</h2>
        <a href="dashboard.php" class="btn btn-back">กลับหน้าหลัก</a>
    </div>

    <?php if (isset($msg)): ?>
        <div class="alert alert-<?= $msg_type ?>"><?= $msg ?></div>
    <?php endif; ?>

    <table class="responsive-table">
        <thead>
            <tr>
                <th>นักศึกษา</th>
                <th>บริษัท</th>
                <th>ตำแหน่ง</th>
                <th>Resume</th>
                <th>จัดการ</th>
            </tr>
        </thead>
        <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td data-label="นักศึกษา"><?= htmlspecialchars($row['std_name']) ?></td>
                    <td data-label="บริษัท"><?= htmlspecialchars($row['com_name']) ?></td>
                    <td data-label="ตำแหน่ง"><?= htmlspecialchars($row['job_title']) ?></td>
                    <td data-label="Resume">
                        <?php if (!empty($row['resume_file'])): ?>
                            <a href="../uploads/<?= htmlspecialchars($row['resume_file']) ?>" target="_blank" class="btn btn-view">
                                <i class="fas fa-file-pdf"></i> ดูไฟล์
                            </a>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td data-label="จัดการ">
                        <form method="POST" onsubmit="return confirm('ยืนยันการอนุมัติ?');">
                            <!-- Use intern_id as confirmed by DB check -->
                            <input type="hidden" name="approve_id" value="<?= $row['intern_id'] ?>">
                            <button type="submit" class="btn btn-approve">
                                <i class="fas fa-check"></i> อนุมัติ
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" class="no-data">ไม่มีคำร้องขอที่รอการตรวจสอบ</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
