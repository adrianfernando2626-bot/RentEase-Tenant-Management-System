<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../includes/database.php')) {
    include_once('../includes/database.php');
}

$warning = "";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$image = '';
$role = "";


if (isset($_POST["create"])) {
    $img_name = $_FILES['my_image']['name'];
    $img_size = $_FILES['my_image']['size'];
    $tmp_name = $_FILES['my_image']['tmp_name'];
    $error = $_FILES['my_image']['error'];

    $firstname = $_POST['firstname'];
    $lastname = $_POST['lastname'];
    $middlename = $_POST['middlename'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $username = $_POST['username'];
    $birthdate = $_POST['birthdate'];
    $password = $_POST['password'];
    $address = $_POST['address'];
    $desired_room = $_POST['desired_room'];
    $phonenumber = $_POST['phonenumber'];
    $date = date("Y-m-d");
    $account_status = "Pending";


    $querycheckemail = "SELECT * FROM userall WHERE email = '$email'";
    $rscheckemail = mysqli_query($db_connection, $querycheckemail);
    $check_rs = mysqli_fetch_array($rscheckemail);

    $querycheckemail2 = "SELECT COUNT(desired_room) as total_desired_room FROM user WHERE desired_room = '$desired_room'";
    $rscheckemail2 = mysqli_query($db_connection, $querycheckemail2);
    $check_rs2 = mysqli_fetch_array($rscheckemail2);

    $querycheckemail1 = "SELECT capacity FROM room WHERE room_id = '$desired_room'";
    $rscheckemail1 = mysqli_query($db_connection, $querycheckemail1);
    $check_rs1 = mysqli_fetch_array($rscheckemail1);

    if ($check_rs1['capacity'] <= $check_rs2['total_desired_room']) {
        $warning = 'The reservation for that room is not.';
    } else {
        if (!empty($_POST['role'])) {

            $role = $_POST['role'];

            if (strlen($phonenumber) !== 13) {
                $warning = 'Please Enter Valid Cellphone number.';
            } else {
                if (substr($phonenumber, 0, 4) === '+639') {
                    if (strlen($password) < 8) {
                        $warning = 'Password must be at least 8 characters long.';
                    } else {


                        if (empty($check_rs['email'])) {

                            if ($error === 0) {

                                if ($img_size > 125000) {
                                    $warning = 'File size too large';
                                } else {
                                    $img_ex = pathinfo($img_name, PATHINFO_EXTENSION);
                                    $img_ex_lc = strtolower($img_ex);
                                    $allowed_exs = array("jpg", "jpeg", "png");

                                    if (in_array($img_ex_lc, $allowed_exs)) {
                                        $new_image_name = uniqid("IMG-", true) . "." . $img_ex_lc;
                                        $img_upload_path = 'images/' . $new_image_name;
                                        move_uploaded_file($tmp_name, $img_upload_path);


                                        try {

                                            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                                            $pdo->beginTransaction();

                                            $sql1 = "INSERT INTO user (desired_room, role, date_registered, account_status) 
                                       VALUES (?, ?, ?, ?)";

                                            $stmt = $pdo->prepare($sql1);
                                            $stmt->execute([$desired_room, $role, $date, $account_status]);
                                            $user_id = $pdo->lastInsertId();

                                            $sql2 = "INSERT INTO personal_info (
                                                    user_id, first_name, last_name, middle_name, birthdate,
                                                    email, gender, address, phone_number, img
                                                ) 
                                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                                            $stmt = $pdo->prepare($sql2);
                                            $stmt->execute([$user_id, $firstname, $lastname, $middlename, $birthdate, $email, $gender, $address, $phonenumber, $new_image_name]);

                                            $sql3 = "INSERT INTO credential (user_id, username, password) VALUES (?,?,?)";
                                            $stmt = $pdo->prepare($sql3);
                                            $stmt->execute([$user_id, $username, $password]);

                                            $pdo->commit();

                                            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                                            echo "<script>
                                        document.addEventListener('DOMContentLoaded', function() {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Successfully Saved',
                                            text: 'You can now log in'
                                        }).then(function() {
                                            window.location.href = 'login.php';
                                        });
                                    });
                                    </script>";
                                        } catch (PDOException $e) {
                                            if ($pdo->inTransaction()) {
                                                $pdo->rollBack();
                                            }
                                            die("Database error: " . $e->getMessage());
                                        }
                                    } else {
                                        $warning = 'It only accept Images';
                                    }
                                }
                            } else {

                                $warning = 'Please Upload Profile Picture';
                            }
                        } else {

                            $warning = 'This email is already taken';
                        }
                    }
                } else {
                    $warning = 'Please insert a valid phone number that starts with +639';
                }
            }
        } else {
            $warning = "Please select a role.";
        }
    }
}
?>


