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
    // Collect form data safely
    $name = trim($_POST['name'] ?? '');
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $street = trim($_POST['street'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $number_of_floors = intval($_POST['number_of_floors'] ?? 0);

    // Basic validation
    if (empty($name)) {
        $_SESSION['error'] = "Building name is required.";
        header("Location: ../building_management.php");
        exit();
    }
    $check = $conn->prepare("SELECT * FROM building WHERE name = ?");
    $check->bind_param("s", $name);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // Already exists → set warning in session
        header("Location: ../building_management.php?building_message=name_taken");
        exit();
    } else {
        $stmt = $conn->prepare("
        INSERT INTO building 
        (name, latitude, longitude, street, barangay, city, province, postal_code, country, number_of_floors, building_is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");

        if (!$stmt) {
            $_SESSION['error'] = "Prepare failed: " . $conn->error;
            header("Location: ../building_management.php");
            exit();
        }

        $stmt->bind_param(
            "sddssssssi",
            $name,
            $latitude,
            $longitude,
            $street,
            $barangay,
            $city,
            $province,
            $postal_code,
            $country,
            $number_of_floors
        );
    }
    // Prepare SQL statement


    if ($stmt->execute()) {
        $_SESSION['success'] = "Building added successfully!";
        header("Location: ../building_management.php?message=building_inserted");
        exit();
    } else {
        $_SESSION['error'] = "Error inserting data: " . $stmt->error;
        header("Location: ../building_management.php");
        exit();
    }

    $stmt->close();
}
$conn->close();
