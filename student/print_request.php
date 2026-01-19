<?php
session_start();
// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

include '../config.php';
// ... existing code ...

if (!isset($_GET['id'])) {
    die("ไม่พบรหัสใบคำร้อง");
}

$intern_id = $_GET['id'];

// Fetch detailed information
$sql = "SELECT i.*, 
               s.std_name, s.std_lastname, s.std_id, s.std_tel, s.std_gmail, s.std_level, s.std_add,
               dep.dep_name,
               c.com_name, c.com_address, c.com_tel,
               d.job_title
        FROM tb_internship i
        JOIN tb_student s ON i.std_id = s.std_id
        LEFT JOIN tb_department dep ON s.dep_id = dep.dep_id
        JOIN tb_company c ON i.com_id = c.com_id
        LEFT JOIN tb_company_detail d ON i.detail_id = d.detail_id
        WHERE i.intern_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $intern_id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    die("ไม่พบข้อมูล");
}

// Date Setup
$current_day = date('j');
$thai_months = [
    1 => "มกราคม", 2 => "กุมภาพันธ์", 3 => "มีนาคม", 4 => "เมษายน", 
    5 => "พฤษภาคม", 6 => "มิถุนายน", 7 => "กรกฎาคม", 8 => "สิงหาคม", 
    9 => "กันยายน", 10 => "ตุลาคม", 11 => "พฤศจิกายน", 12 => "ธันวาคม"
];
$current_month = $thai_months[(int)date('n')];
$current_year = date('Y') + 543;

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>แบบคำร้องขอฝึกงาน</title>
    <!-- TH Sarabun New via Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Typography - TH Sarabun New with Fallback */
        @import url('https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap');
        
        @page {
            size: A4;
            margin: 0;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            font-size: 16px; /* Approx 12pt */
            line-height: 1.5;
            background: #eef2f7;
            margin: 0;
            padding: 30px 0;
            display: flex;
            justify-content: center;
        }

        .page {
            width: 210mm;
            min-height: 297mm;
            background: white;
            padding: 2.5cm 2cm 2.5cm 3cm; /* Standard Thai Official Margins: Top 2.5, Right 2, Bottom 2.5, Left 3 */
            box-sizing: border-box;
            position: relative;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            color: #000;
        }

        @media print {
            body { 
                background: none; 
                padding: 0;
            }
            .page { 
                width: 210mm; 
                height: 297mm; 
                margin: 0; 
                padding: 2.5cm 2cm 2.5cm 3cm; 
                box-shadow: none;
            }
            .no-print { display: none !important; }
        }

        /* Elements Styling */
        h1 {
            font-size: 24px; /* Approx 18pt */
            font-weight: bold;
            text-align: center;
            margin: 0 0 20px 0;
            text-decoration: underline;
        }

        .line {
            display: flex;
            align-items: baseline;
            margin-bottom: 8px;
            width: 100%;
        }

        .line-right { justify-content: flex-end; }
        
        .label {
            white-space: nowrap;
            margin-right: 5px;
        }

        .dot-line {
            border-bottom: 1px dotted #444;
            flex-grow: 1;
            text-align: center;
            min-height: 1.5em;
            margin: 0 4px;
            padding-bottom: 2px;
            color: #000;
        }

        .dot-line.fixed { flex-grow: 0; }
        .w-30 { width: 30px; }
        .w-50 { width: 50px; }
        .w-80 { width: 80px; }
        .w-100 { width: 100px; }
        .w-150 { width: 150px; }
        .w-200 { width: 200px; }
        .w-250 { width: 250px; }

        .bold { font-weight: bold; }
        .underline { text-decoration: underline; }
        .indent-1 { padding-left: 1.5cm; }
        .indent-2 { padding-left: 2.5cm; }

        /* Checkbox Style */
        .chk-container {
            display: inline-flex;
            align-items: center;
            margin-right: 15px;
        }
        .chk-box {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1px solid #000;
            margin-right: 5px;
            text-align: center;
            line-height: 12px;
            font-size: 14px;
            font-weight: bold;
        }

        /* Footer Box */
        .footer-container {
            margin-top: 30px;
            border: 1px solid #000;
            display: flex;
        }
        .footer-col {
            flex: 1;
            padding: 15px;
            border-right: 1px solid #000;
        }
        .footer-col:last-child { border-right: none; }

        /* Helpers */
        .mt-1 { margin-top: 10px; }
        .mt-2 { margin-top: 20px; }
        .mt-3 { margin-top: 30px; }
        .text-center { text-align: center; }
    </style>
</head>
<body>

<div class="no-print" style="position:fixed; top:10px; right:10px; z-index:100; background: #333; padding: 10px; border-radius: 5px;">
    <span style="color:white; margin-right:10px;">Ver: <?= date('H:i:s') ?></span>
    <button onclick="window.print()" style="padding:5px 15px; cursor:pointer;">พิมพ์ / บันทึก PDF</button>
</div>

