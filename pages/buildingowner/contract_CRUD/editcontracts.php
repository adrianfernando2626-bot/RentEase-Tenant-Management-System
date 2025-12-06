<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}
require_once '../../tenant/guest_logging_process/lib/phpqrcode/qrlib.php';
require_once '../../tenant/guest_logging_process/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$id = $_GET['id'] ?? 0;
$warning = "";

if (isset($_GET['message']) && $_GET['message'] === 'email_successful') {
    $warning = 'Email Successful';
}

$stmt = $pdo->prepare("SELECT update_status
                from contract 
                WHERE contract_id = ?");
$stmt->execute([$id]);
$status = $stmt->fetch();

if (isset($_POST['email_tenants'])) {
    $stmt = $pdo->prepare("SELECT b.first_name, b.last_name, b.email 
                from contract a
                JOIN userall b ON b.user_id=a.user_id
                WHERE a.contract_id = ? AND a.update_status = 'Not Approved'");
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
        $mail->Subject = 'Contract Update & Verification Notification';
        $mail->Body    = "Dear " . htmlspecialchars($user['first_name']) . ",<br><br>" .
            "The building is requesting to obtain your consent to update your contract details.<br>" .
            "You can log in to your account: <a href='localhost/capstone/pages/login.php'>Click here to login</a><br><br>" .
            "<strong>This is an automated system message — please do not reply.</strong>";

        $mail->send();

        header('Location: editcontracts.php?id=' . $id . '&message=email_successful');
    } catch (Exception $e) {
        // Optional: log or display failed emails
        error_log("Email to {$user['email']} failed: " . $mail->ErrorInfo);
    }
}


if (isset($_POST['update_contracts'])) {
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';

    $termsArray = isset($_POST['terms']) ? $_POST['terms'] : [];
    $otherTerm = trim($_POST['other_term'] ?? '');
    if (!empty($otherTerm)) {
        $termsArray[] = $otherTerm;
    }
    $terms = implode(', ', $termsArray);

    $status = $_POST['status'] ?? '';
    try {
        if ($start_date & $end_date & $terms & $status) {
            $pdo->beginTransaction();

            // Update contract
            $pdo->prepare("UPDATE contract SET start_date=?, end_date=?, terms=?, status=? WHERE contract_id=?")
                ->execute([$start_date, $end_date, $terms, $status, $id]);

            // Fetch user_id for the contract
            $stmtUser = $pdo->prepare("SELECT user_id FROM contract WHERE contract_id = ?");
            $stmtUser->execute([$id]);
            $user_id = $stmtUser->fetchColumn();

            // Update user account_status
            $pdo->prepare("UPDATE user SET account_status=? WHERE user_id=?")
                ->execute([$status, $user_id]);

            $pdo->commit();
        }
        header("Location: ../contractmanage.php?message=contract_updated");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Database error: " . $e->getMessage());
    }
}

$stmt = $pdo->prepare("SELECT a.*, b.first_name, b.last_name, c.room_number 
                from contract a
                JOIN personal_info b ON b.user_id=a.user_id
                JOIN user d ON d.user_id=a.user_id
                JOIN room c ON d.room_id=c.room_id WHERE contract_id = ?");
$stmt->execute([$id]);
$contract = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../../css/addcontent.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        function confirmEmail(id) {
            var msg = 'It seems the tenant for this contract is not aware that you will edit this contract, Do you want to notify this tenant to get their consent?';
            Swal.fire({
                icon: 'question',
                title: 'Update Contract Confirmation',
                text: msg,
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('emailForm').submit();
                } else {
                    location.reload();

                }
            });
        }

        function ApprovedTenant() {
            document.getElementById('approvedForm').submit();
        }
    </script>
</head>

<body>
    <div class="side-bar collapsed">
        <a href="" class="logo">
            <img src="" alt="" class="logo-img">
            <img src="" alt="" class="logo-icon">
        </a>
        <ul class="nav-link">
            <li><a href="../ownerdashboard.php"><i class="fas fa-th-large"></i>
                    <p>DashBoard</p>
                </a></li>
            <li><a href="../tenantmanage.php"><i class="fas fa-users"></i>
                    <p>User Access Management</p>
                </a></li>
            <li><a href="../contractmanage.php"><i class="fas fa-file-contract"></i>
                    <p>Contract Management</p>
                </a></li>
            <li><a href="../rules.php"><i class="fas fa-file-lines"></i>
                    <p>Rules Management</p>
                </a></li>
            <li><a href="../ownerprofile.php"><i class="fas fa-cog"></i>
                    <p>User Account</p>
                </a></li>
            <div class="active"></div>
        </ul>
    </div>

    <main class="main-content">
        <div class="form-section">
            <div class="form-header">
                <i class="fas fa-clipboard-list fa-2x"></i>
                <h1>Edit Contract</h1>
            </div>
            <?php if (!empty($warning)): ?>
                <div class="alert alert-warning" style="text-align: center;"><?php echo htmlspecialchars($warning); ?></div>
            <?php endif; ?>
            <form class="rule-form" id="approvedForm" method="post">
                <input type="hidden" name="update_contracts" value="1">
                <label for="start-date">Start Date</label>
                <input type="date" name="start_date" value="<?= htmlspecialchars($contract['start_date']) ?>" required />

                <label for="end-date">End Date</label>
                <input type="date" name="end_date" value="<?= htmlspecialchars($contract['end_date']) ?>" required />
                <label for="contract-status">Contract Status:</label>
                <select name="status" id="status">
                    <?php
                    $status_option = '';
                    echo "<option value='" . $contract['status'] . "'>" . $contract['status'] . "</option>";
                    if ($contract['status'] === "Active") {
                        $status_option = 'Inactive';
                    } elseif ($contract['status'] === "Inactive") {
                        $status_option = 'Active';
                    }
                    echo "<option value='" . $status_option . "'>" . $status_option . "</option>";

                    ?>
                </select>

                <?php
                $termOptions = [
                    'deposit_return' => 'Security deposit is refundable upon move-out, minus any damages.',
                    'on_time_rent' => 'Rent must be paid on or before the due date each month.',
                    'no_subleasing' => 'Subleasing without landlord approval is not allowed.',
                    'utility_responsibility' => 'Tenant is responsible for all utility bills.',
                    'notice_required' => 'A 30-day notice is required before moving out.',
                    'property_care' => 'Tenant must maintain cleanliness and avoid damaging the property.'
                ];

                // Convert saved terms string to array
                $currentTerms = array_map('trim', explode(',', $contract['terms']));
                $customTerms = array_filter($currentTerms, fn($term) => !array_key_exists($term, $termOptions));
                ?>

                <h3 style="margin-top: 2rem; font-size: 1.1rem;">Terms</h3>
                <p style="font-size: 0.9rem; margin-bottom: 1rem;">
                    Select the terms to include in this contract. You may also add a custom term below.
                </p>
                <div class="checkbox-group">
                    <?php foreach ($termOptions as $value => $label): ?>
                        <label>
                            <span><input type="checkbox" name="terms[]" value="<?= $value ?>" <?= in_array($value, $currentTerms) ? 'checked' : '' ?>>
                                <?= $label ?>
                            </span>
                        </label><br>
                    <?php endforeach; ?>
                </div>


                <label for="custom-term">Other (custom term)</label>
                <input type="text" name="other_term" id="other_term" class="form-control"
                    placeholder="Enter a custom term (optional)"
                    value="<?= htmlspecialchars(implode(', ', $customTerms)) ?>">



                <div style="display: flex; gap: 1rem;">
                    <button type="button" class="add-btn" <?php
                                                            $onclickFunction = "";
                                                            if ($status['update_status'] === 'Approved') {
                                                                $onclickFunction = 'ApprovedTenant()';
                                                            } elseif ($status['update_status'] === 'Not Approved') {
                                                                $onclickFunction = 'confirmEmail(' . $id . ')';
                                                            }
                                                            ?>
                        onclick="<?php echo $onclickFunction; ?>">Update</button>
                    <a href="../contractmanage.php" class="back-btn">Cancel</a>
                </div>
            </form>
            <form id="emailForm" action="editcontracts.php?id=<?= $id ?>" method="post">
                <input type="hidden" name="email_tenants" value="1">
            </form>
    </main>
    <script src="../../js/script.js"></script>
</body>

</html>