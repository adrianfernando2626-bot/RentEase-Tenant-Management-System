<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}

$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $status = $_POST['status'] ?? '';
    try {
        if ($status & $title) {
            $pdo->prepare("UPDATE rules SET title=?, status=? WHERE rules_id=?")->execute([$title, $status, $id]);
        }
        header("Location: ../rules.php?message=rules_updated");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Database error: " . $e->getMessage());
    }
}

$stmt = $pdo->prepare("SELECT * FROM rules WHERE rules_id = ?");
$stmt->execute([$id]);
$rules = $stmt->fetch();
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
                <h4 class="card-title mb-3">Edit rules</h4>
                <form method="post">
                    <div class="mb-3">
                        <label>Rules Title:<textarea name="title" required><?= htmlspecialchars($rules['title']) ?></textarea></label><br><br>
                        <label>Account Status: <select name="status" id="status">
                                <?php
                                $status_option = '';
                                echo "<option value='" . $rules['status'] . "' selected>" . $rules['status'] . "</option>";
                                if ($rules['status'] === "Active") {
                                    $status_option = 'Inactive';
                                } elseif ($rules['status'] === "Inactive") {
                                    $status_option = 'Active';
                                }
                                echo "<option value='" . $status_option . "'>" . $status_option . "</option>";

                                ?>
                            </select>
                            <button type="submit" class="btn btn-success">Update</button>
                            <a href="../rules.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>

</html>