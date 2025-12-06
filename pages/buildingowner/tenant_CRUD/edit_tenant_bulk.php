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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tenants'])) {
    $updates = $_POST['tenants'] ?? [];
    $deletion_approval = $_POST['deletion_approval'] ?? "Not Approved";

    foreach ($updates as $user_id => $data) {
        $status = $data['account_status'] ?? '';
        $deletion_approval = isset($data['deletion_approval']) ? 'Approved' : 'Not Approved';

        if (in_array($status, ['Active', 'Inactive', 'Pending'])) {
            $updateStmt = $pdo->prepare("UPDATE user 
                                     SET account_status = ?, deletion_approval = ? 
                                     WHERE user_id = ?");
            $updateStmt->execute([$status, $deletion_approval, $user_id]);

            // Fetch tenant info
            $userStmt = $pdo->prepare("SELECT first_name, last_name, email FROM userall WHERE user_id = ?");
            $userStmt->execute([$user_id]);
            $tenant = $userStmt->fetch();

            if ($tenant) {
                $email = $tenant['email'];
                $name = $tenant['first_name'] . ' ' . $tenant['last_name'];

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
                    $mail->Subject = 'Account Status Updated';
                    $mail->Body = "Dear $name,<br><br>Your account status has been updated to 
                              <strong>$status</strong> with deletion approval <strong>$deletion_approval</strong>.<br><br>Thank you.";

                    $mail->send();

                    $notif_text = "Dear $name, 
                               Your account status has been updated to '$status' 
                               and deletion approval set to '$deletion_approval'.";

                    $pdo->prepare("INSERT INTO notification 
                               (user_id, notif_title, notif_text, date_created, notif_status) 
                               VALUES (?, 'Account Status Updated', ?, NOW(), 'unread')")
                        ->execute([$user_id, $notif_text]);
                } catch (Exception $e) {
                    error_log("Email failed for $email: " . $mail->ErrorInfo);
                }
            }
        }
    }


    header("Location: ../tenantmanage.php?message=accounts_updated");
    exit();
}

$selectedTenants = $_POST['selected_tenant'] ?? [];

if (empty($selectedTenants)) {
    die("No tenants selected.");
}

$placeholders = implode(',', array_fill(0, count($selectedTenants), '?'));
$stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email, account_status, deletion_approval FROM userall WHERE user_id IN ($placeholders)");
$stmt->execute($selectedTenants);
$tenants = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Selected Tenants</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link rel="stylesheet" href="../../css/addcontent2.css">


</head>

<body class="bg-light">
    <div class="container form-wrapper">
        <div class="card shadow w-100" style="max-width: 800px;">
            <div class="card-body">
                <div class="form-header text-center">
                    <i class="fas fa-clipboard-list fa-2x"></i>
                    <h1>Edit Selected Tenants</h1>
                </div>
                <form method="post">
                    <?php foreach ($tenants as $tenant): ?>
                        <div class="card mb-3 p-3 shadow-sm">
                            <h5><?= htmlspecialchars($tenant['first_name'] . ' ' . $tenant['last_name']) ?></h5>
                            <input type="hidden" name="tenants[<?= $tenant['user_id'] ?>][user_id]" value="<?= $tenant['user_id'] ?>">
                            <label>Account Status:
                                <select name="tenants[<?= $tenant['user_id'] ?>][account_status]" class="form-control">
                                    <option value="Active" <?= $tenant['account_status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive" <?= $tenant['account_status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </label>
                            <div class="form-check">
                                <input type="checkbox"
                                    id="tenantApproval<?= $tenant['user_id'] ?>"
                                    name="tenants[<?= $tenant['user_id'] ?>][deletion_approval]"
                                    value="Approved"
                                    <?= $tenant['deletion_approval'] === 'Approved' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="tenantApproval<?= $tenant['user_id'] ?>">
                                    Deletion Approved
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>


                    <div class="form-actions">
                        <button type="submit" name="bulkUpdate" class="add-btn">Save All Changes</button>
                        <button type="button" class="back-btn" onclick="window.location.href='../tenantmanage.php'">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="../../js/script.js"></script>
</body>


</html>