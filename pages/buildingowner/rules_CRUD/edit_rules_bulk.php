<?php
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}

session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rules'])) {
    foreach ($_POST['rules'] as $ruleData) {
        $id = $ruleData['rules_id'];
        $title = trim($ruleData['title'] ?? '');
        $status = $ruleData['status'] ?? '';
        $description = $ruleData['description'] ?? '';

        if ($title && $status) {
            $stmt = $pdo->prepare("UPDATE rules SET title = ?, rules_description = ?, rules_status = ? WHERE rules_id = ?");
            $stmt->execute([$title, $description, $status, $id]);
        }
    }

    header("Location: ../rules.php?message=rules_updated");
    exit();
}

$selectedRules = $_POST['selected_rules'] ?? [];

if (empty($selectedRules)) {
    die("No rules selected.");
}

$placeholders = implode(',', array_fill(0, count($selectedRules), '?'));
$stmt = $pdo->prepare("SELECT r.*, b.name FROM rules r
                        JOIN building b ON b.building_id = r.building_id   
                         WHERE r.rules_id IN ($placeholders)");
$stmt->execute($selectedRules);
$rules = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Edit Rules</title>
    <link rel="stylesheet" href="../../css/addcontent.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

</head>

<body class="bg-light">
    <div class="container my-5">
        <div class="card mx-auto shadow" style="max-width: 800px;">
            <div class="card-body">
                <div class="form-header">
                    <i class="fas fa-clipboard-list fa-2x"></i>
                    <h1>Edit Selected Rule</h1>
                </div>
                <form class="rule-form" method="post">
                    <?php foreach ($rules as $rule): $id = $rule['rules_id']; ?>

                        <div class="card mb-4 p-3 shadow-sm">
                            <h5><strong>Rule #<?= htmlspecialchars($id) ?></strong></h5>
                            <label><strong>Building Applied: </strong><?= htmlspecialchars($rule['name']) ?> </label>
                            <input type="hidden" name="rules[<?= $id ?>][rules_id]" value="<?= $id ?>">

                            <label><strong>Title:</strong>
                                <input type="text" name="rules[<?= $id ?>][title]" value="<?= htmlspecialchars($rule['title']) ?>" class="form-control" required>
                            </label><br>

                            <label> <strong>Description:</strong>
                                <textarea name="rules[<?= $id ?>][description]" id="description" class="form-control" placeholder=" Add description" required><?= htmlspecialchars($rule['rules_description']) ?></textarea>
                            </label><br>

                            <label><strong>Status:</strong>
                                <select name="rules[<?= $id ?>][rules_status]" class="form-control" required>
                                    <option value="Active" <?= $rule['rules_status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                                    <option value="Inactive" <?= $rule['rules_status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </label>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" class="add-btn">Save All Changes</button>
                    <button type="button" class="back-btn" onclick="window.location.href='../rules.php'">
                        Cancel
                    </button>

                </form>
            </div>
        </div>
    </div>
    <script src="../../js/script.js"></script>
</body>

</html>