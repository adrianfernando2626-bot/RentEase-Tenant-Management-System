<?php
include_once("includes/database.php");
session_start();
$warning = "";
$role_tenant = "Tenant";
$role_landlord = "Landlord";
$role_owner = "Owner";

if (isset($_GET['message']) && $_GET['message'] === 'account_deleted') {
  $warning = "Your account was successfully deleted";
}
if (isset($_GET['status']) && $_GET['status'] === 'logout') {
  session_unset();
  session_destroy();
}


if (isset($_POST["login"])) {
  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];
    $status = $_POST["status"];

    try {
      $sql = "SELECT * FROM userall WHERE email = :email AND role = :role";
      $stmt = $pdo->prepare($sql);
      $stmt->execute([
        ':email' => $email,
        ':role' => $status
      ]);

      $user = $stmt->fetch();

      if ($user) {
        if ($password === $user['password'] & $user['account_status'] === 'Active') {
          $_SESSION['user_id'] = $user['user_id'];
          $_SESSION['role'] = $user['role'];
          echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
          echo "<script>
                  document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                      icon: 'success',
                      title: 'Login',
                      text: 'You have successfully logged in'
                    }).then(function() {";
          if ($status == 'Tenant') {
            echo "window.location.href = 'tenant/tenantdashboard.php';";
          } elseif ($status == 'Landlord') {
            echo "window.location.href = 'admin/landlorddashboard.php';";
          } elseif ($status == 'Owner') {
            echo "window.location.href = 'buildingowner/ownerdashboard.php';";
          }
          echo "});
                });
                </script>";
        } else {
          if ($password !== $user['password']) {
            $warning = "Incorrect password.";
          } elseif ($user['account_status'] === 'Pending') {
            $warning = "Your account is still under the process.";
          } elseif ($user['account_status'] === 'Inactive') {
            $warning = "Your Account is not Active.";
          }
        }
      } else {
        $warning = "Account not found for selected role.";
      }
    } catch (PDOException $e) {
      $warning = "Database error: " . $e->getMessage();
    }
  }
}

?>




<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Apartment Management System - Login</title>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;700&display=swap');

    * {
      box-sizing: border-box;
    }

    body {
      background: linear-gradient(135deg, #4a90e2, #50e3c2);
      font-family: 'Poppins', sans-serif;
      margin: 0;
      padding: 0;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .container {
      background: white;
      width: 380px;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      overflow: hidden;
      padding: 36px 30px;
    }

    h2 {
      margin: 0 0 24px 0;
      color: #333;
      text-align: center;
      font-weight: 700;
    }

    label {
      margin-bottom: 6px;
      font-weight: 500;
      color: #333;
      display: block;
    }

    input[type="email"],
    input[type="password"],
    select {
      padding: 12px 15px;
      margin-bottom: 18px;
      border: 1.8px solid #ddd;
      border-radius: 6px;
      font-size: 16px;
      width: 100%;
      transition: border-color 0.3s;
    }

    input[type="email"]:focus,
    input[type="password"]:focus,
    select:focus {
      border-color: #50e3c2;
      outline: none;
    }

    button {
      padding: 12px;
      background-color: #4a90e2;
      border: none;
      border-radius: 6px;
      color: white;
      font-weight: 700;
      font-size: 16px;
      cursor: pointer;
      width: 100%;
      transition: background-color 0.3s;
    }

    button:hover {
      background-color: #357ABD;
    }

    .form-footer {
      margin-top: 16px;
      font-size: 14px;
      color: #666;
      text-align: center;
    }

    .form-footer a {
      color: #50e3c2;
      text-decoration: none;
      font-weight: 600;
    }

    .form-footer a:hover {
      text-decoration: underline;
    }
  </style>

</head>

<body>
  <div class="container">
    <h2>Login</h2>
    <p style="color: red;"><?php echo $warning; ?></p>
    <form id="login-form" action="login.php" method="post" novalidate>

      <label for="role">Select Role</label>
      <select id="status" name="status">
        <option value="" disabled selected>Select your role</option>
        <option value="Tenant">Tenant</option>
        <option value="Landlord">Landlord</option>
        <option value="Owner">Owner</option>
      </select>

      <label for="login-email" id="name">Email</label>
      <input type="email" id="email" name="email" placeholder="Enter Email Address"
        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>

      <label for="login-password">Password</label>
      <input type="password" id="password" name="password" placeholder="Enter Password"
        value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>" required>

      <button type="submit" name="login">Log In</button>
    </form>
    <div class="form-footer">
      Don't have an account? <a href="signup.php">Sign Up</a>
    </div>
  </div>


</body>

</html>