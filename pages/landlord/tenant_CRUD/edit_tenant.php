<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../../tenant/guest_logging_process/vendor/autoload.php';

if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}
session_start();
$user_id_landlord = $_SESSION['user_id'];
$warning = "";
$stmtlandlord = $pdo->prepare("SELECT tenant_priviledge FROM userall WHERE user_id = ?");
$stmtlandlord->execute([$user_id_landlord]);
$landlord = $stmtlandlord->fetch(PDO::FETCH_ASSOC);
$disabled_button = "";
if ($landlord['tenant_priviledge'] === 'Not Approved') {
    $disabled_button = "disabled";
    $warning = "It seems you do not have the approval from the Owner to edit the tenant account status";
} else {
    $disabled_button = "";
    $warning = "";
}


$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT a.*, b.room_number
                        FROM userall a
                        JOIN room b ON b.room_id = a.room_id WHERE user_id = ?");
$stmt->execute([$id]);
$tenant = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_status = $_POST['account_status'] ?? '';
    $name = htmlspecialchars($tenant['first_name']) . ' ' . htmlspecialchars($tenant['last_name']) ?? '';
    $email = $tenant['email'] ?? '';
    $mail = new PHPMailer(true);
    try {
        if ($account_status) {
            $pdo->prepare("UPDATE user SET account_status=? WHERE user_id=?")->execute([$account_status, $id]);
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
            $mail->Body = "Dear $name,<br><br>Your account status has been updated to <strong>$account_status</strong>.<br><br>Thank you.";

            $mail->send();
        }
        header("Location: ../user_access.php?message=accounts_updated");
        exit();
    } catch (PDOException $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Email could not be sent to $email. Mailer Error: {$mail->ErrorInfo}");
        die("Database error: " . $e->getMessage());
    }
}

$stmt = $pdo->prepare("SELECT a.*, b.room_number
                        FROM userall a
                        JOIN room b ON b.room_id = a.room_id WHERE user_id = ?");
$stmt->execute([$id]);
$tenant = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../../css/addcontent.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        function logout() {
            var msg = 'Are you sure you want to logout?';
            Swal.fire({
                icon: 'question',
                title: 'Log Out',
                text: msg,
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '../../logout.php?status=logout';
                } else {
                    location.reload();
                }
            });
        }
    </script>
</head>

<body class="bg-light">
    <div class="side-bar collapsed">
        <a href="" class="logo">
            <img src="" alt="" class="logo-img">
            <img src="" alt="" class="logo-icon">
        </a>
        <ul class="nav-link">
            <li><a href="../landlord_dashboard.php"><i class="fas fa-th-large"></i>
                    <p>DashBoard</p>
                </a></li>
            <li><a href="../user_access.php"><i class="fas fa-users"></i>
                    <p>User Access Management</p>
                </a></li>
            <li><a href="../tenant_manage.php"><i class="fas fa-user-check"></i>
                    <p>Room Management</p>
                </a></li>
            <li><a href="../notification_log.php"><i class="fas fa-bell"></i>
                    <p>Billing Notifications</p>
                </a></li>
            <li><a href="../rentreportlandlord.php"><i class="fas fa-file-lines"></i>
                    <p>Report Management</p>
                </a></li>
            <li><a href="../landlordprofile.php"><i class="fas fa-cog"></i>
                    <p>User Account</p>
                </a></li>
            <li><a onclick="logout()"><i class="fas fa-sign-out-alt"></i>
                    <p> Logout</p>
                </a></li>
        </ul>
    </div>
    <main class="main-content">
        <div class="form-section">
            <div class="form-header">
                <i class="fas fa-clipboard-list fa-2x"></i>
                <h1>Edit Account Status</h1>
            </div>
            <?php if (!empty($warning)): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($warning); ?></div>
            <?php endif; ?>
            <form class="rule-form" method="post">
                <h3> <?php echo htmlspecialchars($tenant['first_name']) . ' ' . htmlspecialchars($tenant['last_name']); ?>
                </h3>
                <label for="status">Account Status:</label>
                <select name="account_status" id="account_status">
                    <?php
                    $current_status = $tenant['account_status'];
                    $all_statuses = ['Active', 'Inactive'];

                    // Show current status first
                    echo "<option value='" . $current_status . "' selected>" . $current_status . "</option>";

                    // Show other options except current
                    foreach ($all_statuses as $status) {
                        if ($status !== $current_status) {
                            echo "<option value='$status'>$status</option>";
                        }
                    }
                    ?>
                </select>



                <button type="submit" class="add-btn" <?php echo $disabled_button; ?>>Update</button>
                <a href="../user_access.php" class="back-btn">Cancel</a>
            </form>
    </main>
    <script src="../../js/script.js"></script>
</body>

</html>