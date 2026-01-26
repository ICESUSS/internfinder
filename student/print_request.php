<?php
session_start();
// Prevent caching
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
               s.std_name, s.std_lastname, s.std_id, s.std_tel, s.std_gmail, s.std_level, s.std_add, s.std_room,
               s.std_add_no, s.std_road, s.std_subdistrict, s.std_district, s.std_province, s.std_zipcode,
               dep.dep_name,
               c.com_name, c.com_tel, c.com_contact, c.com_contact_pos,
               c.com_add_no, c.com_road, c.com_subdistrict, c.com_district, c.com_province, c.com_zipcode,
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

// Parse Level and Year
$level_prefix = "";
$level_year = "";
if (preg_match('/(ปวช\.|ปวส\.)\s*(\d+)/u', $data['std_level'], $matches)) {
    $level_prefix = $matches[1];
    $level_year = $matches[2];
}

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>หนังสือคำร้องขอฝึกอาชีพ/ฝึกงาน - <?= htmlspecialchars($data['std_name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: 'Sarabun', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            background: #f0f0f0;
            margin: 0;
            padding: 0;
        }
        .page {
            width: 210mm;
            height: 297mm;
            padding: 1cm 1.5cm;
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
                padding: 1cm 1.5cm;
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
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 8px;
            margin-top: 0;
        }

        .info-row {
            display: flex;
            margin-bottom: 4px;
            align-items: baseline;
            line-height: 1.6;
        }
        .dotted {
            border-bottom: 1px dotted #000;
            padding: 0 5px;
            flex-grow: 1;
            min-height: 20px;
            display: flex;
            align-items: center;
        }
        .checkbox {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 1px solid #000;
            margin-right: 5px;
            vertical-align: middle;
            text-align: center;
            line-height: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .flex-row {
            display: flex;
            gap: 10px;
            width: 100%;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .footer-table td {
            border: 1px solid #000;
            vertical-align: top;
            padding: 5px;
            font-size: 12px;
            line-height: 1.5;
        }
        
        .signature-box {
            margin-top: 12px;
            margin-left: auto;
            width: 280px;
            text-align: center;
            font-size: 13px;
            line-height: 1.6;
        }

        .indent { padding-left: 1.5cm; }
        .section-title { margin-top: 6px; font-weight: bold; font-size: 13px; line-height: 1.5; }

        /* Print Button Styles */
        .no-print-btn {
            position: fixed;
            top: 20px;
            right: 20px;
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
        .no-print-btn:active {
            transform: translateY(0);
        }
        @media print {
            .no-print-btn { display: none !important; }
            body { background: white; }
            .page { margin: 0; box-shadow: none; }
        }
    </style>
</head>
<body>

<button onclick="window.print()" class="no-print-btn">
    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
    พิมพ์เอกสาร / ดาวน์โหลด PDF
</button>

<div class="page">
    <div class="header-title text-center">หนังสือคำร้องขอฝึกอาชีพ/ฝึกงานในสถานประกอบการ</div>
    <br>
</br>
    
    <div class="text-right" style="margin-bottom: 5px; font-size: 13px; line-height: 1.6;">เขียนที่วิทยาลัยเทคนิคสุพรรณบุรี</div>
    <div class="text-right" style="margin-bottom: 10px; font-size: 13px; line-height: 1.6;">
        วันที่ <span style="display:inline-block; width:20px; border-bottom:1px dotted #000; text-align:center;"><?= $current_day ?></span>
        เดือน <span style="display:inline-block; width:80px; border-bottom:1px dotted #000; text-align:center;"><?= $current_month ?></span>
        พ.ศ. <span style="display:inline-block; width:45px; border-bottom:1px dotted #000; text-align:center;"><?= $current_year ?></span>
    </div>

    <div class="info-row">
        <span class="bold">เรื่อง</span>&nbsp;ขอฝึกอาชีพ หรือฝึกงานในสถานประกอบการ
    </div>
    <div class="info-row" style="margin-bottom: 10px;">
        <span class="bold">เรียน</span>&nbsp;ผู้อำนวยการวิทยาลัยเทคนิคสุพรรณบุรี
    </div>

    <div class="info-row indent">
        <span>ข้าพเจ้านาย/นางสาว</span>
        <div class="dotted"><?= htmlspecialchars($data['std_name'] . ' ' . $data['std_lastname']) ?></div>
        <span>รหัสประจำตัว</span>
        <div class="dotted" style="flex-grow:0; width: 140px;"><?= htmlspecialchars($data['std_id']) ?></div>
    </div>

    <div class="info-row">
        <span>นักศึกษาระดับ</span>&nbsp;
        <span class="checkbox"><?= $level_prefix == 'ปวช.' ? '✓' : '' ?></span> ปวช.&nbsp;
        <span class="checkbox"><?= $level_prefix == 'ปวส.' ? '✓' : '' ?></span> ปวส.&nbsp;
        <span>ปีที่</span>
        <div class="dotted" style="flex-grow:0; width: 30px; text-align:center; justify-content:center;"><?= $level_year ?></div>
        <span>กลุ่ม</span>
        <div class="dotted" style="flex-grow:0; width: 60px; text-align:center; justify-content:center;"><?= htmlspecialchars($data['std_room']) ?></div>
        <span>สาขางาน</span>
        <div class="dotted"><?= htmlspecialchars($data['dep_name']) ?></div>
    </div>

    <div class="info-row">
        <span>เบอร์โทรศัพท์นักศึกษา</span>
        <div class="dotted"><?= htmlspecialchars($data['std_tel']) ?></div>
        <span>เบอร์โทรศัพท์ผู้ปกครอง</span>
        <div class="dotted" style="flex-grow:0; width: 120px;"><?= htmlspecialchars($data['parent_tel'] ?? '') ?></div>
    </div>

    <div class="info-row">
        <span>มีผลการเรียนเฉลี่ยสะสม</span>
        <div class="dotted" style="flex-grow:0; width: 100px;"><?= htmlspecialchars($data['gpax'] ?? '') ?></div>
        <span>มีความประสงค์ขอฝึกอาชีพ/ฝึกงาน ภาคเรียนที่</span>
        <div class="dotted" style="flex-grow:0; width: 150px; text-align:center; justify-content:center;"><?= htmlspecialchars($data['term'] ?? '') ?></div>
    </div>

    <div class="info-row">
        <span>ระหว่างวันที่</span>
        <div class="dotted"><?= !empty($data['start_date']) ? date('d/m/', strtotime($data['start_date'])) . (date('Y', strtotime($data['start_date'])) + 543) : '' ?></div>
        <span>ถึงวันที่</span>
        <div class="dotted"><?= !empty($data['end_date']) ? date('d/m/', strtotime($data['end_date'])) . (date('Y', strtotime($data['end_date'])) + 543) : '' ?></div>
    </div>

    <div style="margin-top: 8px;">
        <div class="info-row">
            <span class="checkbox"><?= ($data['request_type'] == '1') ? '✓' : '' ?></span>
            <span class="bold">1. หาสถานที่ฝึกอาชีพฝึกอาชีพ/ฝึกงานเอง ที่ (ชื่อสถานประกอบการ)</span>
        </div>
        <div class="dotted" style="margin-left: 20px; margin-bottom: 6px;"><?= ($data['request_type'] == '1') ? htmlspecialchars($data['com_name']) : '' ?></div>
        
        <div style="margin-left: 20px;">
            <div class="info-row">
                <span>ตำแหน่งที่ติดต่อ</span>
                <div class="dotted"><?= ($data['request_type'] == '1') ? htmlspecialchars($data['com_contact_pos'] ?? '') : '' ?></div>
                <span>เลขที่</span>
                <div class="dotted" style="flex-grow:0; width: 60px;"><?= ($data['request_type'] == '1') ? htmlspecialchars($data['com_add_no'] ?? '') : '' ?></div>
                <span>ถนน</span>
                <div class="dotted" style="flex-grow:0; width: 100px;"><?= ($data['request_type'] == '1') ? htmlspecialchars($data['com_road'] ?? '') : '' ?></div>
            </div>
            <div class="info-row">
                <span>ตำบล</span>
                <div class="dotted"><?= ($data['request_type'] == '1') ? htmlspecialchars($data['com_subdistrict'] ?? '') : '' ?></div>
                <span>อำเภอ</span>
                <div class="dotted"><?= ($data['request_type'] == '1') ? htmlspecialchars($data['com_district'] ?? '') : '' ?></div>
                <span>จังหวัด</span>
                <div class="dotted"><?= ($data['request_type'] == '1') ? htmlspecialchars($data['com_province'] ?? '') : '' ?></div>
            </div>
            <div class="info-row">
                <span>รหัสไปรษณีย์</span>
                <div class="dotted" style="flex-grow:0; width: 80px;"><?= ($data['request_type'] == '1') ? htmlspecialchars($data['com_zipcode'] ?? '') : '' ?></div>
                <span>โทร</span>
                <div class="dotted"><?= ($data['request_type'] == '1') ? htmlspecialchars($data['com_tel']) : '' ?></div>
            </div>
        </div>
    </div>

    <div style="margin-top: 8px;">
        <div class="info-row">
            <span class="checkbox"><?= ($data['request_type'] == '2' || empty($data['request_type'])) ? '✓' : '' ?></span>
            <span class="bold">2. ให้วิทยาลัยหาสถานที่ฝึกอาชีพ/ฝึกงานให้ (ชื่อสถานประกอบการ)</span>
        </div>
        <div class="dotted" style="margin-left: 20px; margin-bottom: 6px;"><?= ($data['request_type'] == '2' || empty($data['request_type'])) ? htmlspecialchars($data['com_name']) : '' ?></div>
        
        <div style="margin-left: 20px;">
            <div class="info-row">
                <span>เลขที่</span>
                <div class="dotted" style="flex-grow:0; width: 60px;"><?= ($data['request_type'] == '2' || empty($data['request_type'])) ? htmlspecialchars($data['com_add_no'] ?? '') : '' ?></div>
                <span>ถนน</span>
                <div class="dotted"><?= ($data['request_type'] == '2' || empty($data['request_type'])) ? htmlspecialchars($data['com_road'] ?? '') : '' ?></div>
                <span>ตำบล</span>
                <div class="dotted"><?= ($data['request_type'] == '2' || empty($data['request_type'])) ? htmlspecialchars($data['com_subdistrict'] ?? '') : '' ?></div>
                <span>อำเภอ</span>
                <div class="dotted"><?= ($data['request_type'] == '2' || empty($data['request_type'])) ? htmlspecialchars($data['com_district'] ?? '') : '' ?></div>
            </div>
            <div class="info-row">
                <span>จังหวัด</span>
                <div class="dotted"><?= ($data['request_type'] == '2' || empty($data['request_type'])) ? htmlspecialchars($data['com_province'] ?? '') : '' ?></div>
                <span>รหัสไปรษณีย์</span>
                <div class="dotted" style="flex-grow:0; width: 80px;"><?= ($data['request_type'] == '2' || empty($data['request_type'])) ? htmlspecialchars($data['com_zipcode'] ?? '') : '' ?></div>
                <span>โทร</span>
                <div class="dotted"><?= ($data['request_type'] == '2' || empty($data['request_type'])) ? htmlspecialchars($data['com_tel']) : '' ?></div>
            </div>
        </div>
    </div>
<br>
</br>
    
    <div class="text-center" style="margin-top: 12px; font-weight: bold; font-size: 14px; line-height: 1.6;">จึงเรียนมาเพื่อโปรดทราบ</div>

    <div class="signature-box">
        <div>ลงชื่อ..................................................................</div>
        <div style="margin-top: 5px;">( <?= htmlspecialchars($data['std_name'] . ' ' . $data['std_lastname']) ?> )</div>
        <div style="margin-top: 5px;">นักเรียน นักศึกษา</div>
    </div>

    <table class="footer-table">
        <tr>
            <td width="55%">
                <div class="section-title">ความเห็นของครูที่ปรึกษา</div>
                <div style="margin-top: 2px; font-size: 11px; line-height: 1.5;"><span class="checkbox"></span> อนุญาต <span class="checkbox"></span> ได้ตรวจสอบคุณสมบัติสถานประกอบการแล้ว</div>
                <div style="margin-top: 8px; font-size: 11px; line-height: 1.5;">ลงชื่อ..................................................................</div>
                <div class="text-center" style="font-size: 11px; line-height: 1.5;">(..................................................................)</div>
                
                <div class="section-title">ความเห็นของทวิภาคีแผนก</div>
                <div style="margin-top: 2px; font-size: 11px; line-height: 1.5;"><span class="checkbox"></span> อนุญาต <span class="checkbox"></span> ได้ตรวจสอบคุณสมบัติสถานประกอบการแล้ว</div>
                <div style="margin-top: 8px; font-size: 11px; line-height: 1.5;">ลงชื่อ..................................................................</div>
                <div class="text-center" style="font-size: 11px; line-height: 1.5;">(..................................................................)</div>

                <div class="section-title">ความเห็นของหัวหน้าแผนกวิชา <span class="dotted" style="display:inline-block; width:100px;"></span></div>
                <div style="margin-top: 2px; font-size: 11px; line-height: 1.5;"><span class="checkbox"></span> อนุญาต <span class="checkbox"></span> ได้ตรวจสอบคุณสมบัติสถานประกอบการแล้ว</div>
                <div style="margin-top: 8px; font-size: 11px; line-height: 1.5;">ลงชื่อ..................................................................</div>
                <div class="text-center" style="font-size: 11px; line-height: 1.5;">(..................................................................)</div>
            </td>
            <td width="45%">
                <div class="section-title">หัวหน้างานอาชีวศึกษาระบบทวิภาคี</div>
                <div style="margin-top: 5px; border-bottom: 1px dotted #000; height: 16px;"></div>
                <div style="margin-top: 2px; border-bottom: 1px dotted #000; height: 16px;"></div>
                <div style="margin-top: 8px; text-align: center; font-size: 11px; line-height: 1.5;">
                    ลงชื่อ..........................................................<br>
                    (นายสุรเชษฐ์ ขาวโต)
                </div>

                <div class="section-title" style="margin-top: 10px;">รองผู้อำนวยการฝ่ายวิชาการ</div>
                <div style="margin-top: 5px; border-bottom: 1px dotted #000; height: 16px;"></div>
                <div style="margin-top: 2px; border-bottom: 1px dotted #000; height: 16px;"></div>
                <div style="margin-top: 8px; text-align: center; font-size: 11px; line-height: 1.5;">
                    ลงชื่อ..........................................................<br>
                    (นายสุธีร์ แบนประเสริฐ)
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
