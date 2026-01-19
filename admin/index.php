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
    WHERE std_id NOT IN (SELECT std_id FROM tb_internship WHERE status = 'approved')
")->fetch_assoc()['total'];

// นักศึกษามีที่ฝึกงานแล้ว
$std_have_intern = $conn->query("
    SELECT COUNT(*) AS total 
    FROM tb_student 
    WHERE std_id IN (SELECT std_id FROM tb_internship WHERE status = 'approved')
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
    <title>Admin Dashboard | InternFinder</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary: #4F46E5;
            --primary-hover: #4338CA;
            --secondary: #64748B;
            --success: #10B981;
            --danger: #EF4444;
            --warning: #F59E0B;
            --bg-main: #F8FAFC;
            --bg-card: #FFFFFF;
            --text-main: #1E293B;
            --text-muted: #64748B;
            --border: #E2E8F0;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Sarabun', sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            line-height: 1.5;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        /* ===== Header ===== */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .btn-logout {
            background-color: #fee2e2;
            color: var(--danger);
        }

        .btn-logout:hover {
            background-color: var(--danger);
            color: white;
        }

        /* ===== Stats Grid ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--bg-card);
            padding: 24px;
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow);
        }

        .stat-card i {
            font-size: 24px;
            padding: 12px;
            border-radius: 12px;
            background: #F1F5F9;
            color: var(--primary);
            margin-bottom: 16px;
        }

        .stat-card.students i { background: #EEF2FF; color: var(--primary); }
        .stat-card.pending i { background: #FFF7ED; color: var(--warning); }
        .stat-card.approved i { background: #ECFDF5; color: var(--success); }
        .stat-card.company i { background: #F8FAFC; color: var(--secondary); }

        .stat-card p {
            color: var(--text-muted);
            font-size: 14px;
            font-weight: 500;
        }

        .stat-card h2 {
            font-size: 32px;
            font-weight: 700;
            margin-top: 4px;
        }

        /* ===== Dashboard Layout ===== */
        .dashboard-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 32px;
        }

        /* ===== Actions Section ===== */
        .actions-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }

        .action-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .action-header h3 {
            font-size: 18px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .action-card p {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 20px;
        }

        .btn-action {
            background-color: var(--primary);
            color: white;
            justify-content: center;
        }

        .btn-action:hover {
            background-color: var(--primary-hover);
        }

        /* ===== Chart Section ===== */
        .chart-card {
            background: var(--bg-card);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            text-align: center;
        }

        .chart-card h3 {
            font-size: 18px;
            margin-bottom: 24px;
        }

        .chart-container {
            position: relative;
            height: 250px;
            margin: 0 auto;
        }

        /* Responsive Mobile */
        @media (max-width: 1024px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px 15px;
            }

            header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                margin-bottom: 30px;
            }

            header h1 {
                font-size: 20px;
            }

            .header-actions {
                width: 100%;
            }

            .btn-logout {
                width: 100%;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                gap: 15px;
                margin-bottom: 30px;
            }

            .stat-card {
                padding: 16px;
            }

            .stat-card h2 {
                font-size: 24px;
            }

            .actions-section {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .chart-container {
                height: 200px;
            }
        }

        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .stat-card {
                display: flex;
                align-items: center;
                gap: 15px;
                text-align: left;
            }

            .stat-card i {
                margin-bottom: 0;
            }

            .stat-card h2 {
                margin-top: 0;
                font-size: 20px;
            }
        }
    </style>
    <link rel="stylesheet" href="../assets/css/mobile-responsive.css">
</head>

<body>

