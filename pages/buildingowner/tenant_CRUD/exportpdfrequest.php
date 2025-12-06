<?php
require_once '../../tenant/guest_logging_process/vendor/autoload.php'; // adjust path to Dompdf
include_once '../../includes/database.php'; // your DB connection

use Dompdf\Dompdf;
use Dompdf\Options;

// Initialize Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);

// Fetch data
$sql = "SELECT a.*, b.update_message, r.room_number
        FROM maintenance_request a
        JOIN status_request b ON b.maintenance_request_id = a.maintenance_request_id
        JOIN room r ON r.room_id = a.room_id
        WHERE b.update_message != 'Archived'
        ORDER BY a.date_requested DESC";

$result = mysqli_query($db_connection, $sql);

// Build HTML content
$html = '
    <h2 style="text-align:center; font-family: Arial;">Maintenance Request and Complaints Report</h2>
    <table border="1" cellspacing="0" cellpadding="6" width="100%" style="border-collapse: collapse; font-family: Arial; font-size: 12px;">
        <thead style="background-color: #f2f2f2;">
            <tr>
                <th>Room</th>
                <th>Issue Category</th>
                <th>Description</th>
                <th>Status</th>
                <th>Date Requested</th>
            </tr>
        </thead>
        <tbody>';

// Loop through data
while ($row = mysqli_fetch_assoc($result)) {
    $html .= '
        <tr>
            <td>' . htmlspecialchars($row['room_number']) . '</td>
            <td>' . htmlspecialchars($row['issue_type']) . '</td>
            <td>' . htmlspecialchars($row['description']) . '</td>
            <td>' . htmlspecialchars($row['update_message']) . '</td>
            <td>' . htmlspecialchars($row['date_requested']) . '</td>
        </tr>';
}

$html .= '
        </tbody>
    </table>
    <br><p style="text-align:right; font-size:11px;">Generated on ' . date('F d, Y h:i A') . '</p>';

// Load to Dompdf
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();

// Output directly in browser
$dompdf->stream('Maintenance_Request_Report.pdf', ["Attachment" => false]);
exit;
