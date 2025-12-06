<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}
require_once '../../tenant/guest_logging_process/lib/phpqrcode/qrlib.php';
require_once '../../tenant/guest_logging_process/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
$user_id = $_SESSION['user_id'];


$warning = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $selectedRoom = $_POST['desired_room'] ?? null;
    $start_date = $_POST['start_date'] ?? null;
    $payment_method = $_POST['payment_method'] ?? null;
    $duration = (int) ($_POST['duration'] ?? 0);
    $status = 'Active';

    if (!$selectedRoom || !$start_date || $duration <= 0) {
        die("Missing or invalid input.");
    }


    $startDateObj = new DateTime($start_date);
    $startDateObj->modify("+$duration months");
    $end_date = $startDateObj->format('Y-m-d');

    $startDateObjDueDate = new DateTime($start_date);
    $startDateObjDueDate->modify("+1 months");
    $due_date = $startDateObjDueDate->format('Y-m-d');

    $termsArray = isset($_POST['terms']) ? $_POST['terms'] : [];
    $otherTerm = trim($_POST['other_term'] ?? '');

    if (!empty($otherTerm)) {
        $termsArray[] = $otherTerm;
    }
    $terms = implode(', ', $termsArray);
    try {
        $pdo->beginTransaction();

        $query = $pdo->prepare("SELECT user_id FROM userall WHERE desired_room = ? AND account_status = 'Pending' AND role = 'Tenant'");
        $query->execute([$selectedRoom]);
        $userIds = $query->fetchAll(PDO::FETCH_COLUMN);

        $query_room = $pdo->prepare("SELECT room_amount, capacity FROM room WHERE room_id = ?");
        $query_room->execute([$selectedRoom]);
        $room_data = $query_room->fetch(PDO::FETCH_ASSOC);

        if ($room_data) {
            $due_for_first_payment = $room_data['room_amount'] / $room_data['capacity'];
        } else {
            $due_for_first_payment = 0;
        }

        if (empty($userIds)) {
            throw new Exception("No tenants found for the selected room.");
        }

        $insert = $pdo->prepare("INSERT INTO contract (user_id, start_date, end_date, terms, status) 
                                 VALUES (?, ?, ?, ?, ?)");
        $insert_payment = $pdo->prepare("INSERT INTO payment (contract_id, amount_due, paid_on, due_date, payment_method) 
                                 VALUES (?, ?, ?, ?, ?)");
        $insert_payment_status_paid = $pdo->prepare("INSERT INTO payment_status (payment_id, status, status_date) 
                                 VALUES (?, 'PAID', ?)");
        $insert_payment_status_unpaid = $pdo->prepare("INSERT INTO payment_status (payment_id, status, status_date) 
                                 VALUES (?, 'UNPAID', ?)");


        foreach ($userIds as $user_id) {
            $insert->execute([$user_id, $start_date, $end_date, $terms, $status]);
            $contract_id_insert = $pdo->lastInsertId();


            $insert_payment->execute([$contract_id_insert, $due_for_first_payment, $start_date, $start_date, $payment_method]);
            $payment_id_insert = $pdo->lastInsertId();
            $insert_payment_status_paid->execute([$payment_id_insert, $start_date]);

            $insert_payment->execute([$contract_id_insert, $due_for_first_payment,  null, $due_date, null]);
            $payment_id_insert = $pdo->lastInsertId();
            $insert_payment_status_unpaid->execute([$payment_id_insert, $start_date]);
        }

        $update_room = $pdo->prepare("UPDATE room SET status = 'Occupied' WHERE room_id = ?");
        $update_room->execute([$selectedRoom]);

        $update = $pdo->prepare("UPDATE userall SET account_status = 'Active', room_id = ? WHERE user_id = ? AND role = 'Tenant'");
        foreach ($userIds as $user_id) {
            $update->execute([$selectedRoom, $user_id]);
        }

        $pdo->commit();
        // Fetch user info for email
        $emailQuery = $pdo->prepare("SELECT first_name, last_name, email FROM userall WHERE desired_room = ? AND account_status = 'Active' AND role = 'Tenant'");
        $emailQuery->execute([$selectedRoom]);
        $usersForEmail = $emailQuery->fetchAll(PDO::FETCH_ASSOC);

        foreach ($usersForEmail as $user) {
            $mail = new PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'adrianfernando2626@gmail.com';
                $mail->Password   = 'cxwqqwktqevyogmt';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                // Recipients
                $mail->setFrom('adrianfernando2626@gmail.com', 'Verification System');
                $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Contract Created & Verification Success';
                $mail->Body    = "Dear " . htmlspecialchars($user['first_name']) . ",<br><br>" .
                    "Your housing contract has been successfully created.<br>" .
                    "You may now log in to your account: <a href='localhost/capstone/pages/login.php'>Click here to login</a><br><br>" .
                    "<strong>This is an automated system message — please do not reply.</strong>";

                $mail->send();
            } catch (Exception $e) {
                // Optional: log or display failed emails
                error_log("Email to {$user['email']} failed: " . $mail->ErrorInfo);
            }
        }

        header("Location: ../contractmanage.php?message=contract_inserted");
        exit();
    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Error inserting contracts: " . $e->getMessage());
    }
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Document</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            padding: 2rem;
        }

        h5 {
            font-size: 22px;
            margin-bottom: 1rem;
        }

        form {
            background-color: #fff;
            padding: 2rem;
            max-width: 600px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .mb-3>div {
            margin-bottom: 1rem;
        }

        label {
            display: block;
            margin-bottom: 0.4rem;
            font-weight: bold;
        }

        input[type="number"],
        textarea,
        input[type="date"],
        select {
            width: 100%;
            padding: 0.6rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box;
        }

        textarea {
            resize: vertical;
            height: 100px;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 0.6rem 1.2rem;
            font-size: 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container">
        <h5>CREATING NEW CONTRACT</h5>
        <?php if (!empty($warning)): ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($warning); ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <div>
                    <label for="start_date">Start Date of the Contract</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="duration">Duration</label>
                    <input type="number" id="duration" name="duration" placeholder="Enter Number of Months" value="<?php echo htmlspecialchars($_POST['duration'] ?? ''); ?>" required>
                </div>
                <div>
                    <label for="terms">Terms</label>
                    <small class="text-muted d-block mb-2">Select the terms to include in this contract. You may also add a custom term below.</small>
                    <label><input type="checkbox" name="terms[]" value="deposit_return"> Security deposit is refundable upon move-out, minus any damages.</label><br>
                    <label><input type="checkbox" name="terms[]" value="on_time_rent"> Rent must be paid on or before the due date each month.</label><br>
                    <label><input type="checkbox" name="terms[]" value="no_subleasing"> Subleasing without landlord approval is not allowed.</label><br>
                    <label><input type="checkbox" name="terms[]" value="utility_responsibility"> Tenant is responsible for all utility bills.</label><br>
                    <label><input type="checkbox" name="terms[]" value="notice_required"> A 30-day notice is required before moving out.</label><br>
                    <label><input type="checkbox" name="terms[]" value="property_care"> Tenant must maintain cleanliness and avoid damaging the property.</label><br><br>

                    <label for="other_term">Other (custom term)</label>
                    <input type="text" name="other_term" id="other_term" class="form-control" placeholder="Enter a custom term (optional)">
                </div>

                <label for="desired_room" class="form-label">Select Desired Room (Grouped by Reserved Tenants)</label>
                <select id="desired_room" name="desired_room" style="width: 100%;" required>
                    <option value="" disabled selected>-- Select a Desired Room Group --</option>
                    <?php
                    $query = mysqli_query($db_connection, 'SELECT a.desired_room, a.first_name, a.last_name, b.room_number, a.role
                                        FROM userall a
                                        JOIN room b ON a.desired_room = b.room_id
                                        WHERE a.account_status = "Pending" AND a.role = "Tenant"');

                    $groupedRooms = [];

                    while ($row = mysqli_fetch_assoc($query)) {
                        $roomId = $row['desired_room'];
                        $roomNumber = $row['room_number'];
                        $fullName = $row['first_name'] . ' ' . $row['last_name'];

                        if (!isset($groupedRooms[$roomId])) {
                            $groupedRooms[$roomId] = [
                                'room_number' => $roomNumber,
                                'names' => [],
                            ];
                        }

                        if (!in_array($fullName, $groupedRooms[$roomId]['names'])) {
                            $groupedRooms[$roomId]['names'][] = $fullName;
                        }
                    }

                    foreach ($groupedRooms as $roomId => $data) {
                        $label = 'Room ' . $data['room_number'] . ': ' . implode(', ', $data['names']);
                        echo '<option value="' . $roomId . '">' . htmlspecialchars($label) . '</option>';
                    }
                    ?>
                </select>

                <select id="payment_method" name="payment_method" style="width: 100%;" required>
                    <option value="" disabled selected>-- Select the payment method for advance payment and maintenance deposit--</option>
                    <option value="GCASH">GCASH</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>
            </div>


            <button type="submit" class="btn btn-primary" name="submit">Submit</button>
            <a href="../contractmanage.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

</body>

</html>