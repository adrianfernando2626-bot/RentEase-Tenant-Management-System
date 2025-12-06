<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../includes/database.php')) {
    include_once('../includes/database.php');
}
session_start();
$user_id = $_SESSION['user_id'];
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Tenant') {
    header("Location: ../login.php");
    exit();
}
$rowsPerPage = isset($_GET['rowsPerPage']) ? (int) $_GET['rowsPerPage'] : 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

$offset = ($page - 1) * $rowsPerPage;

$limitClause = '';
if (!isset($_GET['viewAll'])) {
    $limitClause = "LIMIT $rowsPerPage OFFSET $offset";
}

$sql = 'SELECT * FROM userall WHERE user_id = ' . $user_id;
$rs = mysqli_query($db_connection, $sql);
$rw = mysqli_fetch_array($rs);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../css/rules1.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


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
                    window.location.href = '../logout.php?status=logout';
                } else {
                    location.reload();
                }
            });
        }
    </script>

</head>

<body>
    <div class="side-bar collapsed">
        <a href="tenantdashboard.php" class="logo">
            <img src="../images/Logo No Background Black.png" alt="" class="logo-img">
            <img src="../images/Copy of Logo No Background.png" alt="" class="logo-icon">
        </a>
        <ul class="nav-link">
            <li><a href="tenantdashboard.php"><i class="fas fa-th-large"></i>
                    <p>DashBoard</p>
                </a></li>
            <li><a href="#"><i class="fas fa-user-check"></i>
                    <p>Payment status</p>
                </a></li>
            <li><a href="tenantmaintenance.php"><i class="fas fa-file-contract"></i>
                    <p>Maintenance</p>
                </a></li>
            <li><a href="tenantguestlog.php"><i class="fas fa-file-contract"></i>
                    <p>Guest Log</p>
                </a></li>
            <li><a href="tenantprofile.php"><i class="fas fa-cog"></i>
                    <p>User Profile </p>
                </a></li>
            <li><a onclick="logout()"><i class="fas fa-sign-out-alt"></i>
                    <p> Logout</p>
                </a></li>
            <div class="active"></div>
        </ul>
    </div>

    <main class="main">
        <div class="topbar">
            <div>
                <h1>Payments<br>
                    Status</h1>
            </div>
            <div class="topbar-right">
                <div class="user-info">
                    <div class="position-relative">
                        <i class="bi bi-bell fs-3" id="bell-icon" style="cursor: pointer;"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php
                            $sql123 = 'SELECT COUNT(*) as total_notification FROM notification WHERE user_id = ' . $user_id . ' AND notif_status = "unread"';
                            $rs123  = mysqli_query($db_connection, $sql123);
                            $rw123 = mysqli_fetch_array($rs123);
                            ?>
                            <?php echo $rw123['total_notification']; ?>
                            <span class=" visually-hidden">unread messages</span>
                        </span>
                    </div>
                    <div id="notification-container" style="display: none; position: absolute; top: 60px; right: 160px; background-color: #fff; border: 1px solid #ccc; border-radius: 10px; width: 400px; box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1); z-index: 999;">
                        <div style="padding: 10px; border-bottom: 1px solid #eee; font-weight: bold;">
                            <h3>Notifications</h3>
                        </div>
                        <?php
                        $sql = "
                                SELECT n.*
                                FROM notification n
                                INNER JOIN (
                                    SELECT MAX(notif_id) AS max_id
                                    FROM notification
                                    WHERE user_id = ?
                                    GROUP BY notif_title
                                ) AS latest
                                ON n.notif_id = latest.max_id
                                WHERE n.user_id = ? AND n.notif_status != 'Archived'
                                ORDER BY n.date_created DESC
                            ";
                        $stmt = $db_connection->prepare($sql);
                        $stmt->execute([$user_id, $user_id]);
                        $notifications = $stmt->get_result();
                        if ($notifications && $notifications->num_rows > 0):
                            while ($notification = $notifications->fetch_assoc()):
                                $notif_id = $notification['notif_id'];
                                $notif_title = $notification['notif_title'];

                                $today = new DateTime();
                                $created = new DateTime($notification['date_created']);
                                $interval = $created->diff($today);
                                $daysAgo = $interval->d;

                                $text_stmt = $db_connection->prepare("SELECT notif_text FROM notification WHERE notif_title = ? AND user_id = ? ORDER BY date_created ASC");
                                $text_stmt->execute([$notif_title, $user_id]);
                                $notif_texts = $text_stmt->get_result();
                        ?>
                                <div id="notification-title-<?php echo $notif_id; ?>"
                                    class="notification-title"
                                    data-title="<?php echo htmlspecialchars($notif_title, ENT_QUOTES); ?>"
                                    style="padding: 10px; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center; cursor: pointer;">
                                    <h6 style="margin: 0;"><strong><?php echo $notif_title; ?></strong></h6>
                                    <div style="display: flex; gap: 10px; align-items: center;">
                                        <small style="color: gray;"><?php echo $daysAgo; ?> day<?php echo $daysAgo > 1 ? 's' : ''; ?> ago</small>
                                        <?php
                                        if ($notification['notif_status'] === 'unread'): ?>
                                            <span class="badge bg-light text-success notif-dot">
                                                <i class="fas fa-circle text-primary me-1"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>


                                <div id="notification-container-body-<?php echo $notif_id; ?>" class="notif-body" style="display: none;">
                                    <?php while ($notif_text = $notif_texts->fetch_assoc()): ?>
                                        <div class="notif-item">
                                            <p class="notif-text"><?php echo $notif_text['notif_text']; ?></p>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                        <?php
                            endwhile;
                        endif;
                        ?>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            const bellIcon = document.getElementById('bell-icon');
                            const notificationContainer = document.getElementById('notification-container');

                            const badge = document.querySelector('.badge.bg-danger'); // badge element

                            bellIcon.addEventListener('click', (e) => {
                                e.stopPropagation(); // Prevent triggering outside click listener
                                const isVisible = notificationContainer.style.display === 'block';
                                notificationContainer.style.display = isVisible ? 'none' : 'block';
                            });

                            document.addEventListener('click', (e) => {
                                if (!bellIcon.contains(e.target) && !notificationContainer.contains(e.target)) {
                                    notificationContainer.style.display = 'none';
                                }
                            });

                            const titles = document.querySelectorAll('.notification-title');
                            titles.forEach(title => {
                                title.addEventListener('click', () => {
                                    const notifTitle = title.dataset.title;

                                    const notifId = title.id.split('-')[2];
                                    const body = document.getElementById(`notification-container-body-${notifId}`);
                                    if (body) {
                                        body.style.display = (body.style.display === 'none' || body.style.display === '') ? 'block' : 'none';
                                    }

                                    fetch('marking_notif/markNotificationAsRead.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/x-www-form-urlencoded'
                                            },
                                            body: `notif_title=${encodeURIComponent(notifTitle)}`
                                        })
                                        .then(response => response.text())
                                        .then(result => {
                                            fetch('marking_notif/getUnreadCount.php')
                                                .then(res => res.text())
                                                .then(newCount => {
                                                    if (badge) badge.textContent = newCount;
                                                });

                                            const dot = title.querySelector('.notif-dot');
                                            if (dot) dot.remove();

                                            // 🔽 Update badge counter
                                            if (badge) {
                                                let count = parseInt(badge.textContent.trim(), 10);
                                                if (!isNaN(count) && count > 0) {
                                                    badge.textContent = count - 1;
                                                }
                                            }
                                        })
                                        .catch(err => console.error('Error:', err));
                                });
                            });
                        });
                    </script>
                    <span><?php echo $rw['first_name'] . " " . $rw['last_name']; ?></span>
                    <img class="rounded-circle"
                        width="150px" src="../images/<?php echo $rw['img']; ?>">
                </div>
            </div>
        </div>

        <div class="container mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="d-flex gap-2">

                    <form method="get">

                        <input type="text" class="form-control" name="search" placeholder="Search..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" />

                        <button class="btn btn-primary" type="submit">Search</button>
                    </form>
                </div>
            </div>

            <table id="rulesTable" class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Last Payment</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Amount Due</th>
                        <th>Payment Method</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $search = $_GET['search'] ?? '';
                        $searchClause = '';
                        if (!empty($search)) {
                            $searchSafe = mysqli_real_escape_string($db_connection, $search);
                            $searchClause = "(
                            e.paid_on LIKE '%$searchSafe%'OR 
                            e.amount_due LIKE '%$searchSafe%' OR 
                            f.status LIKE '%$searchSafe%'

                        )";
                        }

                        $whereParts = [];
                        if (!empty($searchClause)) {
                            $whereParts[] = $searchClause;
                        }

                        $finalWhere = "WHERE a.user_id = $user_id AND f.status = 'PAID'";
                        if (!empty($whereParts)) {
                            $finalWhere .= ' AND (' . implode(' OR ', $whereParts) . ')';
                        }


                        $sql = "SELECT e.paid_on, e.amount_due, e.payment_method, e.due_date, f.status
                                        FROM contract a
                                        JOIN payment e ON e.contract_id = a.contract_id
                                        JOIN payment_status f ON e.payment_id = f.payment_id
                                        $finalWhere
                                        ORDER BY e.paid_on DESC";



                        $result_query = mysqli_query($db_connection, $sql);
                        while ($result = mysqli_fetch_array($result_query)) {

                            echo '<tr>
                    
                    <td>' . date("F j, Y", strtotime($result['paid_on']))  . '</td> 
                    <td>' . $result['status'] . '</td>
                    <td>' . date("F j, Y", strtotime($result['due_date'])) . '</td>
                    <td>' . $result['amount_due'] . '</td>
                    <td>' . $result['payment_method'] . '</td>
                    </tr>';
                        }
                    } catch (Exception $e) {
                        echo "Error: " . $e->getMessage();
                    }
                    $countQuery = "SELECT COUNT(*) as total
                                        FROM contract a
                                        JOIN payment e ON e.contract_id = a.contract_id
                                        JOIN payment_status f ON e.payment_id = f.payment_id
                                        $finalWhere AND a.user_id = 6
                                        ORDER BY e.paid_on DESC";
                    $countResult = mysqli_query($db_connection, $countQuery);


                    $totalRows = mysqli_fetch_assoc($countResult)['total'];
                    $totalPages = ceil($totalRows / $rowsPerPage);
                    ?>
                </tbody>
            </table>
            <?php if (!isset($_GET['viewAll'])): ?>

                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?rowsPerPage=<?= $rowsPerPage ?>&page=<?= $page - 1 ?>">Previous</a>
                    </li>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="?rowsPerPage=<?= $rowsPerPage ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?rowsPerPage=<?= $rowsPerPage ?>&page=<?= $page + 1 ?>">Next</a>
                    </li>
                </ul>

            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-center mt-3">
                <form method="get" id="filterForm" class="d-flex gap-2 align-items-center">
                    <button type="submit" class="btn btn-primary" name="viewAll" value="1">View All Payment</button>
                </form>
            </div>
        </div>
    </main>
    <script src="../js/script.js"></script>
    <script src="../js/datatable.js"></script>
</body>

</html>