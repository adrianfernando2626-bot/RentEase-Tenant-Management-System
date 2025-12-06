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
    $description = $_POST['description'] ?? '';
    try {
        if ($status & $title) {
            $pdo->prepare("UPDATE rules SET title=?, description=?, status=? WHERE rules_id=?")->execute([$title, $description, $status, $id]);
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
    <title>Document</title>
    <link rel="stylesheet" href="../../css/addcontent.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
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
                <h1>Edit Rule</h1>
            </div>
            <form class="rule-form" method="post">
                <label for="title">Rule Title</label>
                <input type="text" name="title" id="title" placeholder=" Add Rule Title" value="<?= htmlspecialchars($rules['title']) ?>" required>

                <label for="description">Rule Description</label>
                <textarea id="description" name="description" placeholder="Add description" required><?= htmlspecialchars($rules['description']) ?></textarea>

                <label for="status">Status:</label>
                <select name="status" id="status" required>
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

                <button type="submit" class="add-btn">Add Rule</button>
                <a href="../rules.php" class="back-btn">Back</a>
            </form>
    </main>
    <script src="../../js/script.js"></script>
</body>

</html>