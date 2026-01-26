<?php
session_start();
include '../config.php';

if (!isset($_SESSION['student_login'])) {
    $_SESSION['error'] = 'กรุณาเข้าสู่ระบบ!';
    header('location: ../login.php');
    exit;
}

$std_id = $_SESSION['student_login'];

// Fetch Internship History
$sql = "SELECT i.*, c.com_name, d.job_title 
        FROM tb_internship i 
        LEFT JOIN tb_company c ON i.com_id = c.com_id 
        LEFT JOIN tb_company_detail d ON i.detail_id = d.detail_id 
        WHERE i.std_id = ? 
        ORDER BY i.intern_id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $std_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการยื่นคำร้อง - InternFinder</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; background-color: #f8f9fa; }
        .card { border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 0.85rem; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                
                <?php if(isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header bg-white py-3 border-bottom">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0 text-primary">ประวัติการยื่นคำร้องฝึกงาน</h4>
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">กลับหน้าหลัก</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>วันที่ยื่น</th>
                                            <th>สถานประกอบการ</th>
                                            <th>ตำแหน่ง</th>
                                            <th>ระยะเวลา</th>
                                            <th>สถานะ</th>
                                            <th>จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= date('d/m/Y', strtotime($row['created_at'] ?? 'now')) ?></td> <!-- Assuming created_at exists or just use now -->
                                                <td><?= htmlspecialchars($row['com_name']) ?></td>
                                                <td><?= htmlspecialchars($row['job_title']) ?></td>
                                                <td>
                                                    <small>
                                                        <?= date('d/m/Y', strtotime($row['start_date'])) ?> <br> 
                                                        ถึง <?= date('d/m/Y', strtotime($row['end_date'])) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $status = $row['status'];
                                                        $status_text = 'รอดำเนินการ';
                                                        $badge_class = 'status-pending';
                                                        
                                                        if ($status == 'approved' || $status == 1) { // Check schema
                                                            $status_text = 'อนุมัติแล้ว';
                                                            $badge_class = 'status-approved';
                                                        } elseif ($status == 'rejected' || $status == 2) {
                                                            $status_text = 'ปฏิเสธ';
                                                            $badge_class = 'status-rejected';
                                                        }
                                                    ?>
                                                    <span class="status-badge <?= $badge_class ?>"><?= $status_text ?></span>
                                                </td>
                                                <td>
                                                    <!-- Link to print documents if approved? or just detail -->
                                                    <a href="print_request.php?id=<?= $row['intern_id'] ?>" class="btn btn-sm btn-info text-white" target="_blank">พิมพ์ใบคำร้อง</a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <p>ยังไม่มีประวัติการยื่นคำร้อง</p>
                                <a href="index.php" class="btn btn-primary">ค้นหาสถานที่ฝึกงาน</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
