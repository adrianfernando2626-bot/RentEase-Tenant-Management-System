<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selectedRoom = $_POST['desired_room'] ?? null;

    if ($selectedRoom) {
        $query = $pdo->prepare("SELECT user_id FROM userall WHERE desired_room = ? AND account_status = 'Waiting to Renew' AND role = 'Tenant'");
        $query->execute([$selectedRoom]);
        $userIds = $query->fetchAll(PDO::FETCH_COLUMN); // simple array

        foreach ($userIds as $user_id_sub) {
            $query = $pdo->prepare("UPDATE user SET account_status = 'Pending' WHERE user_id = ?");
            $query->execute([$user_id_sub]);
        }
        $_SESSION['success_for_renewal'] = "Contract renewal request has been submitted.";
    }

    header("Location: ../contract_CRUD/addcontracts.php?message=renewal_sucess"); // or wherever the modal is
    exit();
}