<div class="container">
    <header>
        <h1><i class="fas fa-chart-pie"></i> แผงควบคุมระบบ</h1>
        <div class="header-actions">
            <a class="btn btn-logout" href="../logout.php?logout=1" onclick="return confirm('ออกจากระบบ?')">
                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
            </a>
        </div>
    </header>

    <!-- ===== STATS ===== -->
    <div class="stats-grid">
        <div class="stat-card students">
            <i class="fas fa-user-graduate"></i>
            <p>นักศึกษาทั้งหมด</p>
            <h2><?= number_format($std_all) ?></h2>
        </div>

        <div class="stat-card pending">
            <i class="fas fa-user-clock"></i>
            <p>ยังไม่มีที่ฝึกงาน</p>
            <h2><?= number_format($std_no_intern) ?></h2>
        </div>

        <div class="stat-card approved">
            <i class="fas fa-user-check"></i>
            <p>มีที่ฝึกงานแล้ว</p>
            <h2><?= number_format($std_have_intern) ?></h2>
        </div>

        <div class="stat-card company">
            <i class="fas fa-building"></i>
            <p>สถานประกอบการ</p>
            <h2><?= number_format($company_total) ?></h2>
        </div>
    </div>

    <div class="dashboard-content">
        <!-- ===== MAIN ACTIONS ===== -->
        <div class="actions-section">
            <div class="action-card">
                <div class="action-header">
                    <h3><i class="fas fa-users-cog"></i> จัดการนักศึกษา</h3>
                    <p>จัดการข้อมูลพื้นฐาน เพิ่ม ลบ แก้ไข ข้อมูลนักศึกษาในระบบ</p>
                </div>
                <a href="Student/student_list.php" class="btn btn-action">ดูรายชื่อนักศึกษา</a>
            </div>

            <div class="action-card">
                <div class="action-header">
                    <h3><i class="fas fa-file-signature"></i> คำร้องขอฝึกงาน</h3>
                    <p>พิจารณาอนุมัติหรือปฏิเสธคำร้องขอฝึกงานจากนักศึกษา</p>
                </div>
                <a href="internship_requests.php" class="btn btn-action" style="background:#f43f5e">ตรวจสอบคำร้อง</a>
            </div>

            <div class="action-card">
                <div class="action-header">
                    <h3><i class="fas fa-city"></i> สถานประกอบการ</h3>
                    <p>จัดการข้อมูลและรายชื่อบริษัทที่ร่วมโครงการฝึกงาน</p>
                </div>
                <a href="Com/company_list.php" class="btn btn-action">จัดการข้อมูลบริษัท</a>
            </div>

            <div class="action-card">
                <div class="action-header">
                    <h3><i class="fas fa-bug"></i> รายงานปัญหา</h3>
                    <p>ดูประวัติการแจ้งปัญหาการใช้งานจากนักศึกษาและระบบ</p>
                </div>
                <a href="../admin/report.php" class="btn btn-action" style="background:#64748b">ดูรายงานทั้งหมด</a>
            </div>
        </div>

        <!-- ===== VISUALIZATION ===== -->
        <div class="chart-card">
            <h3>📊 อัตราการฝึกงาน</h3>
            <div class="chart-container">
                <canvas id="internshipChart"></canvas>
            </div>
            <div style="margin-top: 20px; font-size: 14px; color: var(--text-muted);">
                ภาพรวมสถานะนักศึกษา ณ ปัจจุบัน
            </div>
        </div>
    </div>
</div>

<script>
    const ctx = document.getElementById('internshipChart').getContext('2d');
    
    // Custom Plugin for Center Text
    const centerTextPlugin = {
        id: 'centerText',
        afterDraw: (chart) => {
            if (chart.config.type !== 'doughnut') return;
            const { ctx, chartArea: { top, bottom, left, right, width, height } } = chart;
            ctx.save();
            const total = chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
            const haveIntern = chart.data.datasets[0].data[1];
            const percentage = total > 0 ? Math.round((haveIntern / total) * 100) : 0;

            ctx.font = 'bold 30px Inter';
            ctx.fillStyle = '#1E293B';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(`${percentage}%`, left + width / 2, top + height / 2 - 5);
            
            ctx.font = '500 12px Inter';
            ctx.fillStyle = '#64748B';
            ctx.fillText('สำเร็จ', left + width / 2, top + height / 2 + 20);
            ctx.restore();
        }
    };

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['ยังไม่มีที่ฝึกงาน', 'มีที่ฝึกงานแล้ว'],
            datasets: [{
                data: [<?= $std_no_intern ?>, <?= $std_have_intern ?>],
                backgroundColor: [
                    'rgba(239, 68, 68, 0.8)', // Modern Red
                    'rgba(16, 185, 129, 0.8)'  // Modern Green
                ],
                hoverBackgroundColor: [
                    '#EF4444',
                    '#10B981'
                ],
                borderWidth: 0,
                borderRadius: 5,
                spacing: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            family: 'Inter',
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(30, 41, 59, 0.9)',
                    titleFont: { size: 14, family: 'Inter' },
                    bodyFont: { size: 13, family: 'Inter' },
                    padding: 12,
                    cornerRadius: 8,
                    displayColors: false
                }
            },
            animation: {
                animateScale: true,
                animateRotate: true
            }
        },
        plugins: [centerTextPlugin]
    });
</script>

</body>
</html>
