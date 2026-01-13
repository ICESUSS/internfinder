<?php
session_start();
include '../config.php';

/* ===== ตรวจสอบสิทธิ์ ===== */
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

/* ===== ดึงข้อมูลสถิติ ===== */

// นักศึกษาทั้งหมด
$std_all = $conn->query("SELECT COUNT(*) AS total FROM tb_student")
                ->fetch_assoc()['total'];

// นักศึกษายังไม่มีที่ฝึกงาน
$std_no_intern = $conn->query("
    SELECT COUNT(*) AS total 
    FROM tb_student 
    WHERE com_id IS NULL
")->fetch_assoc()['total'];

// นักศึกษามีที่ฝึกงานแล้ว
$std_have_intern = $conn->query("
    SELECT COUNT(*) AS total 
    FROM tb_student 
    WHERE com_id IS NOT NULL
")->fetch_assoc()['total'];

// สถานประกอบการ
$company_total = $conn->query("
    SELECT COUNT(*) AS total FROM tb_company
")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body{font-family:sans-serif;background:#f4f6f9;margin:0}
.container{padding:30px}
h1{margin-bottom:20px}

.cards{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
    gap:20px;
}

.card{
    background:#fff;
    border-radius:12px;
    padding:20px;
    box-shadow:0 4px 12px rgba(0,0,0,.08);
}

.card i{
    font-size:30px;
    margin-bottom:10px;
    color:#2196F3;
}

.card h2{margin:10px 0;font-size:28px}
.card p{color:#666}

.actions{
    margin-top:40px;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:20px;
}

.action{
    background:#fff;
    border-radius:12px;
    padding:25px;
    box-shadow:0 4px 12px rgba(0,0,0,.08);
}

.action h3{margin-bottom:10px}
.action a{
    display:inline-block;
    margin-top:10px;
    padding:10px 16px;
    background:#2196F3;
    color:#fff;
    text-decoration:none;
    border-radius:8px;
}

.action a:hover{background:#1976D2}
</style>
<link rel="stylesheet" href="../assets/css/mobile-responsive.css">
</head>

<body>

<div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center">
        <h1>📊 แผงควบคุมผู้ดูแลระบบ</h1>
        <a class="logout" href="../logout.php?logout=1" onclick="return confirm('ออกจากระบบ?')">ออกจากระบบ</a>
    </div>

    <!-- ===== STAT CARDS ===== -->
    <div class="cards">
        <div class="card">
            <i class="fas fa-user-graduate"></i>
            <p>นักศึกษาทั้งหมด</p>
            <h2><?= $std_all ?></h2>
        </div>

        <div class="card">
            <i class="fas fa-user-clock"></i>
            <p>ยังไม่มีที่ฝึกงาน</p>
            <h2><?= $std_no_intern ?></h2>
        </div>

        <div class="card">
            <i class="fas fa-user-check"></i>
            <p>มีที่ฝึกงานแล้ว</p>
            <h2><?= $std_have_intern ?></h2>
        </div>

        <div class="card">
            <i class="fas fa-building"></i>
            <p>สถานประกอบการ</p>
            <h2><?= $company_total ?></h2>
        </div>
    </div>

    <!-- ===== MANAGEMENT ===== -->
    <div class="actions">

        <div class="action">
            <h3>👨‍🎓 จัดการข้อมูลนักศึกษา</h3>
            <p>เพิ่ม / ลบ / แก้ไข / ดูข้อมูล  
               นักศึกษาที่มีหรือยังไม่มีที่ฝึกงาน</p>
            <a href="Student/student_list.php">จัดการนักศึกษา</a>
        </div>

        <div class="action">
            <h3>📋 คำร้องขอฝึกงาน</h3>
            <p>ตรวจสอบและอนุมัติคำขอฝึกงานจากนักศึกษา</p>
            <a href="internship_requests.php" style="background: #E91E63;">ดูคำร้องขอ</a>
        </div>

        <div class="action">
            <h3>🏢 จัดการสถานประกอบการ</h3>
            <p>เพิ่ม ลบ แก้ไข ข้อมูลสถานประกอบการ</p>
            <a href="Com/company_list.php">จัดการสถานประกอบการ</a>
        </div>

        <div class="action">
            <h3>📄 เอกสารฝึกงาน (PDF)</h3>
            <p>ดาวน์โหลดเอกสารสำหรับฝึกงาน</p>
            <a href="documents/internship_forms.pdf" target="_blank">
                ดาวน์โหลด PDF
            </a>
        </div>

         <div class="action">
            <h3>ปัญหา ที่ถูกรายงาน</h3>
            <p>ปัญหาที่ นักศึกษาทั้งหมดรายงาน</p>
            <a href="../admin/report.php">
                ดูรายงานปัญหา
            </a>
        </div>

    </div>
</div>

</body>
</html>
