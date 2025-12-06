<?php
include_once '../../includes/database.php';

$selectedRules = $_POST['selected_rules'] ?? [];

if (empty($selectedRules)) {
    die("No rules selected for deletion.");
}

// Fetch rule data
$placeholders = implode(',', array_fill(0, count($selectedRules), '?'));
$stmt = $pdo->prepare("SELECT * FROM rules WHERE rules_id IN ($placeholders)");
$stmt->execute($selectedRules);
$rules = $stmt->fetchAll();

// Prepare delete statement
$rulesDeleteStmt = $pdo->prepare("DELETE FROM rules WHERE rules_id = ?");

// Delete each rule
foreach ($rules as $rule) {
    $rule_id = $rule['rules_id'];
    $title = $rule['title'];

    $rulesDeleteStmt->execute([$rule_id]);
}

header("Location: ../rules.php?message=rules_deleted");
exit();
