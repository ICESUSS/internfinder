<?php
session_start();
include('config.php'); // ไฟล์เชื่อมต่อฐานข้อมูล

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];
    
    if ($user_type == 'student') {
        // ตรวจสอบนักศึกษา
        $sql = "SELECT * FROM tb_student WHERE std_id = ? AND std_password = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $_SESSION['user_id'] = $row['std_id'];
            $_SESSION['user_name'] = $row['std_name'];
            $_SESSION['user_type'] = 'student';
            header("Location: student/dashboard.php");
            exit();
        } else {
            $error = "รหัสนักศึกษาหรือรหัสผ่านไม่ถูกต้อง";
        }
    } else if ($user_type == 'admin') {
        // ตรวจสอบผู้ดูแลระบบ
        $sql = "SELECT * FROM tb_admin WHERE ad_id = ? AND ad_password = ?";
        $stmt = $conn->prepare($sql);
        // หมายเหตุ: ถ้า ad_id ในฐานข้อมูลเป็น int ให้ใช้ "is" ถ้าเป็น string ให้ใช้ "ss"
        $stmt->bind_param("is", $username, $password); 
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $_SESSION['user_id'] = $row['ad_id'];
            $_SESSION['user_type'] = 'admin';
            header("Location: admin/dashboard.php");
            exit();
        } else {
            $error = "รหัสผู้ดูแลระบบหรือรหัสผ่านไม่ถูกต้อง";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - InternFinder</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <link rel="stylesheet" href="assets/css/mobile-responsive.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🎓 InternFinder</h1>
            <p>ระบบจัดการฝึกงาน</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="error-message">
                    ⚠️ <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="user-type-selector">
                    <div class="user-type-option">
                        <input type="radio" id="student" name="user_type" value="student" checked>
                        <label for="student">👨‍🎓 นักศึกษา</label>
                    </div>
                    <div class="user-type-option">
                        <input type="radio" id="admin" name="user_type" value="admin">
                        <label for="admin">👨‍💼 ผู้ดูแลระบบ</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="username">
                        <span id="username-label">รหัสนักศึกษา</span>
                    </label>
                    <input type="text" id="username" name="username" required 
                           placeholder="กรอกรหัสนักศึกษา">
                </div>
                
                <div class="form-group">
                    <label for="password">รหัสผ่าน</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="กรอกรหัสผ่าน">
                </div>
                
                <button type="submit" class="btn-login">เข้าสู่ระบบ</button>
            </form>
        </div>
    </div>
    
    <script>
        // เปลี่ยน label เมื่อเลือกประเภทผู้ใช้
        const studentRadio = document.getElementById('student');
        const adminRadio = document.getElementById('admin');
        const usernameLabel = document.getElementById('username-label');
        const usernameInput = document.getElementById('username');
        
        studentRadio.addEventListener('change', function() {
            usernameLabel.textContent = 'รหัสนักศึกษา';
            usernameInput.placeholder = 'กรอกรหัสนักศึกษา';
        });
        
        adminRadio.addEventListener('change', function() {
            usernameLabel.textContent = 'รหัสผู้ดูแลระบบ';
            usernameInput.placeholder = 'กรอกรหัสผู้ดูแลระบบ';
        });
    </script>
</body>
</html>