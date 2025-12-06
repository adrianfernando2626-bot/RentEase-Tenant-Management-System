<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contracts'])) {
    foreach ($_POST['contracts'] as $contractData) {
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
        $stmt = $pdo->prepare("UPDATE contract SET start_date=?, end_date=?, terms=?, status=? WHERE contract_id=?");
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

    header("Location: ../contractmanage.php?message=contract_updated");
    exit();
}


// ✅ Step 2: Display the selected contracts for editing (first POST from checkbox form)
$selectedContracts = $_POST['selected_contracts'] ?? [];

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
    <title>Edit Contracts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <div class="container my-5">
        <div class="card mx-auto shadow" style="max-width: 800px;">
            <div class="card-body">
                <h4 class="card-title mb-4">Edit Selected Contracts</h4>
                <form method="post">
                    <?php foreach ($contracts as $contract):
                        $id = $contract['contract_id'];
                    ?>
                        <div class="card mb-4 p-3 shadow-sm">
                            <h5><?= htmlspecialchars($contract['first_name'] . ' ' . $contract['last_name']) ?> (Room <?= htmlspecialchars($contract['room_number']) ?>)</h5>

                            <input type="hidden" name="contracts[<?= $id ?>][contract_id]" value="<?= $id ?>">

                            <label>Start Date:
                                <input type="date" name="contracts[<?= $id ?>][start_date]" value="<?= htmlspecialchars($contract['start_date']) ?>" class="form-control" required>
                            </label><br>

                            <label>End Date:
                                <input type="date" name="contracts[<?= $id ?>][end_date]" value="<?= htmlspecialchars($contract['end_date']) ?>" class="form-control" required>
                            </label><br>

                            <label>Status:
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
                                <label class="form-label">Terms</label>
                                <small class="text-muted d-block mb-2">Select the terms to include in this contract. You may also add a custom term below.</small>

                                <?php foreach ($termOptions as $value => $label): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                            name="contracts[<?= $id ?>][terms][]" value="<?= $value ?>"
                                            <?= in_array($value, $currentTerms) ? 'checked' : '' ?>>
                                        <label class="form-check-label"><?= $label ?></label>
                                    </div>
                                <?php endforeach; ?>

                                <label for="custom_term_<?= $id ?>" class="mt-2">Other (custom term)</label>
                                <input type="text" name="contracts[<?= $id ?>][custom_term]"
                                    value="<?= htmlspecialchars(implode(', ', $customTerms)) ?>"
                                    class="form-control" id="custom_term_<?= $id ?>" placeholder="Enter a custom term (optional)">
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="btn btn-success">Save All Changes</button>
                    <a href="../contractmanage.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>

</html>