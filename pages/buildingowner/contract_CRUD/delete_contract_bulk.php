<?php
include_once '../../includes/database.php';
require_once '../../tenant/guest_logging_process/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$selected_contract = $_POST['selected_contract'] ?? [];

if (empty($selected_contract)) {
    die("No contract selected for deletion.");
}

// Fetch contracts and related tenant info
$placeholders = implode(',', array_fill(0, count($selected_contract), '?'));
$query = "SELECT 
            c.contract_id, 
            c.user_id, 
            c.update_status,
            c.contract_status,
            pi.first_name, 
            pi.last_name, 
            pi.email, 
            pay.payment_id,
            pi.room_id 
          FROM contract c
          JOIN userall pi ON c.user_id = pi.user_id
          JOIN payment pay ON c.contract_id  = pay.contract_id   
            JOIN payment_status stat ON pay.payment_id  = stat.payment_id   
          WHERE c.contract_id IN ($placeholders)";
$stmt = $pdo->prepare($query);
$stmt->execute($selected_contract);
$contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
$today = date('Y-m-d');
$deleteStmt = $pdo->prepare("UPDATE contract SET contract_status = 'Deleted' WHERE contract_id = ?");
$terminateAccountStmt = $pdo->prepare("UPDATE user SET account_status = 'Deleted', room_id = null, desired_room = null WHERE user_id = ?");
$roomdeletestmt = $pdo->prepare("UPDATE room SET room_status = 'Available' WHERE room_id = ?");

foreach ($contracts as $contract) {
    $contract_id = $contract['contract_id'];
    $user_id = $contract['user_id'];
    $update_status = $contract['update_status'];
    $status = $contract['contract_status'];
    $payment_id = $contract['payment_id'];
    $room_id = $contract['room_id'];

    if ($update_status === 'Approved' || $status === 'Expired') {
        $deleteStmt->execute([$contract_id]);
        $terminateAccountStmt->execute([$user_id]);

        $deletepaymentStmt = $pdo->prepare("
            UPDATE payment_status 
            SET is_active = 0 
            WHERE payment_id IN (
                SELECT p.payment_id 
                FROM payment p 
                WHERE p.contract_id = ?
            )
        ");
        $maintenanceStatusStmt = $pdo->prepare("
                                UPDATE status_request 
                                SET update_message = 'Archived',
                                    updated_at = ?
                                WHERE maintenance_request_id IN (
                                    SELECT m.maintenance_request_id     
                                    FROM maintenance_request m 
                                    WHERE m.room_id = ?
                                )
                            ");
        $maintenanceStatusStmt->execute([$today, $room_id]);
        $deletepaymentStmt->execute([$contract_id]);
        $roomdeletestmt->execute([$room_id]);
    } else {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'adrianfernando2626@gmail.com';
            $mail->Password   = 'cxwqqwktqevyogmt';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('adrianfernando2626@gmail.com', 'Termination Request');
            $mail->addAddress($contract['email'], $contract['first_name'] . ' ' . $contract['last_name']);

            $mail->isHTML(true);
            $mail->Subject = 'Contract Termination Consent Needed';
            $mail->Body    = "Dear " . htmlspecialchars($contract['first_name']) . ",<br><br>" .
                "We received a request to terminate your housing contract. However, we require your approval to proceed.<br><br>" .
                "<strong>Please reply to this email or click the confirmation link to approve the termination.</strong><br><br>" .
                "<a href='http://localhost/capstone/contract_CRUD/approve_termination.php?id={$contract_id}'>Click here to approve termination</a><br><br>" .
                "Thank you.";

            $mail->send();
        } catch (Exception $e) {
            error_log("Email to {$contract['email']} failed: " . $mail->ErrorInfo);
        }
    }
}

header("Location: ../contractmanage.php?message=contract_terminated_bulk&room_id=" . $room_id);
exit();
