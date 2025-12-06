<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once 'tenant/guest_logging_process/vendor/autoload.php';

if (isset($_GET['message']) && $_GET['message'] === 'account_deleted') {
    $warning = "Your account was successfully deleted";
}
if (isset($_GET['status']) && $_GET['status'] === 'logout') {
    session_unset();
    session_destroy();
}

// Load session messages and form values
$old = $_SESSION['old_input'] ?? [];
$warning_sign_up = $_SESSION['warning_sign_up'] ?? '';
$warning_log_in = $_SESSION['warning_log_in'] ?? '';
unset($_SESSION['old_input'], $_SESSION['warning_sign_up'], $_SESSION['warning_log_in']);

$host = "localhost";
$username = "root";
$password = "";
$database = "apartment";
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = trim($_POST['password']);
    $role = strtolower($_POST['role']);

    $stmt = $conn->prepare("SELECT c.user_id, c.password, u.role, pi.email, pi.first_name, u.account_status 
                            FROM credential c
                            JOIN user u ON c.user_id = u.user_id
                            JOIN personal_info pi ON u.user_id = pi.user_id
                            WHERE pi.email = ? AND u.role = ?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user) {
        if ($user['account_status'] === 'Renewal for Contract') {
            $_SESSION['show_renewal_swal'] = true;
            $_SESSION['renew_user_id'] = $user['user_id'];
            header("Location: login.php");
            exit();
        } else {
            if ($user['account_status'] === 'Active') {
                if (password_verify($password, $user['password'])) {
                    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
                    $expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

                    $stmt = $conn->prepare("INSERT INTO otp (user_id, otp_code, expires_at) VALUES (?, ?, ?)");
                    $stmt->bind_param("iss", $user['user_id'], $otp, $expires);
                    $stmt->execute();

                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username   = 'adrianfernando2626@gmail.com';
                        $mail->Password   = 'cxwqqwktqevyogmt';
                        $mail->SMTPSecure = 'tls';
                        $mail->Port = 587;

                        $mail->setFrom('adrianfernando2626@gmail.com', 'Tenant Management');
                        $mail->addAddress($user['email'], $user['first_name']);

                        $mail->isHTML(true);
                        $mail->Subject = 'Login Verification OTP';
                        $mail->Body = "<p>Hi {$user['first_name']},</p><p>Your login OTP is: <strong>$otp</strong><br>This will expire in 5 minutes.</p>";
                        $mail->send();

                        $_SESSION['pending_user_id'] = $user['user_id'];
                        $_SESSION['pending_role'] = $user['role'];
                        $_SESSION['otp_email'] = $user['email'];
                        header("Location: verify_login_otp.php");
                        exit();
                    } catch (Exception $e) {
                        $_SESSION['warning_log_in'] = "OTP email failed: {$mail->ErrorInfo}";
                        header("Location: login.php");
                        exit();
                    }
                } else {
                    $_SESSION['warning_log_in'] = "Incorrect password.";
                    header("Location: login.php");
                    exit();
                }
            } else {
                if ($user['account_status'] === 'Pending') {
                    $_SESSION['warning_log_in'] = "Your account is not active yet.";
                    header("Location: login.php");
                    exit();
                } elseif ($user['account_status'] === 'Waiting to Renew') {
                    $_SESSION['warning_log_in'] = "Your contract is waiting to be renewed.";
                    header("Location: login.php");
                    exit();
                } elseif ($user['account_status'] === 'Deleted') {
                    $_SESSION['warning_log_in'] = "Your account has been deleted.";
                    header("Location: login.php");
                    exit();
                } elseif ($user['account_status'] === 'Inactive') {
                    $_SESSION['warning_log_in'] = "Your account is inactive.";
                    header("Location: login.php");
                    exit();
                }
            }
        }
    } else {
        $_SESSION['warning_log_in'] = "No user found from the respective role.";
        header("Location: login.php");
        exit();
    }

    $stmt->close();
}

?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900&display=swap"
        rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <title>Login Page</title>
</head>

