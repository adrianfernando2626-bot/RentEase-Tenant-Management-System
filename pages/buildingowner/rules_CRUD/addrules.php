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
    $description = $_POST["description"] ?? '';
    $status = 'Active';
    $building_ids = $_POST["building_id"] ?? []; // array of checked building IDs

    if (!empty($title) && !empty($description) && !empty($building_ids)) {
        foreach ($building_ids as $building_id) {
            // Get building name
            $stmtB = $pdo->prepare("SELECT name FROM building WHERE building_id = ?");
            $stmtB->execute([$building_id]);
            $buildingName = $stmtB->fetchColumn();

            // Insert rule for this building
            $stmt = $pdo->prepare("INSERT INTO rules (title, rules_description, rules_status, building_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$title, $description, $status, $building_id]);

            // Get all active users
            $stmtU = $pdo->prepare("SELECT user_id, first_name, last_name, email 
                                    FROM userall 
                                    WHERE (role = 'Tenant' OR role = 'Landlord') 
                                    AND account_status = 'Active'");
            $stmtU->execute();
            $users = $stmtU->fetchAll();

            foreach ($users as $user) {
                $email = $user['email'];
                $name = $user['first_name'] . ' ' . $user['last_name'];
                $user_id = $user['user_id'];

                // Notification text with building name
                $notif_text = "Dear $name, A new rule has been added for <strong>$buildingName</strong>:
                <br><br>'$title'<br><br>
                If you have any concerns, please contact the building administration.
                <br><br>Thank you.";

                // Insert notification
                $pdo->prepare("INSERT INTO notification 
                               (user_id, notif_title, notif_text, date_created, notif_status) 
                               VALUES (?, 'Apartment Rules', ?, NOW(), 'unread')")
                    ->execute([$user_id, $notif_text]);

                // Send email
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
                    $mail->Subject = "New Rule for $buildingName";
                    $mail->Body = "Dear $name,<br><br>
                                   A new rule has been added for <strong>$buildingName</strong>: 
                                   <br><br>'$title'<br><br>
                                   If you have any concern, please contact the building administration.
                                   <br><br>Thank you.<br><br>
                                   <strong>This is a generated system email, please do not reply</strong>";

                    $mail->send();
                } catch (Exception $e) {
                    error_log("Failed to send email to $email: " . $mail->ErrorInfo);
                }
            }
        }
    }

    header("Location: ../rules.php?message=rules_inserted");
    exit();
}



?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../../css/addcontent2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <main class="main-content">
        <div class="form-section">
            <div class="form-header">
                <i class="fas fa-clipboard-list fa-2x"></i>
                <h1>Add a New Rule</h1>
            </div>
            <form class="rule-form" method="post">
                <div class="form-check">
                    <label for="building">Buildings Applied:</label><br>
                    <?php
                    $stmtbuilding = $pdo->prepare("SELECT building_id, name FROM building");
                    $stmtbuilding->execute();
                    $buildings = $stmtbuilding->fetchAll();
                    foreach ($buildings as $building):
                    ?>
                        <input id="building_id_<?php echo $building['building_id']; ?>"
                            type="checkbox"
                            name="building_id[]"
                            value="<?php echo $building['building_id']; ?>" checked>
                        <span><?php echo $building['name']; ?></span><br>

                    <?php endforeach; ?>
                </div>
                <label for="title">Rule Title</label>
                <input type="text" name="title" id="title" placeholder=" Add Rule Title" required>

                <label for="description">Rule Description</label>
                <textarea name="description" id="description" placeholder=" Add description" required></textarea>


                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-primary px-4 py-2">Save Rules</button>
                    <button type="button" class="btn btn-secondary px-4 py-2" onclick="window.location.href='../rules.php'">
                        Cancel
                    </button>
                </div>



            </form>
        </div>

        <!-- Right Panel -->
        </div>
    </main>
    <script src="../../js/script.js"></script>
</body>

</html>