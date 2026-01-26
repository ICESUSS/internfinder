<?php
session_start();
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

include '../config.php';

if (!isset($_GET['id'])) {
    die("ไม่พบรหัสใบคำร้อง");
}

$intern_id = $_GET['id'];

// Fetch detailed information
$sql = "SELECT i.*, 
               s.std_name, s.std_lastname, s.std_id, s.std_level, s.dep_id,
               dep.dep_name
        FROM tb_internship i
        JOIN tb_student s ON i.std_id = s.std_id
        LEFT JOIN tb_department dep ON s.dep_id = dep.dep_id
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
    <title>หนังสืออนุญาตจากผู้ปกครอง - <?= htmlspecialchars($data['std_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: 'Sarabun', sans-serif;
            font-size: 14px; /* Reduced from 16px to ensure fit */
            line-height: 1.4; /* Tighter line height */
            background: #f0f0f0;
            margin: 0;
            padding: 0;
        }
        .page {
            width: 210mm;
            height: 297mm;
            padding: 1.5cm 2cm 1.5cm 2.5cm; /* Reduced padding: Top/Bottom 1.5cm */
            margin: 0 auto;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            box-sizing: border-box;
            overflow: hidden;
        }
        @media print {
            body { 
                background: white; 
                padding: 0; 
                margin: 0;
            }
            .page { 
                box-shadow: none; 
                margin: 0; 
                padding: 1.5cm 2cm 1.5cm 2.5cm;
                width: 210mm;
                height: 297mm;
            }
            .no-print { display: none; }
            @page {
                margin: 0;
            }
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        
        .header-title {
            font-size: 18px; /* Reduced from 20px */
            font-weight: bold;
            margin-bottom: 12px;
            margin-top: 0;
            text-align: center;
        }

        .info-row {
            display: flex;
            margin-bottom: 2px; /* Reduced from 4px */
            align-items: baseline;
            line-height: 1.5;
            flex-wrap: wrap; 
        }
        .dotted {
            border-bottom: 1px dotted #000;
            padding: 0 5px;
            flex-grow: 1;
            min-height: 18px; /* Reduced height */
            display: flex;
            align-items: center;
            justify-content: center; 
        }
        .dotted.left-align {
            justify-content: flex-start;
        }
        
        .indent { padding-left: 1.5cm; }
        
        .signature-section {
            margin-top: 20px; /* Reduced spacing */
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px; /* Reduced gap */
        }
        
        .signature-block {
            text-align: center;
            width: 350px;
        }

        /* Print Button Styles */
        .no-print-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: #2563eb;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
            z-index: 1000;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Sarabun', sans-serif;
            transition: all 0.2s ease;
            font-size: 16px;
        }
        .no-print-btn:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.4);
        }
        @media print {
            .no-print-btn { display: none !important; }
        }
    </style>
</head>
<body>

<button onclick="window.print()" class="no-print-btn">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
    พิมพ์เอกสาร / ดาวน์โหลด PDF
</button>