<div class="page">
    <h1>แบบคำร้องขอฝึกงาน</h1>

    <div class="line line-right">
        <span class="label">วันที่</span>
        <span class="dot-line fixed w-30"><?= $current_day ?></span>
        <span class="label">เดือน</span>
        <span class="dot-line fixed w-100"><?= $current_month ?></span>
        <span class="label">พ.ศ.</span>
        <span class="dot-line fixed w-50"><?= $current_year ?></span>
    </div>

    <div class="line mt-2">
        <span class="label">เรียน</span>
        <span>ผู้อำนวยการวิทยาลัย............................................................</span>
    </div>

    <div class="line indent-1 mt-1">
        <span class="label">ข้าพเจ้า (นาย/นางสาว)</span>
        <span class="dot-line"><?= htmlspecialchars($data['std_name'] . ' ' . $data['std_lastname']) ?></span>
        <span class="label">รหัสนักศึกษา</span>
        <span class="dot-line fixed w-150"><?= htmlspecialchars($data['std_id']) ?></span>
    </div>

    <div class="line indent-1">
        <span class="label">ระดับชั้น</span>
        <span class="dot-line fixed w-100"><?= htmlspecialchars($data['std_level'] ?? '...........') ?></span>
        <span class="label">สาขาวิชา</span>
        <span class="dot-line"><?= htmlspecialchars($data['dep_name'] ?? '......................') ?></span>
    </div>

    <div class="line indent-1">
        <span class="label">ที่อยู่ปัจจุบัน</span>
        <span class="dot-line"><?= htmlspecialchars($data['std_add'] ?? '..................................................................') ?></span>
    </div>

    <div class="line indent-1">
        <span class="label">เบอร์โทรศัพท์</span>
        <span class="dot-line fixed w-200"><?= htmlspecialchars($data['std_tel']) ?></span>
        <span class="label">E-mail :</span>
        <span class="dot-line"><?= htmlspecialchars($data['std_gmail']) ?></span>
    </div>

    <div class="line mt-2 bold">
        มีความประสงค์ขอฝึกงาน / ฝึกประสบการณ์วิชาชีพ ดังนี้
    </div>

    <div class="line indent-1 mt-1">
        <span class="label">ชื่อสถานประกอบการ</span>
        <span class="dot-line"><?= htmlspecialchars($data['com_name']) ?></span>
    </div>

    <div class="line indent-1">
        <span class="label">ที่อยู่เลขที่</span>
        <span class="dot-line"><?= htmlspecialchars($data['com_address']) ?></span>
    </div>

    <div class="line indent-1">
        <span class="label">โทรศัพท์</span>
        <span class="dot-line fixed w-150"><?= htmlspecialchars($data['com_tel'] ?? '......................') ?></span>
        <span class="label">ตำแหน่งที่จะเข้าฝึกงาน</span>
        <span class="dot-line"><?= htmlspecialchars($data['job_title'] ?? '......................') ?></span>
    </div>

    <div class="line indent-1 mt-2">
        <span class="chk-container">
            <span class="chk-box"><?= ($data['status'] == 'approved' ? '✓' : '') ?></span> 
            วิทยาลัยฯ ติดต่อประสานงานให้
        </span>
        <span class="chk-container">
            <span class="chk-box">✓</span> 
            นักศึกษาติดต่อประสานงานเอง
        </span>
    </div>

    <div class="line mt-2">
        <span class="label bold">ระยะเวลาฝึกงาน :</span>
        <span class="label">ตั้งแต่วันที่</span>
        <span class="dot-line fixed w-150">..................................</span>
        <span class="label">ถึงวันที่</span>
        <span class="dot-line fixed w-150">..................................</span>
    </div>

    <div class="line indent-1 mt-3">
        จึงเรียนมาเพื่อโปรดตรวจสอบคุณสมบัติและพิจารณาดำเนินการต่อไป
    </div>

    <!-- Signature Section -->
    <div style="margin-top: 40px; display: flex; justify-content: flex-end;">
        <div style="text-align: center; width: 300px;">
            <div class="line">
                <span class="label">ลงชื่อ</span>
                <span class="dot-line"></span>
                <span class="label">นักศึกษา</span>
            </div>
            <div class="mt-1">( <?= htmlspecialchars($data['std_name'] . ' ' . $data['std_lastname']) ?> )</div>
            <div class="mt-1">....... / ....... / .......</div>
        </div>
    </div>

    <!-- Approval Boxes -->
    <div class="footer-container mt-3">
        <div class="footer-col">
            <div class="text-center bold underline">ความเห็นของอาจารย์ที่ปรึกษา</div>
            <div class="mt-2">
                <span class="chk-container"><span class="chk-box"></span> เห็นควรอนุมัติ</span>
            </div>
            <div class="mt-1">
                <span class="chk-container"><span class="chk-box"></span> อื่นๆ .............................</span>
            </div>
            <div class="mt-3 text-center">
                ลงชื่อ ..........................................<br>
                (..........................................)
            </div>
        </div>
        <div class="footer-col">
            <div class="text-center bold underline">ผลการพิจารณาของงานฝึกงาน</div>
            <div class="mt-2">
                <span class="chk-container"><span class="chk-box"></span> อนุมัติ</span>
                <span class="chk-container"><span class="chk-box"></span> ไม่อนุมัติ</span>
            </div>
            <div class="mt-1">
                หมายเหตุ ....................................
            </div>
            <div class="mt-3 text-center">
                ลงชื่อ ..........................................<br>
                (..........................................)
            </div>
        </div>
    </div>
</div>

</body>
</html>
