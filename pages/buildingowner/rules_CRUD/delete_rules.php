<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}
$id = $_GET['id'] ?? 0;

if ($id) {
    $stmt = $pdo->prepare("DELETE FROM rules WHERE rules_id = ?");
    $stmt->execute([$id]);
}

header("Location: ../rules.php?message=rules_deleted");
exit();
