<?php
require_once 'lib/phpqrcode/qrlib.php';

$pdo = new PDO("mysql:host=localhost;dbname=apartment", "root", "");

$id = $_GET['id'] ?? null;
if (!$id) die("Missing ID");

$stmt = $pdo->prepare("SELECT * FROM guest_logs WHERE id = ?");
$stmt->execute([$id]);
$guest = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$guest) die("Guest not found");
$qr_token = $guest['qr_token'];
$qrData = "TOKEN:$qr_token";

ob_start();
QRcode::png($qrData, null, QR_ECLEVEL_L, 4);
$qrBase64 = base64_encode(ob_get_clean());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $visit_datetime = $_POST['visit_datetime'] ?? '';
    $email = $_POST['email'] ?? '';
    try {
        if ($name & $purpose & $visit_datetime & $email) {
            $pdo->prepare("UPDATE guest_logs 
                            SET name=?, purpose=?, visit_datetime=?, email=?  
                            WHERE id=?")->execute([$name, $purpose, $visit_datetime, $email, $id]);
        } else {
            echo "may mali";
        }
        header("Location: ../tenantguestlog.php?message=guest_updated");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Database error: " . $e->getMessage());
    }
}

?>

<!DOCTYPE html>
<html>

<head>
    <title>Guest Pass</title>
    <style>
        body {
            font-family: Arial;
            text-align: center;
        }

        .card {
            border: 2px solid #000;
            border-radius: 10px;
            padding: 20px;
            width: 400px;
            margin: auto;
        }

        .logo {
            width: 100px;
            margin-bottom: 10px;
        }

        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: gray;
        }

        button {
            margin: 10px;
            padding: 10px 20px;
        }
    </style>
    <script>
        function backtoguestlog() {
            window.location.href = "../tenantguestlog.php";
        }
    </script>
</head>

<body>

    <div class="card">
        <img src="assets/logo.png" class="logo" alt="Logo">
        <h2>Guest Pass</h2>
        <form method="post">
            <p><strong>Name:</strong> <input type="text" name="name" value="<?= htmlspecialchars($guest['name']) ?>"></p>
            <p><strong>Email:</strong> <input type="email" name="email" value="<?= htmlspecialchars($guest['email']) ?>"></p>
            <p><strong>Purpose:</strong> <textarea name="purpose" id="purpose" rows="1" required></textarea></p>
            <p><strong>Date:</strong> <input type="datetime-local" name="visit_datetime" value="<?= htmlspecialchars($guest['visit_datetime']) ?>"></p>
            <img src="data:image/png;base64,<?= $qrBase64 ?>" alt="QR Code">

            <div class="footer">Please present this pass at the entrance. Thank you!</div>
            <button type="submit">Update</button>

        </form>
        <button onclick="backtoguestlog()">Back</button>
    </div>



</body>

</html>