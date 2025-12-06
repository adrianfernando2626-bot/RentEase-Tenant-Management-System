<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}
require_once '../../tenant/guest_logging_process/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contracts'])) {
    $contractsWithEmail = [];
    $contractsApproved = [];

    foreach ($_POST['contracts'] as $contractData) {
        $id = $contractData['contract_id'];

        // Check if the contract is approved
        $checkApproval = $pdo->prepare("SELECT update_status FROM contract WHERE contract_id = ?");
        $checkApproval->execute([$id]);
        $approval = $checkApproval->fetchColumn();

        if ($approval === 'Approved') {
            $contractsApproved[] = $contractData;
        } else {
            $contractsWithEmail[] = $id;
        }
    }

    // Update directly approved contracts
    foreach ($contractsApproved as $contractData) {
        $id = $contractData['contract_id'];
        $start_date = $contractData['start_date'];
        $end_date = $contractData['end_date'];
        $status = $contractData['status'];
        $termsArray = $contractData['terms'] ?? [];
        $customTerm = trim($contractData['custom_term'] ?? '');
        if (!empty($customTerm)) {
            $termsArray[] = $customTerm;
        }
        $terms = implode(', ', $termsArray);

        // Update contract
        $stmt = $pdo->prepare("UPDATE contract SET start_date=?, end_date=?, terms=?, contract_status=? WHERE contract_id=?");
        $stmt->execute([$start_date, $end_date, $terms, $status, $id]);

        // Get the user_id for this contract
        $userStmt = $pdo->prepare("SELECT user_id FROM contract WHERE contract_id = ?");
        $userStmt->execute([$id]);
        $userRow = $userStmt->fetch();

        if ($userRow && isset($userRow['user_id'])) {
            $user_id = $userRow['user_id'];

            // Update user account_status based on contract status
            $statusToSet = '';
            if (strtolower($status) === 'inactive') {
                $statusToSet = 'Inactive';
            } elseif (strtolower($status) === 'active') {
                $statusToSet = 'Active';
            }

            if ($statusToSet) {
                $updateUser = $pdo->prepare("UPDATE user SET account_status = ? WHERE user_id = ?");
                $updateUser->execute([$statusToSet, $user_id]);
            }
        }
    }

    // Send email to tenants whose contract is not approved
    foreach ($contractsWithEmail as $id) {
        $userDetails = $pdo->prepare("SELECT b.first_name, b.last_name, b.email
            FROM contract a
            JOIN userall b ON b.user_id = a.user_id
            WHERE a.contract_id = ?");
        $userDetails->execute([$id]);
        $user = $userDetails->fetch();

        if ($user) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'adrianfernando2626@gmail.com';
                $mail->Password   = 'cxwqqwktqevyogmt';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('adrianfernando2626@gmail.com', 'Verification System');
                $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);

                $mail->isHTML(true);
                $mail->Subject = 'Contract Update & Verification Notification';
                $mail->Body    = "Dear " . htmlspecialchars($user['first_name']) . ",<br><br>" .
                    "The building is requesting to obtain your consent to update your contract details.<br>" .
                    "You can log in to your account: <a href='localhost/capstone/pages/login.php'>Click here to login</a><br><br>" .
                    "<strong>This is an automated system message &mdash; please do not reply.</strong>";

                $mail->send();
            } catch (Exception $e) {
                error_log("Email to {$user['email']} failed: " . $mail->ErrorInfo);
            }
        }
    }

    header('Location: ../contractmanage.php?message=contract_updated_bulk');
}

// The rest of the file remains the same to render the form...
// Leave HTML rendering section untouched except for JS confirmation popup (shown in next step)



// ✅ Step 2: Display the selected contracts for editing (first POST from checkbox form)
$selectedContracts = $_POST['selected_contract'] ?? [];

if (empty($selectedContracts)) {
    die("No contracts selected.");
}

$placeholders = implode(',', array_fill(0, count($selectedContracts), '?'));

