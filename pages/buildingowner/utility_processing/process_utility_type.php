<?php
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}

// Add new utility type
if (isset($_POST['add_utility'])) {
    $name = trim($_POST['utility_name']);
    $building_id = trim($_POST['building_id']);
    // Check if utility type already exists
    $check = $db_connection->prepare("SELECT * FROM utility_type WHERE utility_name = ?");
    $check->bind_param("s", $name);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        // Already exists → set warning in session
        header("Location: ../room_management.php?message=name_taken");
        exit();
    } else {
        // Safe to insert
        $stmt = $db_connection->prepare("INSERT INTO utility_type (building_id, utility_name) VALUES (?, ?)");
        $stmt->bind_param("is", $building_id, $name);
        if ($stmt->execute()) {
            $_SESSION['success'] = "Utility type '$name' added successfully.";
        } else {
            $_SESSION['error'] = "Error adding utility type.";
        }
    }
    header("Location: ../room_management.php?message=room_inserted");
    exit();
}

// Edit utility type
if (isset($_POST['edit_utility'])) {
    $id = $_POST['utility_type_id'];
    $name = trim($_POST['utility_name']);

    $check = $db_connection->prepare("SELECT * FROM utility_type WHERE utility_name = ?");
    $check->bind_param("s", $name);
    $check->execute();
    $result = $check->get_result();
    if ($result->num_rows > 0) {
        // Already exists → set warning in session
        header("Location: ../room_management.php?message=name_taken");
        exit();
    } else {
        $stmt = $db_connection->prepare("UPDATE utility_type SET utility_name=? WHERE utility_type_id=?");
        $stmt->bind_param("si", $name, $id);
        $stmt->execute();
    }


    header("Location: ../room_management.php?updated=1");
    exit();
}

// Delete utility type
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    $stmt = $db_connection->prepare("DELETE FROM utility_type WHERE utility_type_id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: ../room_management.php?deleted=1");
    exit();
}
