<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}

$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Document</title>
</head>

<body class="bg-light">

    <div class="container my-5">
        <div class="card mx-auto shadow" style="max-width: 500px;">
            <div class="card-body">
                <h4 class="card-title mb-3">Edit Contract</h4>
                <form method="post">
                    <div class="mb-3">
                        <label>Start Date: <input type="date" name="start_date" value="<?= htmlspecialchars($contract['start_date']) ?>" required /></label><br><br>
                        <label>End Date: <input type="date" name="end_date" value="<?= htmlspecialchars($contract['end_date']) ?>" required /></label><br><br>
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

                        <div>
                            <label for="terms">Terms</label>
                            <small class="text-muted d-block mb-2">Select the terms to include in this contract. You may also add a custom term below.</small>

                            <?php foreach ($termOptions as $value => $label): ?>
                                <label>
                                    <input type="checkbox" name="terms[]" value="<?= $value ?>" <?= in_array($value, $currentTerms) ? 'checked' : '' ?>>
                                    <?= $label ?>
                                </label><br>
                            <?php endforeach; ?>

                            <label for="other_term" class="mt-2">Other (custom term)</label>
                            <input type="text" name="other_term" id="other_term" class="form-control"
                                placeholder="Enter a custom term (optional)"
                                value="<?= htmlspecialchars(implode(', ', $customTerms)) ?>">
                        </div>
                        </label><br><br>
                        <label>Contract Status:</label>
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
                        <button type="submit" class="btn btn-success">Update</button>
                        <a href="../contractmanage.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>

</html>