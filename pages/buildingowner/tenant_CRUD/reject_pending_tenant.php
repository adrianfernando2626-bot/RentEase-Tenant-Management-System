<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../../tenant/guest_logging_process/vendor/autoload.php';
include_once("../../includes/database.php"); // Your DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_tenant'])) {
    $selectedIds = $_POST['selected_tenant'] ?? [];


    foreach ($selectedIds as $userId) {
        // Get tenant info before deletion
        $query = "SELECT email, first_name, last_name FROM userall WHERE user_id = ?";
        $stmt = $db_connection->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tenant = $result->fetch_assoc();

        if ($tenant) {
            $email = $tenant['email'];
            $name = $tenant['first_name'] . ' ' . $tenant['last_name'];

            // Delete tenant (adjust table if needed)
            $delete = $db_connection->prepare("DELETE FROM user WHERE user_id = ?");
            $delete->bind_param("i", $userId);
            $delete->execute();

            // Send rejection email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; // or your SMTP host
                $mail->SMTPAuth   = true;
                $mail->Username = 'adrianfernando2626@gmail.com';
                $mail->Password = 'cxwqqwktqevyogmt';  // use app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('adrianfernando2626@gmail.com', 'Tenant Management');
                $mail->addAddress($email, $name);

                $mail->isHTML(true);
                $mail->Subject = 'Tenant Application Rejected';
                $mail->Body    = "
                    <p>Dear <b>$name</b>,</p>
                    <p>We regret to inform you that your tenant application has been <b>rejected</b>.</p>
                    <p>If you believe this is an error, kindly contact the administrator.</p>
                    <br>
                    <p>Thank you,<br>Apartment Management Team</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log("Email could not be sent to $email. Error: {$mail->ErrorInfo}");
            }
        }
    }

    header("Location: ../tenantmanage.php?message=accounts_deleted");
    exit;
} else {
    header("Location: ../tenantmanage.php?error=no_selection");
    exit;
}
