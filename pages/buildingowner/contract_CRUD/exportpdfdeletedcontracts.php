
<?php
require_once '../../tenant/guest_logging_process/vendor/autoload.php'; // Adjust path as needed
use Dompdf\Dompdf;
use Dompdf\Options;

include_once '../../includes/database.php'; // adjust path as needed

// Fetch deleted contracts with tenant info
$sql = "SELECT a.*, b.first_name, b.last_name
        FROM contract a
        JOIN userall b ON b.user_id = a.user_id
        WHERE a.contract_status = 'Deleted'";
$result_query = mysqli_query($db_connection, $sql);

// Term mapping
$termLabels = [
    'deposit_return' => '- Security deposit is refundable upon move-out, minus any damages.',
    'on_time_rent' => '- Rent must be paid on or before the due date each month.',
    'no_subleasing' => '- Subleasing without landlord approval is not allowed.',
    'utility_responsibility' => '- Tenant is responsible for all utility bills.',
    'notice_required' => '- A 30-day notice is required before moving out.',
    'property_care' => '- Tenant must maintain cleanliness and avoid damaging the property.'
];

// Build the PDF HTML
$html = '
<h2 style="text-align: center;">Deleted Contracts</h2>
<table border="1" cellspacing="0" cellpadding="5" width="100%">
    <thead style="background-color: #f2f2f2;">
        <tr>
            <th>Tenant Name</th>
            <th>Start Date</th>
            <th>Lease End Date</th>
            <th>Terms</th>
        </tr>
    </thead>
    <tbody>';

while ($row = mysqli_fetch_assoc($result_query)) {
    $termsArray = explode(', ', $row['terms']);
    $displayTerms = array_map(function ($term) use ($termLabels) {
        return $termLabels[$term] ?? htmlspecialchars($term);
    }, $termsArray);
    $string_start_date = date("F j, Y", strtotime($row['start_date']));
    $string_end_date = date("F j, Y", strtotime($row['end_date']));

    $html .= '<tr>  
        <td>' . htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) . '</td>
        <td>' . htmlspecialchars($string_start_date) . '</td>
        <td>' . htmlspecialchars($string_end_date) . '</td>
        <td>' . implode('<br>', $displayTerms) . '</td>
    </tr>';
}

$html .= '</tbody></table>';

// Generate PDF
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("deleted_contracts_" . date("Y-m-d") . ".pdf", ["Attachment" => false]);
exit;