<div class="page">
    <div class="header-title">หนังสืออนุญาตจากผู้ปกครอง</div>
    <br></br>

    <div class="info-row" style="justify-content: flex-end;">
        <span>เขียนที่</span>
        <div class="dotted" style="flex-grow: 0; width: 250px;">วิทยาลัยเทคนิคสุพรรณบุรี</div>
    </div>
    
    <div class="info-row" style="justify-content: flex-end;">
        <span>วันที่</span>
        <div class="dotted" style="flex-grow: 0; width: 50px;"><?= $current_day ?></div>
        <span>เดือน</span>
        <div class="dotted" style="flex-grow: 0; width: 100px;"><?= $current_month ?></div>
        <span>พ.ศ.</span>
        <div class="dotted" style="flex-grow: 0; width: 60px;"><?= $current_year ?></div>
    </div>

    <div class="info-row" style="margin-top: 20px;">
        <span class="bold">เรื่อง</span>&nbsp;อนุญาตให้ นักเรียน / นักศึกษา ฝึกอาชีพ/ฝึกงาน ในสถานประกอบการ
    </div>
    <div class="info-row">
        <span class="bold">เรียน</span>&nbsp;ผู้อำนวยการวิทยาลัยเทคนิคสุพรรณบุรี
    </div>

    <div class="info-row indent" style="margin-top: 20px;">
        <span>ข้าพเจ้า</span>
         <div class="dotted" style="width: 150px; flex-grow: 0;"><?= htmlspecialchars($data['parent_name'] ?? '') ?></div>
        <span>บัตรประจำตัวประชาชนเลขที่</span>
        <div class="dotted" style="width: 140px; flex-grow: 0;"><?= htmlspecialchars($data['parent_id_card'] ?? '') ?></div>
    </div>
    
    <div class="info-row">
        <span>สถานที่ที่สามารถติดต่อได้</span>
        <div class="dotted left-align" style="flex-grow: 1;"><?= htmlspecialchars($data['parent_address'] ?? '') ?></div>
    </div>
    
    <div class="info-row">
        <span>เกี่ยวข้องเป็น</span>
        <div class="dotted" style="width: 120px; flex-grow: 0;"><?= htmlspecialchars($data['parent_relation'] ?? '') ?></div>
        <span>ของ นาย/นางสาว</span>
        <div class="dotted" style="width: 200px;"><?= htmlspecialchars($data['std_name'] . ' ' . $data['std_lastname']) ?></div>
        <span style="margin-left: auto;">ซึ่งเป็น</span>
    </div>
    
    <div class="info-row">
        <span>นักเรียน/นักศึกษา ระดับชั้น</span>
        <div class="dotted" style="width: 80px; flex-grow: 0;"><?= htmlspecialchars($data['std_level']) ?></div>
        <span>แผนกวิชา</span>
        <div class="dotted" style="width: 200px; flex-grow: 0;"><?= htmlspecialchars($data['dep_name']) ?></div>
        <span>ซึ่งอยู่ในความปกครองของข้าพเจ้า</span>
    </div>
    <div class="info-row">
        <span>โดยอนุญาตให้นักเรียน / นักศึกษา ฝึกอาชีพหรือฝึกงาน ตาม วัน เวลา และสถานที่ ตามที่วิทยาลัยกำหนด</span>
    </div>

    <div class="info-row indent" style="margin-top: 20px;">
        <span>ถ้าหาก นาย/นางสาว</span>
        <div class="dotted" style="width: 200px;"><?= htmlspecialchars($data['std_name'] . ' ' . $data['std_lastname']) ?></div>
        <span>ได้รับอุบัติเหตุหรืออันตรายใดๆ เนื่องจาก</span>
    </div>
    <div class="info-row">
        <span>การฝึกอาชีพหรือฝึกงาน ซึ่งอาจเกิดขึ้นเพราะเหตุสุดวิสัย หรือความประมาทเลินเล่อ เกิดจากเครื่องมือเครื่องใช้และสิ่งแวดล้อมใด ๆ</span>
    </div>
    <div class="info-row">
        <span>ในสถานที่ฝึกอาชีพหรือฝึกงาน อื่นๆ ทั้งนี้ไม่ว่าจะเป็นการฝึกอาชีพ หรือฝึกงานไม่ว่าจะเป็นในหรือนอกสถานที่ ข้าพเจ้าจะไม่ร้อง</span>
    </div>
    <div class="info-row">
        <span>โดยอาศัยบทบัญญัติของกฎหมายนั้น ๆ ด้วย</span>
    </div>

    <div class="info-row indent" style="margin-top: 20px;">
        <span>ข้าพเจ้ายินยอมชดใช้ค่าเสียหาย ในกรณี นาย/นางสาว</span>
        <div class="dotted" style="width: 200px;"><?= htmlspecialchars($data['std_name'] . ' ' . $data['std_lastname']) ?></div>
        <span>ได้ทำให้เกิดความเสียหายขึ้น</span>
    </div>
    <div class="info-row">
        <span>แก่ทรัพย์สินที่ใช้ในการฝึกอาชีพหรือฝึกงาน โดยพละการไม่ว่าจะเป็นของสถานที่ฝึกอาชีพหรือฝึกงาน หรือของวิทยาลัย</span>
    </div>

    <div class="info-row indent" style="margin-top: 20px;">
        <span>ถ้าหากนาย/นางสาว</span>
        <div class="dotted" style="width: 200px;"><?= htmlspecialchars($data['std_name'] . ' ' . $data['std_lastname']) ?></div>
        <span>ฝ่าฝืนกฎระเบียบข้อบังคับของสถานที่ฝึกอาชีพ หรือฝึกงาน</span>
    </div>
    <div class="info-row">
        <span>ถือว่าเป็นความผิดอย่างร้ายแรง ตามระเบียบข้อบังคับของสถานฝึกอาชีพหรือฝึกงาน หรือของวิทยาลัย ข้าพเจ้าขอยินยอมให้วิทยาลัย</span>
    </div>
    <div class="info-row">
        <span>คัดชื่อนาย/นางสาว</span>
         <div class="dotted" style="width: 200px;"><?= htmlspecialchars($data['std_name'] . ' ' . $data['std_lastname']) ?></div>
        <span>ออกจากการเป็น นักเรียน/นักศึกษา ของวิทยาลัย</span>
    </div>
    <div class="info-row">
        <span>โดยไม่มีข้อโต้แย้งใด ๆ ทั้งสิ้น</span>
    </div>

    <div class="signature-section">
        <div class="signature-block">
            <div class="info-row" style="justify-content: center;">
                <span>ลงชื่อ</span>
                <div class="dotted" style="flex-grow: 0; width: 200px;"></div>
                <span>ผู้ให้ความยินยอม</span>
            </div>
            <div class="info-row" style="justify-content: center; margin-top: 5px;">
                ( <div class="dotted" style="flex-grow: 0; width: 200px;"><?= htmlspecialchars($data['parent_name'] ?? '') ?></div> )
            </div>
        </div>
        
        <div class="signature-block">
            <div class="info-row" style="justify-content: center;">
                <span>ลงชื่อ</span>
                <div class="dotted" style="flex-grow: 0; width: 200px;"></div>
                <span>พยาน</span>
            </div>
            <div class="info-row" style="justify-content: center; margin-top: 5px;">
                ( <div class="dotted" style="flex-grow: 0; width: 150px;">&nbsp;</div> )
            </div>
        </div>

        <div class="signature-block">
            <div class="info-row" style="justify-content: center;">
                <span>ลงชื่อ</span>
                <div class="dotted" style="flex-grow: 0; width: 200px;"></div>
                <span>พยาน</span>
            </div>
            <div class="info-row" style="justify-content: center; margin-top: 5px;">
                ( <div class="dotted" style="flex-grow: 0; width: 150px;">&nbsp;</div> )
            </div>
        </div>
    </div>
</div>

</body>
</html>
