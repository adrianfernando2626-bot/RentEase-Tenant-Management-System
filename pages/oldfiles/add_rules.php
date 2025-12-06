<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../../tenant/guest_logging_process/vendor/autoload.php';

if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST["title"] ?? '';
    $status = 'Active';
    $stmt = $pdo->prepare("SELECT first_name, last_name, email FROM userall WHERE role = 'Tenant' AND account_status = 'Active'");
    $stmt->execute();
    $tenants = $stmt->fetchAll();
    if ($title) {
        $pdo->prepare("INSERT INTO rules(title, status) VALUES(?,?)")->execute([$title, $status]);
    }

    foreach ($tenants as $tenant) {
        $email = $tenant['email'];
        $name = $tenant['first_name'] . ' ' . $tenant['last_name'];
        $mail = new PHPMailer(true);
        try {

            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'adrianfernando2626@gmail.com';
            $mail->Password = 'cxwqqwktqevyogmt';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('adrianfernando2626@gmail.com', 'Building Owner');
            $mail->addAddress($email, $name);
            $mail->isHTML(true);
            $mail->Subject = 'Apartment Rules';
            $mail->Body = "Dear $name,<br><br>The owner has made a new rule for the apartment. <br><br>'$title'<br><br>If you have any concern, please contact the building administration.<br><br>Thank you.<br><br><strong>This is a generated system email, please do not reply</strong>";

            $mail->send();
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Failed to send email to $email: " . $mail->ErrorInfo);
            die("Database error: " . $e->getMessage());
        }
    }

    header("Location: ../rules.php?message=rules_inserted");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body class="bg-light">

    <div class="container my-5">
        <div class="card mx-auto shadow" style="max-width: 500px;">
            <div class="card-body">
                <h4 class="card-title mb-3">Add New Rule</h4>
                <form method="post">
                    <div class="mb-3">
                        <label>Rule Title: <textarea name="title" id="title" required></textarea></label><br><br>
                        <button type="submit" class="btn btn-success">Insert</button>
                        <a href="../rules.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>

</html>