<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}

$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issue_type = $_POST['issue_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $date_requested = $_POST['date_requested'] ?? '';
    try {
        if ($issue_type & $description & $date_requested) {
            $pdo->prepare("UPDATE maintenance_request SET issue_type=?, description=?, date_requested=? WHERE maintenance_request_id=?")->execute([$issue_type, $description, $date_requested, $id]);
        }
        header("Location: ../tenantmaintenance.php?message=request_updated");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Database error: " . $e->getMessage());
    }
}
$sql = "";

$stmt = $pdo->prepare("SELECT a.*, b.update_message
                                FROM maintenance_request a
                                JOIN status_request b ON b.maintenance_request_id = a.maintenance_request_id WHERE a.maintenance_request_id = ?");
$stmt->execute([$id]);
$request = $stmt->fetch();
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
                <h4 class="card-title mb-3">Edit request</h4>
                <form method="post">
                    <div class="mb-3">
                        <label> Request Issue Type:
                            <select name="issue_type" id="issue_type">
                                <?php
                                $status_option = ['Plumbing', 'Electrical', 'Noise', 'Appliance', 'Other'];

                                echo "<option value='" . $request['issue_type'] . "' selected>" . $request['issue_type'] . "</option>";
                                foreach ($status_option as $option) {
                                    if ($option !== $request['issue_type']) {
                                        echo "<option value='" . $option . "'>" . $option . "</option>";
                                    }
                                }
                                ?>
                            </select>
                        </label>

                        <label>Description:<textarea name="description" required><?= htmlspecialchars($request['description']) ?></textarea></label><br><br>
                        <label>Date Requested:<input type="date" name="date_requested" value="<?= htmlspecialchars($request['date_requested']) ?>" required /></label><br><br>

                        <button type="submit" class="btn btn-success">Update</button>
                        <a href="../tenantmaintenance.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>

</html>