<!doctype html>
<html lang="en">

<head>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign Up</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="../css/signup.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap');

        body {
            background: linear-gradient(135deg, #4a90e2, #50e3c2);
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: white;
            width: 700px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            padding: 40px;
        }

        h1 {
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
            color: #333;
        }

        form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        form input[type="text"],
        form input[type="email"],
        form input[type="password"],
        form input[type="number"],
        form input[type="date"],
        form select,
        form input[type="file"] {
            padding: 10px 12px;
            font-size: 15px;
            border: 1.8px solid #ccc;
            border-radius: 6px;
            width: 100%;
        }

        form input:focus,
        form select:focus {
            border-color: #4a90e2;
            outline: none;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #333;
        }

        .full-width {
            grid-column: span 2;
        }

        .gender-group {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-top: 10px;
        }

        .gender-group input {
            margin-right: 5px;
        }

        button,
        #btn {
            grid-column: span 2;
            padding: 12px;
            background-color: #4a90e2;
            color: white;
            font-weight: bold;
            font-size: 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        #btn:hover {
            background-color: #357ABD;
        }

        .form-footer {
            grid-column: span 2;
            text-align: center;
            margin-top: 20px;
        }

        .form-footer a {
            color: #4a90e2;
            text-decoration: none;
            font-weight: 600;
        }

        .form-footer a:hover {
            text-decoration: underline;
        }

        .error {
            grid-column: span 2;
            color: red;
            text-align: center;
            font-weight: 500;
            margin-bottom: 15px;
        }
    </style>

    </style>
</head>

<body>
    <div class="container">
        <h1>Sign Up</h1>
        <form name="form" action="signup.php" method="post" enctype="multipart/form-data" id="formal">
            <?php if (!empty($warning)): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($warning); ?></div>
            <?php endif; ?>

            <div class="full-width">
                <label for="role">Select Your Role</label>
                <select id="role" name="role" required>
                    <option value="" disabled selected>Select your role</option>
                    <option value="Tenant">Tenant</option>
                    <option value="Landlord">Landlord</option>
                </select>
            </div>

            <div>
                <label for="firstname">First Name</label>
                <input type="text" id="firstname" name="firstname" placeholder="Enter Firstname" value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>" required>
            </div>

            <div>
                <label for="lastname">Last Name</label>
                <input type="text" id="lastname" name="lastname" placeholder="Enter Lastname" value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>" required>
            </div>

            <div>
                <label for="middlename">Middle Name</label>
                <input type="text" id="middlename" name="middlename" placeholder="Enter Middlename" value="<?php echo htmlspecialchars($_POST['middlename'] ?? ''); ?>">
            </div>

            <div>
                <label for="birthdate">Birthdate</label>
                <input type="date" id="birthdate" name="birthdate" value="<?php echo htmlspecialchars($_POST['birthdate'] ?? ''); ?>" required>
            </div>

            <div>
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="Enter Email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div>
                <label>Gender</label>
                <div class="gender-group">
                    <label><input type="radio" name="gender" value="male" required> Male</label>
                    <label><input type="radio" name="gender" value="female" required> Female</label>
                    <label><input type="radio" name="gender" value="other" required> Other</label>
                </div>
            </div>

            <div>
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter Username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
            </div>

            <div>
                <label for="password">Create Password</label>
                <input type="password" id="password" name="password" placeholder="Create Password" value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>" required>
            </div>

            <div>
                <label for="address">Address</label>
                <input type="text" id="address" name="address" placeholder="Enter Address" value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" required>
            </div>

            <div>
                <label for="phonenumber">Phone Number</label>
                <input type="text" id="phonenumber" name="phonenumber" placeholder="639..." value="<?php echo htmlspecialchars($_POST['phonenumber'] ?? '+639'); ?>" required>
            </div>

            <div class="full-width">
                <label for="my_image">Profile Picture</label>
                <input type="file" name="my_image" id="my_image">
            </div>
            <div>
                <label for="room_list" class="form-label">Available Rooms:</label>
                <select id="desired_room" name="desired_room" style="width: 100%;">
                    <option value="" disabled selected>-- Select a Room --</option>
                    <?php

                    $rs = mysqli_query($db_connection, 'SELECT * from room WHERE status = "Available"');
                    while ($rw = mysqli_fetch_array($rs)) {
                        echo '<option value="' . $rw['room_id'] . '"> ' . $rw['room_number'] . ' </option>';
                    }
                    echo '</select>';

                    ?>
            </div>


            <input type="submit" id="btn" value="Sign Up" name="create">

            <div class="form-footer">
                Already have an account? <a href="login.php">Log In</a>
            </div>
        </form>
    </div>

</body>

</html>