<body>
    <div class="container" id="container">

        <div class="sign-up">

            <form id="signupForm" action="signup.php" method="POST" enctype="multipart/form-data">
                <h1>Create Account</h1>
                <?php if ($warning_sign_up): ?>
                    <p style="color: red;"><?php echo $warning_sign_up; ?></p>
                <?php endif; ?>
                <input type="text" name="last_name" value="<?php echo htmlspecialchars($old['last_name'] ?? ''); ?>" placeholder="Last Name" class="inputs" required />
                <input type="text" name="first_name" value="<?php echo htmlspecialchars($old['first_name'] ?? ''); ?>" placeholder="First Name" class="inputs" required />
                <input type="text" name="middle_name" value="<?php echo htmlspecialchars($old['middle_name'] ?? ''); ?>" placeholder="Middle Name" class="inputs" />
                <input type="email" name="email" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" placeholder="Email" required />
                <input type="text" name="phone_number" value="<?php echo htmlspecialchars($old['phone_number'] ?? '+639'); ?>" placeholder="Phone Number" class="inputs" required>
                <input type="text" name="address" value="<?php echo htmlspecialchars($old['address'] ?? ''); ?>" placeholder="Address" class="inputs" required />
                <input type="date" name="birthdate" value="<?php echo htmlspecialchars($old['birthdate'] ?? ''); ?>" placeholder="Date of Birth" class="inputs" required />

                <label for="gender">Gender:</label>
                <select id="gender" name="gender" required>
                    <option value="Male" <?php echo (isset($old['gender']) && $old['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo (isset($old['gender']) && $old['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                    <option value="Nonbinary" <?php echo (isset($old['gender']) && $old['gender'] === 'nonbinary') ? 'selected' : ''; ?>>Non-binary</option>
                    <option value="Prefer_not_to_say" <?php echo (isset($old['gender']) && $old['gender'] === 'prefer_not_to_say') ? 'selected' : ''; ?>>Prefer not to say</option>
                    <option value="Other" <?php echo (isset($old['gender']) && $old['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                </select>
                <label for="room_list" class="form-label">Available Rooms:</label>
                <select id="desired_room" name="desired_room" required>
                    <option value="" disabled selected>- Select a Room</option>
                    <?php
                    $stmt = $conn->prepare("SELECT 
                                            room_id, 
                                            room_number,
                                            capacity
                                        FROM room 
                                        WHERE status = 'Available' 
                                        ");
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($user = $result->fetch_array()) {
                        echo '<option value="' . $user['room_id'] . '">' . htmlspecialchars($user['room_number']) . '</option>';
                    }
                    $conn->close();
                    ?>
                </select>
                <input type="file" name="my_image" id="my_image" required>
                <input type="password" name="password" id="password" value="<?php echo htmlspecialchars($old['password'] ?? ''); ?>" placeholder="Create Password" required />
                <input type="password" name="confirm_password" value="<?php echo htmlspecialchars($old['confirm_password'] ?? ''); ?>" id="confirm_password" placeholder="Confirm Password" required />

                <div class="show-password-container">
                    <input type="checkbox" id="show-password" name="show_password"
                        <?php echo (!empty($old['show_password'])) ? 'checked' : ''; ?> />

                    <label for="show-password">Show Password</label>
                </div>
                <div class="show-password-container">
                    <input type="checkbox" id="termsConditions" name="terms_agreed"
                        <?php echo (!empty($old['terms_agreed'])) ? 'checked' : ''; ?> />

                    <label for="termsConditions">
                        I agree to the <a href="#" id="show-terms" style="color: blue; text-decoration: underline;">Terms and Conditions</a>
                    </label>
                </div>

                <button type="submit">Sign Up</button>
                <?php if ($warning_sign_up): ?>
                    <p style="color: red;"><?php echo $warning_sign_up; ?></p>
                <?php endif; ?>


                <?php if (isset($_SESSION['success_message'])): ?>
                    <p style="color: green;"><?php echo $_SESSION['success_message']; ?></p>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>
            </form>
        </div>

        <div class="sign-in">
            <form action="login.php" method="POST">

                <h1>Sign In</h1>
                <input type="email" name="email" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" placeholder="Email" required />
                <input type="password" name="password" value="<?php echo htmlspecialchars($old['password'] ?? ''); ?>" id="login_password" placeholder="Password" required />

                <label>Select Role</label>
                <select class="inputs" name="role" required>
                    <option value="">Select Role</option>
                    <option value="Admin" <?php echo (isset($old['role']) && $old['role'] === 'Admin') ? 'selected' : ''; ?>>Admin</option>
                    <option value="Landlord" <?php echo (isset($old['role']) && $old['role'] === 'Landlord') ? 'selected' : ''; ?>>Landlord</option>
                    <option value="Tenant" <?php echo (isset($old['role']) && $old['role'] === 'Tenant') ? 'selected' : ''; ?>>Tenant</option>
                </select>

                <div class="show-password-container">
                    <input type="checkbox" id="show-login-password" />
                    <label for="show-login-password">Show Password</label>
                </div>

                <a href="#" id="forgot-password-link">Forgot password?</a>
                <button type="submit">Sign In</button>
                <?php if ($warning_log_in): ?>
                    <p style="color: red;"><?php echo $warning_log_in; ?></p>
                <?php endif; ?>

                <p style="color: green;">
                    <?php
                    if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
                        echo "Password reset successful. You can now log in.";
                    }
                    ?>
                </p>

            </form>
        </div>

        <div class="forgot-password">
            <form action="forgot_password.php" method="POST">
                <h2>Forgot Password</h2>
                <input type="email" name="email" placeholder="Enter your email" required />
                <button type="submit">Send OTP</button>
                <button id="back-to-login">Back to Login</button>
            </form>
        </div>


        <div class="terms-and-conditions">
            <h2>Terms and Conditions</h2>
            <p>1. User Access Eligibility: Only authorized employees with valid login credentials may use the EMS.
                Account Responsibility: Users are responsible for the confidentiality of their login credentials and all activities under their account.
                Access Restrictions: Use the EMS only for job-related tasks. Unauthorized access or misuse will result in disciplinary action.</p>
            <p>2. System Usage Permitted Use: Use the EMS only for work-related purposes in line with company policies.
                Prohibited Use: Do not engage in illegal activities, share harmful content, or attempt unauthorized access.</p>
            <p>3. Data Privacy & Security Data Use: The EMS collects and processes employee data for work-related purposes. By using the EMS, you consent to this data collection.
                Confidentiality: Maintain the confidentiality of all data accessed through the EMS.</p>
            <p>4. Intellectual Property All content in the EMS is owned by OKX lang ako and is protected by copyright. Users may only access the system for authorized purposes.
            <p>5. Monitoring The company may monitor EMS activity to ensure compliance with these terms and to protect the system..</p>
            <button id="back-to-signup">Back to Signup</button>
        </div>


        <div class="toogle-container">
            <div class="toogle">
                <div class="toogle-panel toogle-left">
                    <h1>Welcome User!</h1>
                    <p>If you already have an account</p>
                    <button class="hidden" id="login">Sign In</button>
                </div>
                <div class="toogle-panel toogle-right">
                    <h1>Hello, User!</h1>
                    <p>If you don't have an account</p>
                    <button class="hidden" id="register">Sign Up</button>
                </div>
            </div>
        </div>
    </div>
    <?php if (isset($_SESSION['renew_user_id'])): ?>
        <form action="tenant/approveOwner/renew_contract.php" method="post" id="renewForm">
            <input type="hidden" name="user_id" value="<?php echo $_SESSION['renew_user_id']; ?>">
        </form>
    <?php endif; ?>

    <script src="js/script.js"></script>
    <?php if (isset($_SESSION['show_renewal_swal']) && $_SESSION['show_renewal_swal'] === true): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'question',
                    title: 'Expired Contract',
                    text: 'It seems your contract is already expired. Do you want to request a renewal of contract from the building owner?',
                    showCancelButton: true,
                    confirmButtonText: 'Yes',
                    cancelButtonText: 'No',
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('renewForm').submit();
                    } else {
                        location.reload();
                    }
                });
            });
        </script>
        <?php unset($_SESSION['show_renewal_swal'], $_SESSION['renew_user_id']); ?>
    <?php endif; ?>
    <?php if (isset($_GET['message']) && $_GET['message'] === 'renewal_success'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Renewal Success',
                text: 'Your request has been sent to the building owner, please wait for the response',
                confirmButtonText: 'OK'
            });
        </script>
    <?php endif; ?>
    <?php if (isset($_GET['message']) && $_GET['message'] === 'signed_up_successful'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Sign Up Success',
                text: 'Successfully creation of account, please wait for the building owner to make you contract',
                confirmButtonText: 'OK'
            });
        </script>
    <?php endif; ?>
</body>

</html>