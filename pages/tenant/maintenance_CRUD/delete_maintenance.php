<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}

$id = $_GET['id'] ?? 0;
$status = $_GET['status'] ?? 0;


$sql1 = "DELETE FROM maintenance_request WHERE maintenance_request_id = $id";
$sql2 = "DELETE FROM status_request WHERE maintenance_request_id = $id";

if (mysqli_query($db_connection, $sql1) && mysqli_query($db_connection, $sql2)) {
    if ($status === 'dashboard') {

        header("Location: ../tenantdashboard.php?message=request_deleted");
        exit();
    } elseif ($status === 'maintenance') {

        header("Location: ../tenantmaintenance.php?message=request_deleted");
        exit();
    }
} else {
    die("Error deleting account: " . mysqli_error($db_connection));
}
