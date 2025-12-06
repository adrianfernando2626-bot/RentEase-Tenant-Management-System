<?php
include_once '../../includes/database.php';

try {
    $pdo->beginTransaction();

    $stmtMaintenance = $pdo->prepare("SELECT user_id, room_id, img FROM userall WHERE account_status = 'Deleted'");
    $stmtMaintenance->execute();
    $user_ids = $stmtMaintenance->fetchAll(PDO::FETCH_ASSOC);

    $deleteUser = $pdo->prepare("DELETE FROM user WHERE user_id = ?");
    $deletePersonal = $pdo->prepare("DELETE FROM personal_info WHERE user_id = ?");
    $deleteCredential = $pdo->prepare("DELETE FROM credential WHERE user_id = ?");
    $deleteGuest = $pdo->prepare("DELETE FROM guest_logs WHERE user_id = ?");
    $deleteContract = $pdo->prepare("DELETE FROM contract WHERE user_id = ?");
    $deletePayment = $pdo->prepare("DELETE FROM payment WHERE contract_id = ?");
    $deletePaymentStatus = $pdo->prepare("DELETE FROM payment_status WHERE payment_id = ?");
    $deleteMaintenance = $pdo->prepare("DELETE FROM maintenance_request WHERE maintenance_request_id = ?");
    $deleteStatusRequest = $pdo->prepare("DELETE FROM status_request WHERE maintenance_request_id = ?");

    $stmtContract = $pdo->prepare("SELECT contract_id FROM contract WHERE user_id = ?");
    $stmtPayment = $pdo->prepare("SELECT payment_id FROM payment WHERE contract_id = ?");
    $stmtMaintenanceId = $pdo->prepare("SELECT maintenance_request_id FROM maintenance_request WHERE room_id = ?");

    foreach ($user_ids as $row) {
        $user_id = $row['user_id'];
        $room_id = $row['room_id'];

        $img = $row['img'];
        $path = __DIR__ . '/../../images/';

        $file = $path . $img;
        if (file_exists($file)) {
            if (unlink($file)) {
                echo "Deleted: $file<br>";
            } else {
                echo "Failed to delete: $file<br>";
            }
        } else {
            echo "File not found: $file<br>";
        }

        $stmtArchivedMaintenance = $pdo->prepare("SELECT maintenance_request_id FROM status_request WHERE update_message = 'Archived'");
        $stmtArchivedMaintenance->execute();
        $archived_ids = $stmtArchivedMaintenance->fetchAll(PDO::FETCH_COLUMN);

        foreach ($archived_ids as $mid) {
            $deleteStatusRequest->execute([$mid]);
            $deleteMaintenance->execute([$mid]);
        }


        $stmtContract->execute([$user_id]);
        $contract_ids = $stmtContract->fetchAll(PDO::FETCH_COLUMN);

        foreach ($contract_ids as $contract_id) {
            $stmtPayment->execute([$contract_id]);
            $payment_ids = $stmtPayment->fetchAll(PDO::FETCH_COLUMN);

            foreach ($payment_ids as $payment_id) {
                $deletePaymentStatus->execute([$payment_id]);
            }

            $deletePayment->execute([$contract_id]);
        }

        $deleteContract->execute([$user_id]);
        $deleteCredential->execute([$user_id]);
        $deletePersonal->execute([$user_id]);
        $deleteGuest->execute([$user_id]);
        $deleteUser->execute([$user_id]);
    }

    $pdo->commit();
    header("Location: ../contractmanage.php?message=permanent_contract_deleted");
    exit();
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Failed to delete users: " . $e->getMessage();
    exit();
}
