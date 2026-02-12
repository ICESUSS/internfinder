<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
require __DIR__ . '/mail_config.php';

function send_internship_mail($to, $subject, $message, $attachments = []) {
    if (empty($to)) return false;

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // Recipients
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Attachments
        if (!empty($attachments)) {
            foreach ($attachments as $filePath) {
                if (file_exists($filePath)) {
                    $mail->addAttachment($filePath);
                }
            }
        }

        // Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        return $mail->send();
    } catch (Exception $e) {
        $error_msg = "PHPMailer Error: " . $mail->ErrorInfo;
        error_log($error_msg);
        // แสดง error ชั่วคราวบนหน้าจอเพื่อการ Debug
        if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin') {
            $_SESSION['mail_error'] = $error_msg;
        }
        return false;
    }
}
?>
