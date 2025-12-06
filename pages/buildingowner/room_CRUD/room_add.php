<?php

session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$database = "apartment";

$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_no = $_POST['room_no'] ?? '';
    $room_price = $_POST['room_price'] ?? 0;
    $capacity = $_POST['capacity'] ?? 0;
    $floor_number = $_POST['floor_number'] ?? 0;
    $building_id = $_SESSION['id'] ?? 1;
    $status = 'Available'; // Set default

    // Check if room already exists
    $stmt = $conn->prepare("SELECT room_number FROM room WHERE room_number = ?");
    $stmt->bind_param("s", $room_no); // "s" = string
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        header("Location: ../room_management.php?message=room_number_taken");
        exit();
    }
    // Optional: validate input further

    $stmt = $conn->prepare("INSERT INTO room (building_id, floor_number, room_amount, room_number, capacity, room_status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iidsss", $building_id, $floor_number, $room_price, $room_no, $capacity,  $status);

    if ($stmt->execute()) {
        header("Location: ../room_management.php?message=room_inserted");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
