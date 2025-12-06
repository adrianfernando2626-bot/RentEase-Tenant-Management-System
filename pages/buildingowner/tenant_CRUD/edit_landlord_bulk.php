<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../../tenant/guest_logging_process/vendor/autoload.php';

if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
} elseif (file_exists('../includes/database.php')) {
    include_once('../includes/database.php');
} elseif (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['landlords'])) {
    $updates = $_POST['landlords'] ?? [];

    foreach ($updates as $user_id => $data) {
        $status = $data['account_status'] ?? '';
        $payment = $data['payment_privilege'] ?? 'Not Approved';
        $tenant = $data['tenant_privilege'] ?? 'Not Approved';

        // Fetch current data for comparison
        $currentStmt = $pdo->prepare("SELECT account_status, payment_priviledge, tenant_priviledge FROM user WHERE user_id = ?");
        $currentStmt->execute([$user_id]);
        $current = $currentStmt->fetch(PDO::FETCH_ASSOC);

        $changes = [];

        if ($status !== $current['account_status'] && in_array($status, ['Active', 'Inactive', 'Pending'])) {
            $changes[] = "Account Status changed to <strong>$status</strong>";
        }

        if ($payment !== $current['payment_priviledge']) {
            $changes[] = "Payment Privilege changed to <strong>$payment</strong>";
        }

        if ($tenant !== $current['tenant_priviledge']) {
            $changes[] = "Tenant Privilege changed to <strong>$tenant</strong>";
        }

        if (!empty($changes)) {
            // Perform update
            $updateStmt = $pdo->prepare("UPDATE user SET account_status = ?, payment_priviledge = ?, tenant_priviledge = ? WHERE user_id = ?");
            $updateStmt->execute([$status, $payment, $tenant, $user_id]);

            // Fetch user info
            $userStmt = $pdo->prepare("SELECT first_name, last_name, email FROM userall WHERE user_id = ?");
            $userStmt->execute([$user_id]);
            $user = $userStmt->fetch();

            if ($user) {
                $email = $user['email'];
                $name = $user['first_name'] . ' ' . $user['last_name'];
                $changeDetails = implode("<br>", $changes);

                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'adrianfernando2626@gmail.com';
                    $mail->Password = 'cxwqqwktqevyogmt';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('adrianfernando2626@gmail.com', 'Owner');
                    $mail->addAddress($email, $name);
                    $mail->isHTML(true);
                    $mail->Subject = 'Account Update Notification';
                    $mail->Body = "Dear $name,<br><br>The following updates have been made to your account:<br><br>$changeDetails<br><br>Thank you.";

                    $mail->send();
                } catch (Exception $e) {
                    error_log("Email failed for $email: " . $mail->ErrorInfo);
                }
            }
        }
    }


    header("Location: ../tenantmanage.php?message=accounts_landlord_updated");
    exit();
}

$selectedLandlord = $_POST['selected_landlord'] ?? [];

if (empty($selectedLandlord)) {
    die("No Landlord selected.");
}

$placeholders = implode(',', array_fill(0, count($selectedLandlord), '?'));
$stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email, account_status, payment_priviledge, tenant_priviledge FROM userall WHERE user_id IN ($placeholders)");
$stmt->execute($selectedLandlord);
$landlords = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Selected Tenants</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="../../css/addcontent.css">


</head>


<body class="bg-light">
    <div class="container my-5">
        <div class="card mx-auto shadow" style="max-width: 800px;">
            <div class="card-body">
                <div class="form-header">
                    <i class="fas fa-clipboard-list fa-2x"></i>
                    <h1>Edit Selected Landlord</h1>
                </div>
                <form method="post">
                    <?php foreach ($landlords as $landlord): ?>
                        <div class="card mb-3 p-3 shadow-sm">
                            <h5><?= htmlspecialchars($landlord['first_name'] . ' ' . $landlord['last_name']) ?></h5>
                            <input type="hidden" name="landlords[<?= $landlord['user_id'] ?>][user_id]" value="<?= $landlord['user_id'] ?>">

                            <div class="mb-2">
                                <label>Account Status:
                                    <select name="landlords[<?= $landlord['user_id'] ?>][account_status]" class="form-control">
                                        <option value="Active" <?= $landlord['account_status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                        <option value="Inactive" <?= $landlord['account_status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                    </select>
                                </label>
                            </div>

                            <div class="form-check">
                                <input type="checkbox" name="landlords[<?= $landlord['user_id'] ?>][payment_privilege]" value="Approved" <?= $landlord['payment_priviledge'] === 'Approved' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="payPriv<?= $landlord['user_id'] ?>">
                                    Payment Privilege
                                </label>
                            </div>

                            <div class="form-check">
                                <input type="checkbox" name="landlords[<?= $landlord['user_id'] ?>][tenant_privilege]" value="Approved" <?= $landlord['tenant_priviledge'] === 'Approved' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="tenantPriv<?= $landlord['user_id'] ?>">
                                    Tenant Privilege
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>


                    <button type="submit" name="bulkUpdate" class="add-btn">Save All Changes</button>
                    <button type="button" class="back-btn" onclick="window.location.href='../tenantmanage.php'">Cancel</button>

                </form>
            </div>
        </div>
    </div>
    <script src="../../js/script.js"></script>

</body>

</html>