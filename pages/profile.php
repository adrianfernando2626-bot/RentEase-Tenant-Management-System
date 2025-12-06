<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}
if (isset($_GET['message']) && $_GET['message'] === 'account_updated') {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Account Updated',
                text: 'Your account was successfully updated.'
            });
        });
    </script>";
}

if (isset($_GET['message']) && $_GET['message'] === 'account_image_updated') {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
    echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'Account Image Updated',
                text: 'Your account image was successfully updated.'
            });
        });
    </script>";
}


$warning_for_picture = "";
$warning = "";
session_start();
$sql = 'SELECT * FROM userall WHERE user_id = 6';
$rs = mysqli_query($db_connection, $sql);
$rw = mysqli_fetch_array($rs);

$user_id = 6;

if (isset($_POST['updatepic']) && isset($_FILES['my_image'])) {

    $img_name = $_FILES['my_image']['name'];
    $img_size = $_FILES['my_image']['size'];
    $tmp_name = $_FILES['my_image']['tmp_name'];
    $error = $_FILES['my_image']['error'];

    if ($error === 0) {
        if ($img_size > 250000) {
            $warning_for_picture = "The file size is too large";
        } else {
            $img_ex = pathinfo($img_name, PATHINFO_EXTENSION);
            $img_ex_lc = strtolower($img_ex);
            $allowed_exs = array("jpg", "jpeg", "png");

            if (in_array($img_ex_lc, $allowed_exs)) {
                $new_image_name = uniqid("IMG-", true) . "." . $img_ex_lc;
                $img_upload_path = 'images/' . $new_image_name;
                move_uploaded_file($tmp_name, $img_upload_path);

                $sql2 = "UPDATE personal_info SET 
                img = '" . $new_image_name . "'
                WHERE user_id = $user_id";


                if (mysqli_query($db_connection, $sql2)) {
                    echo "<script>
                            window.location.href = 'profile.php?message=account_image_updated';
                            </script>";
                    exit();
                    session_destroy();
                } else {
                    echo mysqli_error($db_connection);
                }
            } else {
                $warning_for_picture = "It only accepts images";
            }
        }
    } else {

        $warning_for_picture = "Error Uploading the File";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updated_account'])) {

    $update_user_id = mysqli_real_escape_string($db_connection, $user_id);


    $first_name = $_POST['copy_first_name'];
    $last_name = $_POST['copy_last_name'];
    $middle_name = $_POST['copy_middle_name'];
    $email = $_POST['copy_email'];
    $username = $_POST['copy_username'];
    $birthdate = $_POST['copy_birthdate'];
    $password = $_POST['copy_password'];
    $address = $_POST['copy_address'];
    $phone_number = ($_POST['copy_phone_number']);

    $today = new DateTime();
    $birthdate_obj = DateTime::createFromFormat('Y-m-d', $birthdate);
    $ageInterval = $birthdate_obj->diff($today);
    $age = $ageInterval->y;


    if (strlen($phone_number) === 13 && substr($phone_number, 0, 4) === '+639' && strlen($password) >= 8 && $age >= 18) {
        $sql = "UPDATE credential SET 
        username = '" . $username . "',
        password = '" . $password . "'      
        WHERE user_id = $update_user_id";

        $sql2 = "UPDATE personal_info SET 
        first_name = '" . $first_name . "',
        last_name = '" . $last_name . "',
        middle_name = '" . $middle_name . "',
        birthdate = '" . $birthdate . "',
        email = '" . $email . "',
        address = '" . $address . "',
        phone_number ='" . $phone_number . "'    
        WHERE user_id = $update_user_id";

        if (mysqli_query($db_connection, $sql) && mysqli_query($db_connection, $sql2)) {
            echo "<script>
            window.location.href = 'profile.php?message=account_updated';
        </script>";
        } else {
            echo mysqli_error($db_connection);
        }
    } else {
        if (strlen($password) < 8) {
            $warning = "Password must be at least 8 characters long.";
        } elseif (strlen($phone_number) !== 13) {
            $warning = "Please input a valid contact number";
        } elseif (substr($phone_number, 0, 4) !== '+639') {
            $warning = "Please insert a valid phone number that starts with +639";
        } elseif ($age < 18) {
            $warning = "You must be at least 18 years old.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $delete_user_id = mysqli_real_escape_string($db_connection, $user_id);


    $sql1 = "DELETE FROM user WHERE user_id = $delete_user_id";
    $sql2 = "DELETE FROM credential WHERE user_id = $delete_user_id";
    $sql3 = "DELETE FROM personal_info WHERE user_id = $delete_user_id";

    if (mysqli_query($db_connection, $sql1) && mysqli_query($db_connection, $sql2) && mysqli_query($db_connection, $sql3)) {
        session_destroy();
        header("Location: login.php?message=account_deleted");
        exit();
    } else {
        die("Error deleting account: " . mysqli_error($db_connection));
    }
}




?>


<!doctype html>
<html>

<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        function done() {
            window.location.href = 'signup.php';
        }

        function logout() {
            var msg = 'Are you sure you want to logout?';
            Swal.fire({
                icon: 'question',
                title: 'Log Out',
                text: msg,
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'login.php';
                } else {
                    window.location.href = 'profile.php';
                }
            });
        }

        function confirmDelete() {
            Swal.fire({
                icon: 'warning',
                title: 'Delete Account',
                text: 'Are you sure you want to delete your account?',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteForm').submit();
                }
            });
        }

        function confirmUpdate() {
            Swal.fire({
                icon: 'warning',
                title: 'Updating Account',
                text: 'Are you sure you want to update your account?',
                showCancelButton: true,
                confirmButtonText: 'Yes, update it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById("updated_account").value = "1";

                    const visibleValue = document.getElementById("first_name").value;
                    document.getElementById("copy_first_name").value = visibleValue;

                    const visibleValue1 = document.getElementById("last_name").value;
                    document.getElementById("copy_last_name").value = visibleValue1;

                    const visibleValue2 = document.getElementById("middle_name").value;
                    document.getElementById("copy_middle_name").value = visibleValue2;

                    const visibleValue3 = document.getElementById("email").value;
                    document.getElementById("copy_email").value = visibleValue3;

                    const visibleValue4 = document.getElementById("address").value;
                    document.getElementById("copy_address").value = visibleValue4;

                    const visibleValue5 = document.getElementById("birthdate").value;
                    document.getElementById("copy_birthdate").value = visibleValue5;

                    const visibleValue6 = document.getElementById("username").value;
                    document.getElementById("copy_username").value = visibleValue6;

                    const visibleValue7 = document.getElementById("password").value;
                    document.getElementById("copy_password").value = visibleValue7;

                    const visibleValue8 = document.getElementById("phone_number").value;
                    document.getElementById("copy_phone_number").value = visibleValue8;


                    document.getElementById('updateForm').submit();
                }
            });
        }
    </script>

    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1'>
    <title>Profile</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-alpha1/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='#' rel='stylesheet'>
    <script type='text/javascript' src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js'></script>
    <style>
        * {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            outline: none;
            border: none;
            transition: all .2s linear;
        }

        .submit-btn {
            width: 100%;
            padding: 12px;
            font-size: 17px;
            background: #9a2d2d;
            color: #fff;
            margin: 6px;
            cursor: pointer;
            border-radius: 50px;
            box-shadow: 5px 5px 10px rgba(0, 0, 0, .1);
        }

        .submit-btn:hover {
            background: #7a2f2f;
            transform: scale(1.05);
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #888;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        body {
            background: linear-gradient(#7a2f2f, #210a0a);
        }

        .form-control:focus {
            box-shadow: none;
            border-color: #BA68C8
        }

        .profile-button {
            background: rgb(99, 39, 120);
            box-shadow: none;
            border: none
        }

        .profile-button:hover {
            background: #ff0000
        }

        .profile-button:focus {
            background: #682773;
            box-shadow: none
        }

        .profile-button:active {
            background: #F5F5DC;
            box-shadow: none
        }

        .back:hover {
            color: #F5F5DC;
            cursor: pointer
        }

        .labels {
            font-size: 11px;
            font-weight: bold;
            font-size: 17px;
            margin: 5px;
        }

        .add-experience:hover {
            background: #F5F5DC;
            color: #fff;
            cursor: pointer;
            border: solid 1px #F5F5DC
        }

        .error {
            grid-column: span 2;
            color: red;
            text-align: center;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .label-phone_number {
            display: flex;
            justify-content: center;
            align-items: center;

        }
    </style>
    <script type='text/javascript'
        src='https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-alpha1/dist/js/bootstrap.bundle.min.js'></script>
    <script type='text/javascript' src='#'></script>
    <script type='text/javascript' src='#'></script>
    <script type='text/javascript' src='#'></script>
    <script type='text/javascript'>

    </script>
    <script type='text/javascript'>
        var myLink = document.querySelector('a[href="#"]');
        myLink.addEventListener('click', function(e) {
            e.preventDefault();
        });
    </script>

</head>

<body className='snippet-body'>
    <form action="profile.php"
        method="post"
        enctype="multipart/form-data">
        <div class="container rounded bg-white mt-5 mb-5">
            <div class="row">
                <div class="col-md-3 border-right">
                    <form action="profile.php"
                        method="post"
                        enctype="multipart/form-data">
                        <div class="d-flex flex-column align-items-center text-center p-3 py-5"><img class="rounded-circle mt-5"
                                width="150px" src="images/<?php echo $rw['img']; ?>"


                                class="font-weight-bold"><?php echo $rw['username']; ?></span>
                            <span class="text-black-50"><?php echo $rw['email']; ?></span>

                            <input type="file" name="my_image" class="img-folder"><br>
                            <?php if (!empty($warning_for_picture)): ?>
                                <div class="alert alert-warning"><?php echo htmlspecialchars($warning_for_picture); ?></div>
                            <?php endif; ?>
                            <input type="submit" name="updatepic" class="submit-btn" value="Update your Profile Pic">

                        </div>
                    </form>
                </div>
                <div class="col-md-5 border-right">
                    <div class="p-3 py-5">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="text-right"><strong>Profile Settings</strong></h4>


                        </div>
                        <?php if (!empty($warning)): ?>
                            <div class="alert alert-warning"><?php echo htmlspecialchars($warning); ?></div>
                        <?php endif; ?>
                        <div class="row mt-2">
                            <div class="col-md-6"><label class="labels">First Name</label>
                                <input type="text" class="form-control"
                                    placeholder="Enter First Name" value="<?php echo $rw['first_name']; ?>" name="first_name" id="first_name">
                            </div>

                            <div class="col-md-6"><label class="labels">Surname</label>
                                <input type="text"
                                    class="form-control" value="<?php echo $rw['last_name']; ?>" placeholder="Enter Last Name" name="last_name" id="last_name">
                            </div>

                            <div class="col-md-6"><label class="labels">Middlename</label>
                                <input type="text"
                                    class="form-control" value="<?php echo $rw['middle_name']; ?>" placeholder="Enter Middle Name" name="middle_name" id="middle_name">
                            </div>
                            <div class="col-md-6"><label class="labels">Gender</label>
                                <input type="text"
                                    class="form-control" value="<?php echo $rw['gender']; ?>" placeholder="Enter Gender" name="middle_name" id="middle_name" disabled>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-12"><label class="labels">Date of Birth</label>
                                <input type="date"
                                    class="form-control" placeholder="enter phone number" value="<?php echo $rw['birthdate']; ?>" name="birthdate" id="birthdate">
                            </div>

                            <div class="col-md-12"><label class="labels">Email</label>
                                <input type="text"
                                    class="form-control" placeholder="Enter Email Address" value="<?php echo $rw['email']; ?>" name="email" id="email">
                            </div>

                            <div class="col-md-12"><label class="labels">Username</label>
                                <input type="text"
                                    class="form-control" placeholder="Enter Username" value="<?php echo $rw['username']; ?>" name="username" id="username">
                            </div>

                            <div class="col-md-12"><label class="labels">Password</label>
                                <input type="password" class="form-control" placeholder="Enter Password"
                                    value="<?php echo  $rw['password']; ?>"
                                    name="password" id="password">
                            </div>

                            <div class="col-md-12"><label class="labels">Address</label>
                                <input type="text"
                                    class="form-control" placeholder="Enter Address" value="<?php echo $rw['address']; ?>" name="address" id="address">
                            </div>

                            <div class="col-md-12"><label class="labels-phone_number">Phone Number</label>
                                <input type="text" class="form-control" placeholder="Enter Phone Number"
                                    value="<?php echo $rw['phone_number']; ?>" name="phone_number" id="phone_number">
                            </div>
                            <div class="col-md-12"><label class="labels">User Role</label>

                                <input type="text"
                                    class="form-control" placeholder="Status" value="<?php echo $rw['role']; ?>" name="role" id="role" disabled>
                            </div>
                        </div>
                        <div class="row mt-3">

                            <button type="button" class="submit-btn" name="update" onclick="confirmUpdate()">Update</button>
                            <button type="button" class="submit-btn" name="delete" onclick="confirmDelete()">Delete</button>
                            <button type="button" class="submit-btn" onclick="logout()">Logout</button>
                            <button type="button" class="submit-btn" onclick="done()">Done</button>

                        </div>


                    </div>
                </div>

            </div>
        </div>

    </form>
    <form id="deleteForm" method="POST" action="profile.php">
        <input type="hidden" name="delete_account" value="1">
    </form>

    <form id="updateForm" method="POST" action="profile.php">
        <input type="hidden" name="updated_account" id="updated_account" value="1">
        <input type="hidden" id="copy_first_name" name="copy_first_name">
        <input type="hidden" id="copy_last_name" name="copy_last_name">
        <input type="hidden" id="copy_middle_name" name="copy_middle_name">
        <input type="hidden" id="copy_email" name="copy_email">
        <input type="hidden" id="copy_address" name="copy_address">
        <input type="text" id="copy_phone_number" name="copy_phone_number" hidden>
        <input type="hidden" id="copy_birthdate" name="copy_birthdate">
        <input type="hidden" id="copy_username" name="copy_username">
        <input type="hidden" id="copy_password" name="copy_password">

    </form>
</body>

</html>