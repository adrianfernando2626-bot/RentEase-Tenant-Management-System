<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}
require_once '../../tenant/guest_logging_process/lib/phpqrcode/qrlib.php';
require_once '../../tenant/guest_logging_process/vendor/autoload.php';

use Dompdf\Dompdf;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
$loggedInUserId = $_SESSION['user_id']; // keep session user safe

$warning = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $selectedRoom = $_POST['desired_room'] ?? null;
    $start_date = $_POST['start_date'] ?? null;
    $payment_method = $_POST['payment_method'] ?? null;
    $duration = (int) ($_POST['duration'] ?? 0);
    $contract_status = 'Active';

    if (!$selectedRoom || !$start_date || $duration <= 0) {
        die("Missing or invalid input.");
    }

    if ($duration === 6) {
        $duration_value = '6 months';
    } elseif ($duration === 12) {
        $duration_value = '1 year';
    } elseif ($duration === 24) {
        $duration_value = '2 years';
    }

    // Dates
    $startDateObj = new DateTime($start_date);
    $startDateObj->modify("+$duration months");
    $end_date = $startDateObj->format('Y-m-d');

    $startDateObjDueDate = new DateTime($start_date);
    $startDateObjDueDate->modify("+1 months");
    $due_date = $startDateObjDueDate->format('Y-m-d');

    // Terms
    $termsArray = isset($_POST['terms']) ? $_POST['terms'] : [];
    $otherTerm = trim($_POST['other_term'] ?? '');
    if (!empty($otherTerm)) {
        $termsArray[] = $otherTerm;
    }
    $terms = implode(', ', $termsArray);

    try {
        $pdo->beginTransaction();

        // Fetch tenants with pending contract_status for the room
        $query = $pdo->prepare("
            SELECT user_id, CONCAT(first_name, ' ', last_name) AS name 
            FROM userall 
            WHERE desired_room = ? 
              AND account_status = 'Pending' 
              AND role = 'Tenant'
        ");
        $query->execute([$selectedRoom]);
        $tenants = $query->fetchAll(PDO::FETCH_ASSOC);

        // Room data
        $query_room = $pdo->prepare("SELECT room_amount, capacity, room_number FROM room WHERE room_id = ?");
        $query_room->execute([$selectedRoom]);
        $room_data = $query_room->fetch(PDO::FETCH_ASSOC);

        $roomCapacity = (int) ($room_data['capacity'] ?? 0);
        $room_number  = (int) ($room_data['room_number'] ?? 0);

        // Count reserved tenants
        $query_tenant_count = $pdo->prepare("SELECT COUNT(*) FROM userall WHERE desired_room = ? AND role = 'Tenant'");
        $query_tenant_count->execute([$selectedRoom]);
        $tenantCount = (int) $query_tenant_count->fetchColumn();

        if ($tenantCount === $roomCapacity) {
            if ($room_data) {
                $due_for_first_payment = $room_data['room_amount'] / $room_data['capacity'];
                $total_balance = $room_data['room_amount']  * $duration;
                $balance = $total_balance - $room_data['room_amount'];
            } else {
                $due_for_first_payment = 0;
                $balance = 0;
            }

            if (empty($tenants)) {
                throw new Exception("No tenants found for the selected room.");
            }


            $insert_contract = $pdo->prepare("
                INSERT INTO contract (user_id, start_date, end_date, terms, duration, contract_status, update_status) 
                VALUES (?, ?, ?, ?, ?, ?, 'Not Approved')
            ");
            $insert_payment = $pdo->prepare("
                INSERT INTO payment (contract_id, paid_on, due_date, payment_method) 
                VALUES (?, ?, ?, ?)
            ");
            $insert_rent_payment = $pdo->prepare("
                INSERT INTO rent_payment (payment_id, expected_amount_due, tenant_payment, balance) 
                VALUES (?, ?, ?, ?)
            ");
            $insert_payment_status_paid = $pdo->prepare("
                INSERT INTO payment_status (payment_id, status, status_date, is_active) 
                VALUES (?,'PAID', ?, 1)
            ");
            $insert_payment_status_unpaid = $pdo->prepare("
                INSERT INTO payment_status (payment_id, status, status_date, is_active) 
                VALUES (?,'UNPAID', ?, 1)
            ");

            foreach ($tenants as $tenant) {
                $tenantId = $tenant['user_id'];
                $tenantName = $tenant['name'];


                $checkExpired = $pdo->prepare("SELECT contract_id FROM contract WHERE user_id = ? AND contract_status = 'Expired'");
                $checkExpired->execute([$tenantId]);
                $existingContract = $checkExpired->fetch(PDO::FETCH_ASSOC);

                $qr_token = bin2hex(random_bytes(10));
                $stmt = $pdo->prepare("INSERT INTO guest_pass (user_id, qr_token) VALUES (?, ?)");
                $stmt->execute([$tenantId, $qr_token]);
                $guest_id = $pdo->lastInsertId();

                $qr_content = "TOKEN:$qr_token";
                $qr_path = "../../tenant/guest_logging_process/qrcodes/qr_$guest_id.png";
                QRcode::png($qr_content, $qr_path, QR_ECLEVEL_L, 4);

                $qr_base64 = base64_encode(file_get_contents($qr_path));
                $qr_image = 'data:image/png;base64,' . $qr_base64;

                $dompdf = new Dompdf();
                $html = "
                    <h2>Guest Pass</h2>
                    <p><strong>Name:</strong> $tenantName</p>
                    <p><strong>Room:</strong> $room_number</p>
                    <img src='$qr_image' alt='QR Code' style='width:150px;'>
                ";
                $dompdf->loadHtml($html);
                $dompdf->setPaper('A5', 'portrait');
                $dompdf->render();
                $pdf_output = $dompdf->output();
                file_put_contents("../../tenant/guest_logging_process/pdfs/guest_pass_$guest_id.pdf", $pdf_output);

                if ($existingContract) {
                    $updateContract = $pdo->prepare("
                        UPDATE contract 
                        SET start_date = ?, end_date = ?, terms = ?, contract_status = ? 
                        WHERE contract_id = ?
                    ");
                    $updateContract->execute([$start_date, $end_date, $terms, $contract_status, $existingContract['contract_id']]);
                    $contract_id_insert = $existingContract['contract_id'];
                } else {
                    $insert_contract->execute([$tenantId, $start_date, $end_date, $terms, $duration_value, $contract_status]);
                    $contract_id_insert = $pdo->lastInsertId();
                }

                // First payment (advance)
                $insert_payment->execute([$contract_id_insert, $start_date, $start_date, $payment_method]);
                $payment_id_insert = $pdo->lastInsertId();
                $insert_rent_payment->execute([$payment_id_insert, 0, $due_for_first_payment, $balance]);
                $insert_payment_status_paid->execute([$payment_id_insert, $start_date]);

                // Next month payment (due)
                $insert_payment->execute([$contract_id_insert, null, $due_date, null]);
                $payment_id_insert = $pdo->lastInsertId();
                $insert_rent_payment->execute([$payment_id_insert, $due_for_first_payment, 0, $balance]);
                $insert_payment_status_unpaid->execute([$payment_id_insert, $start_date]);
            }

            // Update room contract_status
            $update_room = $pdo->prepare("UPDATE room SET room_status = 'Occupied' WHERE room_id = ?");
            $update_room->execute([$selectedRoom]);

            // Update tenants to Active
            $update_user = $pdo->prepare("UPDATE user SET account_status = 'Active', room_id = ? WHERE user_id = ? AND role = 'Tenant'");
            foreach ($tenants as $tenant) {
                $update_user->execute([$selectedRoom, $tenant['user_id']]);
            }

            $pdo->commit();

            // Send emails
            $emailQuery = $pdo->prepare("
                SELECT first_name, last_name, email 
                FROM userall 
                WHERE desired_room = ? 
                  AND account_status = 'Active' 
                  AND role = 'Tenant'
            ");
            $emailQuery->execute([$selectedRoom]);
            $usersForEmail = $emailQuery->fetchAll(PDO::FETCH_ASSOC);

            foreach ($usersForEmail as $user) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'adrianfernando2626@gmail.com';
                    $mail->Password   = 'cxwqqwktqevyogmt';
                    $mail->SMTPSecure = 'tls';
                    $mail->Port       = 587;

                    $mail->setFrom('adrianfernando2626@gmail.com', 'Verification System');
                    $mail->addAddress($user['email'], $user['first_name'] . ' ' . $user['last_name']);

                    $mail->isHTML(true);
                    $mail->Subject = 'Contract Created & Verification Success';
                    $mail->Body    = "Dear " . htmlspecialchars($user['first_name']) . ",<br><br>" .
                        "Your housing contract has been successfully created.<br>" .
                        "You may now log in to your account: <a href='localhost/capstone/pages/login.php'>Click here to login</a><br><br>" .
                        "<strong>This is an automated system message — please do not reply.</strong>";

                    $mail->send();
                } catch (Exception $e) {
                    error_log("Email to {$user['email']} failed: " . $mail->ErrorInfo);
                }
            }

            header("Location: ../contractmanage.php?message=contract_inserted");
            exit();
        } else {
            $warning = "Room reservation does not yet meet the total capacity of the room. Currently reserved: $tenantCount / $roomCapacity.";
        }
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
    <title>Adding of Contract</title>
    <link rel="stylesheet" href="../../css/addcontent.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
</head>

<body>
    <main class="main-content">
        <div class="form-section">
            <div class="form-header">
                <i class="fas fa-clipboard-list fa-2x"></i>
                <h1>Least Agreement</h1>
            </div>
            <?php if (!empty($warning)): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($warning); ?></div>
            <?php endif; ?>
            <form class="rule-form" method="post">
                <label for="start-date">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($_POST['start_date'] ?? ''); ?>" required />

                <label for="duration">End of Contract</label>
                <select name="duration" id="duration">
                    <option value="" selected disabled>-- Select Duration --</option>
                    <option value="6">6 months</option>
                    <option value="12">1 year</option>
                    <option value="24">2 years</option>
                </select>

                <h3 style="margin-top: 2rem; font-size: 1.1rem;">Terms</h3>
                <p style="font-size: 0.9rem; margin-bottom: 1rem;">
                    Select the terms to include in this contract. You may also add a custom term below.
                </p>

                <div class="checkbox-group">
                    <label><input type="checkbox" name="terms[]" value="deposit_return" checked> <span>Security deposit is refundable upon move-out, minus any damages.</span></label>
                    <label><input type="checkbox" name="terms[]" value="on_time_rent" checked><span> Rent must be paid on or before the due date each month.</span></label>
                    <label><input type="checkbox" name="terms[]" value="no_subleasing" checked> <span>Subleasing without landlord approval is not allowed.</span></label>
                    <label><input type="checkbox" name="terms[]" value="utility_responsibility" checked><span> Tenant is responsible for all utility bills.</span></label>
                    <label><input type="checkbox" name="terms[]" value="notice_required" checked><span> A 30-day notice is required before moving out.</span></label>
                    <label><input type="checkbox" name="terms[]" value="property_care" checked><span> Tenant must maintain cleanliness and avoid damaging the property.</span></label>
                </div>

                <label for="custom-term">Other (custom term)</label>
                <input type="text" name="other_term" id="other_term" class="form-control" placeholder="Enter a custom term (optional)">

                <label for="desired_room" class="form-label">Select Reserved Room (Grouped by Tenants)</label>
                <select id="desired_room" name="desired_room" style="width: 100%;" required>
                    <option value="" disabled selected>-- Select a Reserved Room Group --</option>
                    <?php
                    $query = mysqli_query($db_connection, 'SELECT a.desired_room, a.first_name, a.last_name, b.room_number, a.role, b.capacity
                                        FROM userall a
                                        JOIN room b ON a.desired_room = b.room_id
                                        WHERE a.account_status = "Pending" AND a.role = "Tenant"');

                    $groupedRooms = [];
                    while ($row = mysqli_fetch_assoc($query)) {
                        $roomId = $row['desired_room'];
                        $roomNumber = $row['room_number'];
                        $fullName = $row['first_name'] . ' ' . $row['last_name'];
                        $capacity = $row['capacity'];

                        $query_desired_room = mysqli_query($db_connection, 'SELECT COUNT(desired_room) AS total_desired_room
                        FROM userall WHERE desired_room = ' . $roomId . ' GROUP BY desired_room');
                        $row_desired_room = mysqli_fetch_assoc($query_desired_room);
                        $desired_room = $row_desired_room['total_desired_room'];

                        if (!isset($groupedRooms[$roomId])) {
                            $groupedRooms[$roomId] = [
                                'room_number' => $roomNumber,
                                'desired_room' => $desired_room,
                                'capacity' => $capacity,
                                'names' => [],
                            ];
                        }
                        if (!in_array($fullName, $groupedRooms[$roomId]['names'])) {
                            $groupedRooms[$roomId]['names'][] = $fullName;
                        }
                    }

                    foreach ($groupedRooms as $roomId => $data) {
                        $label = 'Room ' . $data['room_number'] . ': ' . 'Reservation Capacity: ' . $data['desired_room'] . ' /' . $data['capacity'] . ' | Names: [ ' . implode(', ', $data['names']) . ' ]';

                        if ($data['desired_room'] != $data['capacity']) {
                            $disabled_button = "disabled";
                            $showWarning = '<span style="color: red;">(Room Capacity is not met)</span>';
                        }
                        echo '<option value="' . $roomId . '" ' . $disabled_button . '>' . htmlspecialchars($label) . ' ' . $showWarning . '</option>';
                    }
                    ?>
                </select>

                <label for="payment_method" class="form-label">Select the payment method for advance payment and maintenance deposit</label>
                <select id="payment_method" name="payment_method" style="width: 100%;" required>
                    <option value="" disabled selected>-- Select the payment method for advance payment and maintenance deposit--</option>
                    <option value="GCASH">GCASH</option>
                    <option value="Cash">Cash</option>
                    <option value="Bank Transfer">Bank Transfer</option>
                </select>
                <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="add-btn" name="submit">Add</button>
                    <a href="../contractmanage.php" class="back-btn">Cancel</a>
                </div>
            </form>
        </div>
    </main>
    <script src="../../js/script.js"></script>
    <?php if (isset($_GET['message']) && $_GET['message'] === 'renewal_sucess'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Contracts Renewal',
                text: 'You can now set their new start date, duration and other details.',
                confirmButtonText: 'OK'
            });
        </script>
    <?php endif; ?>
</body>

</html>