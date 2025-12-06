<?php
require_once '../../tenant/guest_logging_process/vendor/autoload.php'; // Adjust path as needed
use Dompdf\Dompdf;
use Dompdf\Options;

include_once '../../includes/database.php'; // Adjust based on your file structure

$options = new Options();
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);

// Fetch landlord data
$query = "SELECT first_name, last_name, email, date_registered, phone_number, payment_priviledge, tenant_priviledge
          FROM userall WHERE role = 'Landlord'";
$result = mysqli_query($db_connection, $query);

// Build HTML content
$html = '
<h2 style="text-align:center;">Landlord User Accounts Report</h2>
<table border="1" cellspacing="0" cellpadding="5" width="100%">
    <thead>
        <tr style="background-color:#f2f2f2;">
            <th>Landlord Name</th>
            <th>Email Address</th>
            <th>Contact Number</th>
            <th>Date Registered</th>
            <th>Payment Privilege</th>
            <th>Tenant Privilege</th>
        </tr>
    </thead>
    <tbody>';

while ($row = mysqli_fetch_assoc($result)) {
    $html .= '<tr>
        <td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>
        <td>' . htmlspecialchars($row['email']) . '</td>
        <td>' . htmlspecialchars($row['phone_number']) . '</td>
        <td>' . htmlspecialchars($row['date_registered']) . '</td>
        <td>' . htmlspecialchars($row['payment_priviledge']) . '</td>
        <td>' . htmlspecialchars($row['tenant_priviledge']) . '</td>
    </tr>';
}

$html .= '
    </tbody>
</table>
<p style="text-align:right; margin-top:20px;">Generated on: ' . date("F j, Y, g:i a") . '</p>';

// Load and render PDF
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Output PDF to browser
$dompdf->stream("Landlord_User_Accounts.pdf", array("Attachment" => false));
exit;