$stmt = $pdo->prepare("SELECT a.*, b.first_name, b.last_name, c.room_number
FROM contract a
JOIN personal_info b ON b.user_id = a.user_id
JOIN user d ON d.user_id = a.user_id
JOIN room c ON d.room_id = c.room_id
WHERE a.contract_id IN ($placeholders)");
$stmt->execute($selectedContracts);
$contracts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Contracts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="../../css/addcontent.css">

    <style>
        .add-btn {
            margin-top: 2rem;
            padding: 0.75rem 2rem;
            background-color: #a38336;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease;
        }

        .add-btn:hover {
            background-color: #8a6b2e;
        }

        .back-btn {
            margin-top: 2rem;
            padding: 0.75rem 2rem;
            background-color: #bb0b0b;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease;
        }
    </style>
</head>

<body class="bg-light">
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
            <li><a href="../logout.php" id="logoutBtn" class="logout-btn "><i class="fas fa-sign-out-alt"></i>
                    <p> Logout</p>
                </a></li>
            <div class="active"></div>
        </ul>
    </div>
    <div class="container my-5">
        <div class="card mx-auto shadow" style="max-width: 800px;">
            <div class="card-body">
                <div class="form-header">
                    <i class="fas fa-clipboard-list fa-2x"></i>
                    <h1>Edit Selected Contracts</h1>
                </div>
                <form method="post">
                    <?php foreach ($contracts as $contract):
                        $id = $contract['contract_id'];
                    ?>
                        <div class="card mb-4 p-3 shadow-sm">
                            <h5> <strong><?= htmlspecialchars($contract['first_name'] . ' ' . $contract['last_name']) ?> (Room <?= htmlspecialchars($contract['room_number']) ?>)</strong></h5>

                            <input type="hidden" name="contracts[<?= $id ?>][contract_id]" value="<?= $id ?>">

                            <label><strong>Start Date:</strong>
                                <input type="date" name="contracts[<?= $id ?>][start_date]" value="<?= htmlspecialchars($contract['start_date']) ?>" class="form-control" required>
                            </label><br>

                            <label><strong>End Date:</strong>
                                <input type="date" name="contracts[<?= $id ?>][end_date]" value="<?= htmlspecialchars($contract['end_date']) ?>" class="form-control" required>
                            </label><br>

                            <label><strong>Status:</strong>
                                <select name="contracts[<?= $id ?>][status]" class="form-control">
                                    <option value="Active" <?= $contract['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive" <?= $contract['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </label><br>

                            <?php
                            $termOptions = [
                                'deposit_return' => 'Security deposit is refundable upon move-out, minus any damages.',
                                'on_time_rent' => 'Rent must be paid on or before the due date each month.',
                                'no_subleasing' => 'Subleasing without landlord approval is not allowed.',
                                'utility_responsibility' => 'Tenant is responsible for all utility bills.',
                                'notice_required' => 'A 30-day notice is required before moving out.',
                                'property_care' => 'Tenant must maintain cleanliness and avoid damaging the property.'
                            ];

                            // Current terms from DB
                            $currentTerms = array_map('trim', explode(',', $contract['terms']));
                            $customTerms = array_filter($currentTerms, fn($term) => !array_key_exists($term, $termOptions));
                            ?>

                            <div>
                                <label class="form-label"><strong>Terms</strong> </label>
                                <small class="text-muted d-block mb-2">Select the terms to include in this contract. You may also add a custom term below.</small>

                                <?php foreach ($termOptions as $value => $label): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            name="contracts[<?= $id ?>][terms][]" value="<?= $value ?>"
                                            <?= in_array($value, $currentTerms) ? 'checked' : '' ?>>
                                        <label class="form-check-label"><?= $label ?></label>
                                    </div>
                                <?php endforeach; ?>

                                <label for="custom_term_<?= $id ?>" class="mt-2"><strong>Other (custom term)</strong> </label>
                                <input type="text" name="contracts[<?= $id ?>][custom_term]"
                                    value="<?= htmlspecialchars(implode(', ', $customTerms)) ?>"
                                    class="form-control" id="custom_term_<?= $id ?>" placeholder="Enter a custom term (optional)">
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="add-btn">Save All Changes</button>
                    <a href="../contractmanage.php" class="back-btn">Cancel</a>
                </form>
            </div>
        </div>
    </div>
    <script src="../../js/script.js"></script>
</body>

</html>