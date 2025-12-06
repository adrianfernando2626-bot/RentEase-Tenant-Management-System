<?php
include_once '../../includes/database.php';
session_start();
$user_id_landlord = $_SESSION['user_id'];
$stmtlandlord = $pdo->prepare("SELECT payment_priviledge FROM userall WHERE user_id = ?");
$stmtlandlord->execute([$user_id_landlord]);
$landlord = $stmtlandlord->fetch(PDO::FETCH_ASSOC);
if ($landlord['payment_priviledge'] === 'Not Approved') {
    $_SESSION['warning_approval_payment'] = "It seems you do not have the approval from the Owner to delete the Payment Archived";
    header("Location: ../tenant_manage.php?walalang");
    exit();
} else {
    try {
        $pdo->beginTransaction();

        $selectPayment_id = $pdo->prepare("SELECT a.payment_id FROM payment a 
                                       JOIN payment_status b ON a.payment_id = b.payment_id 
                                       WHERE b.payment_status = 'Archived'");
        $selectPayment_id->execute();
        $payment_ids = $selectPayment_id->fetchAll(PDO::FETCH_ASSOC);

        $deletePayment = $pdo->prepare("DELETE FROM payment WHERE payment_id = ?");
        $deletePaymentStatus = $pdo->prepare("DELETE FROM payment_status WHERE payment_id = ?");

        foreach ($payment_ids as $pid) {
            $payment_id = $pid['payment_id'];
            $deletePaymentStatus->execute([$payment_id]);
            $deletePayment->execute([$payment_id]);
        }

        $pdo->commit();
        header("Location: ../tenant_manage.php?message=payment_deleted");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Failed to delete payments: " . $e->getMessage();
        exit();
    }
}
