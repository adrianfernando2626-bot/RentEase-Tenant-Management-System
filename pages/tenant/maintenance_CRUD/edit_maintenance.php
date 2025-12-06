<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
}
session_start();
$user_id = $_SESSION['user_id'];
$sql = 'SELECT * FROM userall WHERE user_id = ' . $user_id;
$rs = mysqli_query($db_connection, $sql);
$rw = mysqli_fetch_array($rs);
$id = $_GET['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issue_type = $_POST['issue_type'] ?? '';
    $description = $_POST['description'] ?? '';
    $date_requested = $_POST['date_requested'] ?? '';
    try {
        if ($issue_type & $description & $date_requested) {
            $pdo->prepare("UPDATE maintenance_request SET issue_type=?, description=?, date_requested=? WHERE maintenance_request_id=?")->execute([$issue_type, $description, $date_requested, $id]);
        }
        header("Location: ../tenantmaintenance.php?message=request_updated");
        exit();
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Database error: " . $e->getMessage());
    }
}
$sql = "";

$stmt = $pdo->prepare("SELECT a.*, b.update_message
                                FROM maintenance_request a
                                JOIN status_request b ON b.maintenance_request_id = a.maintenance_request_id WHERE a.maintenance_request_id = ?");
$stmt->execute([$id]);
$request = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../../css/addcontent.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <title>Edit Maintenance Request</title>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>

    <script>
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
                    window.location.href = '../../logout.php?status=logout';
                } else {
                    location.reload();
                }
            });
        }
    </script>
</head>

<body>
    <div class="side-bar collapsed">
        <a href="" class="logo">
            <img src="" alt="" class="logo-img">
            <img src="" alt="" class="logo-icon">
        </a>
        <ul class="nav-link">
            <li><a href="../tenantdashboard.php"><i class="fas fa-th-large"></i>
                    <p>DashBoard</p>
                </a></li>
            <li><a href="../tenantprofile.php"><i class="fas fa-cog"></i>
                    <p>User Profile </p>
                </a></li>
            <li><a href="../tenantmaintenance.php"><i class="fas fa-file-contract"></i>
                    <p>Maintenance</p>
                </a></li>
            <li><a href="../tenantguestlog.php"><i class="fas fa-file-contract"></i>
                    <p>Guest Log</p>
                </a></li>
            <li><a onclick="logout()"><i class="fas fa-sign-out-alt"></i>
                    <p> Logout</p>
                </a></li>
            <div class="active"></div>
        </ul>
    </div>

    <main class="main-content">
        <div class="form-section">
            <div class="form-header">
                <i class="fas fa-clipboard-list fa-2x"></i>
                <h1>Edit Maintenance Request</h1>
            </div>
            <form method="post" class="rule-form">
                <div class="mb-3">
                    <label> Request Issue Type:
                        <select name="issue_type" id="issue_type">
                            <?php
                            $status_option = ['Plumbing', 'Electrical', 'Noise', 'Appliance', 'Other'];

                            echo "<option value='" . $request['issue_type'] . "' selected>" . $request['issue_type'] . "</option>";
                            foreach ($status_option as $option) {
                                if ($option !== $request['issue_type']) {
                                    echo "<option value='" . $option . "'>" . $option . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </label>

                    <label>Description:<textarea name="description" required><?= htmlspecialchars($request['description']) ?></textarea></label><br><br>
                    <label>Date Requested:<input type="date" name="date_requested" value="<?= htmlspecialchars($request['date_requested']) ?>" required /></label><br><br>

                    <button type="submit" class="add-btn">Update</button>
                    <a href="../tenantmaintenance.php" class="back-btn">Cancel</a>
            </form>
        </div>
        <div class="profile-section">
            <div class="profile-card">
                <img src="../../images/<?php echo $rw['img'] ?>" alt="Profile Picture" class="avatar">
                <h3><?php echo $rw['first_name'] ?> <?php echo $rw['last_name'] ?></h3>
                <p><?php echo $rw['email'] ?></p>
            </div>

            <div class="activity-panel">
                <h4>Recent Activity</h4>
                <ul>
                    <?php
                    $sql = 'SELECT a.*, b.issue_type FROM change_log a
                            JOIN maintenance_request b ON b.maintenance_request_id = a.record_id
                            WHERE a.action_type IN ("INSERT", "UPDATE") AND table_name = "maintenance_request" AND b.room_id = ' . $rw['room_id'] . '
                            ORDER BY a.changed_at DESC LIMIT 4';

                    $result = mysqli_query($db_connection, $sql);

                    while ($row = mysqli_fetch_assoc($result)):
                        if ($row['issue_type']) {
                            if ($row['action_type'] === 'INSERT') {
                                echo '<li><i class="fas fa-plus"></i> Added a request "' . $row['issue_type'] . '"</li>';
                            } elseif ($row['action_type'] === 'UPDATE') {
                                echo '<li><i class="fas fa-pencil-alt"></i> Edited "' . $row['issue_type'] . '"</li>';
                            }
                        } else {
                            echo '<li><i class="fas fa-exclamation-circle"></i> Request data not found (may have been deleted)</li>';
                        }
                    endwhile;
                    ?>
                </ul>

            </div>
        </div>

    </main>

    <script src="../../js/script.js"></script>
</body>

</html>