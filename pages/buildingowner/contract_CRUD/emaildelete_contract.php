<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}
require_once '../../tenant/guest_logging_process/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_GET['id'])) {
    $id = $_GET['id'] ?? 0;
    $stmt = $pdo->prepare("SELECT b.first_name, b.last_name, b.email 
                from contract a
                JOIN userall b ON b.user_id = a.user_id
                WHERE a.contract_id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'adrianfernando2626@gmail.com';
        $mail->Password   = 'cxwqqwktqevyogmt';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('adrianfernando2626@gmail.com', 'Verification System');
        $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Contract Delete & Verification Notification';
        $mail->Body    = "Dear " . htmlspecialchars($user['first_name']) . ",<br><br>" .
            "The building owner is requesting to obtain your consent to delete your contract.<br>" .
            "You can log in to your account: <a href='localhost/capstone/pages/login.php'>Click here to login</a><br><br>" .
            "<strong>This is an automated system message — please do not reply.</strong>";

        $mail->send();

        header('Location: ../contractmanage.php?message=email_successful');
    } catch (Exception $e) {
        // Optional: log or display failed emails
        error_log("Email to {$user['email']} failed: " . $mail->ErrorInfo);
    }
}
