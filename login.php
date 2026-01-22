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
        $sql = "SELECT * FROM tb_student WHERE std_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // ตรวจสอบรหัสผ่าน - ใช้ password_verify() เท่านั้น
            $password_valid = false;
            
            // ถ้ารหัสผ่านเป็น hashed (เริ่มต้นด้วย $2y$ หรือ $2a$ หรือ $2b$)
            if (preg_match('/^\$2[ayb]\$/', $row['std_password'])) {
                $password_valid = password_verify($password, $row['std_password']);
                // ถ้า verify สำเร็จและเป็น plain text เก่า ให้ hash ใหม่
                if ($password_valid) {
                    // Optional: Upgrade old plain text passwords to hashed
                    // Uncomment if you want to auto-upgrade
                    // $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    // $update_stmt = $conn->prepare("UPDATE tb_student SET std_password = ? WHERE std_id = ?");
                    // $update_stmt->bind_param("ss", $new_hash, $row['std_id']);
                    // $update_stmt->execute();
                }
            } else {
                // ถ้ายังเป็น plain text (ไม่ควรใช้ แต่รองรับเพื่อ migration)
                // เปรียบเทียบและ hash ใหม่ทันที
                if ($password === $row['std_password']) {
                    $password_valid = true;
                    // Hash รหัสผ่านใหม่ทันที
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE tb_student SET std_password = ? WHERE std_id = ?");
                    $update_stmt->bind_param("ss", $new_hash, $row['std_id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }
            
            if ($password_valid) {
                $_SESSION['user_id'] = $row['std_id'];
                $_SESSION['user_name'] = $row['std_name'] . ' ' . $row['std_lastname'];
                $_SESSION['user_type'] = 'student';
                header("Location: student/index.php");
                exit();
            } else {
                $error = "รหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $error = "ไม่พบรหัสนักศึกษานี้";
        }
    } else if ($user_type == 'admin') {
        // ตรวจสอบผู้ดูแลระบบ
        $sql = "SELECT * FROM tb_admin WHERE ad_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username); 
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            // ตรวจสอบรหัสผ่าน - ใช้ password_verify() เท่านั้น
            $password_valid = false;
            
            // ถ้ารหัสผ่านเป็น hashed (เริ่มต้นด้วย $2y$ หรือ $2a$ หรือ $2b$)
            if (preg_match('/^\$2[ayb]\$/', $row['ad_password'])) {
                $password_valid = password_verify($password, $row['ad_password']);
            } else {
                // ถ้ายังเป็น plain text (ไม่ควรใช้ แต่รองรับเพื่อ migration)
                // เปรียบเทียบและ hash ใหม่ทันที
                if ($password === $row['ad_password']) {
                    $password_valid = true;
                    // Hash รหัสผ่านใหม่ทันที
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE tb_admin SET ad_password = ? WHERE ad_id = ?");
                    $update_stmt->bind_param("ss", $new_hash, $row['ad_id']);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
            }
            
            if ($password_valid) {
                $_SESSION['user_id'] = $row['ad_id'];
                $_SESSION['user_type'] = 'admin';
                header("Location: admin/index.php");
                exit();
            } else {
                $error = "รหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $error = "ไม่พบรหัสผู้ดูแลระบบนี้";
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