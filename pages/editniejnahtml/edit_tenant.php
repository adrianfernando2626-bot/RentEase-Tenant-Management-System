<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}

$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $account_status = $_POST['account_status'] ?? '';
    try {
        if ($account_status) {
            $pdo->prepare("UPDATE user SET account_status=? WHERE user_id=?")->execute([$account_status, $id]);
        }
        header("Location: ../tenantmanage.php?message=account_updated");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Document</title>
</head>

<body class="bg-light">

    <div class="container my-5">
        <div class="card mx-auto shadow" style="max-width: 500px;">
            <div class="card-body">
                <h4 class="card-title mb-3">Edit Tenant</h4>
                <form method="post">
                    <div class="mb-3">
                        <label>Name: <input type="text" name="name" value="<?= htmlspecialchars($tenant['first_name']) ?> <?= htmlspecialchars($tenant['last_name']) ?>" required disabled /></label><br><br>
                        <label>Room Number: <input type="number" name="room_number" value="<?= htmlspecialchars($tenant['room_number']) ?>" required disabled /></label><br><br>
                        <label>Email: <input type="text" name="email" value="<?= $tenant['email'] ?>" required disabled /></label><br><br>
                        <label>Role: <input type="text" name="role" value="<?= $tenant['role'] ?>" required disabled /></label><br><br>
                        <label>Account Status: <select name="account_status" id="account_status">
                                <?php
                                $status_option = '';
                                echo "<option value='" . $tenant['account_status'] . "'>" . $tenant['account_status'] . "</option>";
                                if ($tenant['account_status'] === "Active") {
                                    $status_option = 'Inactive';
                                } elseif ($tenant['account_status'] === "Inactive") {
                                    $status_option = 'Active';
                                }
                                echo "<option value='" . $status_option . "'>" . $status_option . "</option>";

                                ?>
                            </select>
                            <button type="submit" class="btn btn-success">Update</button>
                            <a href="../tenantmanage.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>

</html>