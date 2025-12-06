<?php
require_once '../../tenant/guest_logging_process/vendor/autoload.php'; // Adjust path as needed
use Dompdf\Dompdf;
use Dompdf\Options;

include_once("../../includes/database.php");



if (isset($_GET['contract_id'])) {
    $contract_id = intval($_GET['contract_id']);

    $sql = "SELECT a.*, b.first_name, b.last_name, c.room_number
            FROM contract a
            JOIN personal_info b ON b.user_id = a.user_id
            JOIN user d ON d.user_id = a.user_id
            JOIN room c ON d.room_id = c.room_id
            WHERE a.contract_id = '$contract_id' AND a.contract_status != 'Deleted'";

    $result = mysqli_query($db_connection, $sql);

    $sqladmin = "SELECT phone_number, email FROM userall WHERE role = 'Admin'";
    $resultadmin = mysqli_query($db_connection, $sqladmin);
    $rowadmin = mysqli_fetch_array($resultadmin);
    $email = $rowadmin['email'];
    $phone_number = $rowadmin['phone_number'];

    $tenants = [];
    $room_number = "";
    $start_date = "";
    $end_date = "";
    $terms = "";

    while ($row = mysqli_fetch_assoc($result)) {
        $room_number = $row['room_number'];
        $start_date = date("F j, Y", strtotime($row['start_date']));
        $end_date = date("F j, Y", strtotime($row['end_date']));
        $terms = $row['terms'];
        $tenants[] = $row['first_name'] . " " . $row['last_name'];
    }
    $logoPath = realpath(__DIR__ . "/../../images/Copy of Logo No Background.png");
    $termLabels = [
        'deposit_return' => '- Security deposit is refundable upon move-out, minus any damages.',
        'on_time_rent' => '- Rent must be paid on or before the due date each month.',
        'no_subleasing' => '- Subleasing without landlord approval is not allowed.',
        'utility_responsibility' => '- Tenant is responsible for all utility bills.',
        'notice_required' => '- A 30-day notice is required before moving out.',
        'property_care' => '- Tenant must maintain cleanliness and avoid damaging the property.'
    ];
    $termsArray = explode(', ', $terms);
    $displayTerms = array_map(fn($t) => $termLabels[$t] ?? $t, $termsArray);

    $html = "
    <style>
        @page { margin: 120px 50px 80px 50px; } /* top, right, bottom, left */
        header {
            position: fixed;
            top: -90px;
            left: 0;
            right: 0;
            height: 80px;
            text-align: center;
            border-bottom: 1px solid #ccc;
        }
        header img {
            height: 60px;
            vertical-align: middle;
        }
        header h1 {
            display: inline-block;
            vertical-align: middle;
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        footer {
            position: fixed;
            bottom: -60px;
            left: 0;
            right: 0;
            height: 50px;
            text-align: center;
            font-size: 12px;
            color: #555;
            border-top: 1px solid #ccc;
            line-height: 1.4em;
        }
    </style>
    <h2 style='text-align:center; margin-top:20px;'>Residential Lease Agreement</h2>
    <p><strong>Room:</strong> $room_number</p>
    <p><strong>Tenants:</strong> " . implode(', ', $tenants) . "</p>
    <p><strong>Start Date:</strong> $start_date</p>
    <p><strong>End Date:</strong> $end_date</p>
    <h4>Terms & Conditions</h4>
    <ul><li>" . implode("</li><li>", $displayTerms) . "</li></ul>
    <hr><br><br>
    <table width='100%' style='text-align:center; margin-top:40px;'>
        <tr>
            <td>__________________________<br><b>Owner / Landlord</b></td>
            <td>__________________________<br><b>Date</b></td>
        </tr>
    </table>
    <br><br>
    <table width='100%' style='text-align:center;'>
<header>
    <h1>Tita Ria's Apartment</h1>
</header>
        <footer>
        Contact us: $email | $phone_number
    </footer>";
    foreach ($tenants as $t) {
        $html .= "<tr><td>__________________________<br><b>$t (Tenant)</b></td></tr><br>";
    }
    $html .= "</table>";

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("contract_room_$room_number.pdf", ["Attachment" => false]);
}
