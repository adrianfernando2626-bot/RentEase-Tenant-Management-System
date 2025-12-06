<?php
require_once '../../tenant/guest_logging_process/vendor/autoload.php';
include_once '../../includes/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$selectedLandlords = $_POST['selected_landlord'] ?? [];

if (empty($selectedLandlords)) {
    die("No tenants selected for deletion.");
}
$placeholders = implode(',', array_fill(0, count($selectedLandlords), '?'));
$stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email, room_id, building_id FROM userall WHERE user_id IN ($placeholders)");
$stmt->execute($selectedLandlords);
$landlords = $stmt->fetchAll();

$userUpdateStmt = $pdo->prepare("UPDATE user SET account_status = 'Deleted' WHERE user_id = ?");
$buildingstmt = $pdo->prepare("UPDATE building SET building_is_active = 0 WHERE building_id = ?");

foreach ($landlords as $landlord) {
    $user_id = $landlord['user_id'];
    $building_id = $landlord['building_id'];
    $email = $landlord['email'];
    $name = $landlord['first_name'] . ' ' . $landlord['last_name'];


    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'adrianfernando2626@gmail.com';
        $mail->Password = 'cxwqqwktqevyogmt';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('adrianfernando2626@gmail.com', 'Building Owner');
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Account Deletion Notice';
        $mail->Body = "Dear $name,<br><br>Your account has been deleted from our system.<br><br>If this is a mistake, please contact the building administration.";

        $mail->send();
    } catch (Exception $e) {
        error_log("Email to $email failed: " . $mail->ErrorInfo);
    }

    $userUpdateStmt->execute([$user_id]);
    $buildingstmt->execute([$building_id]);
}


header("Location: ../tenantmanage.php?message=accounts_deleted");
exit();
