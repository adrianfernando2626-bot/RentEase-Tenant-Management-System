<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../includes/database.php')) {
    include_once('../includes/database.php');
}
session_start();
$user_id = $_SESSION['user_id'];
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}
$sql = 'SELECT * FROM userall WHERE user_id = ' . $user_id;
$rs = mysqli_query($db_connection, $sql);
$rw = mysqli_fetch_array($rs);
// Load session messages and form values
$old = $_SESSION['old_input'] ?? [];
$warning_signing_landlord = $_SESSION['warning_signing_landlord'] ?? '';
unset($_SESSION['old_input'], $_SESSION['warning_signing_landlord']);

$message = $_GET['message'] ?? '';
$warning = "";
if (isset($_GET['message']) && $_GET['message'] === 'account_updated') {
    $warning = 'Account Updated';
}
if (isset($_GET['message']) && $_GET['message'] === 'accounts_landlord_updated') {
    $warning = 'Landlord Account Updated';
}
if (isset($_GET['message']) && $_GET['message'] === 'accounts_deleted_permanent') {
    $warning = 'Accounts Permanently Deleted';
}
if (isset($_GET['message']) && $_GET['message'] === 'accounts_deleted') {
    $warning = 'Accounts Deleted';
}
$rowsPerPage = isset($_GET['rowsPerPage']) ? (int) $_GET['rowsPerPage'] : 5;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

$offset = ($page - 1) * $rowsPerPage;

$limitClause = '';
if (!isset($_GET['viewAll'])) {
    $limitClause = "LIMIT $rowsPerPage OFFSET $offset";
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Access Management Admin</title>
    <link rel="stylesheet" href="../css/rule4.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <style>
        .modal-xl-custom {
            max-width: 95%;
        }
    </style>
    <script>
        function confirmDelete(user_id) {

            Swal.fire({
                icon: 'warning',
                title: 'Delete Account',
                text: 'Are you sure you want to delete this account?',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'tenant_CRUD/delete_tenant.php?id=' + user_id;
                }
            });
        }

        function confirmPermanentDelete() {
            Swal.fire({
                icon: 'warning',
                title: 'Delete Account',
                text: 'Are you sure you want to delete all the data to this accounts, this will not be reverted?',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'tenant_CRUD/delete_permanent_tenant.php';
                }
            });
        }

        function confirmBulkDelete() {
            const deleteBtn = document.getElementById('bulkDeleteBtn');
            if (deleteBtn.disabled) {
                return; // Prevent SweetAlert if button is disabled
            }

            const bulkDeleteForm = document.getElementById('bulkDeleteForm');
            Swal.fire({
                icon: 'warning',
                title: 'Delete Tenants',
                text: 'Are you sure you want to Delete these Tenants?',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    bulkDeleteForm.submit();
                }
            });
        }

        function confirmBulkUnselectedDelete() {
            const bulkDeleteForm = document.getElementById('bulkUnselectedDeleteForm');
            Swal.fire({
                icon: 'warning',
                title: 'Delete Tenants',
                text: 'Are you sure you want to Delete these Tenants?',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    bulkDeleteForm.submit();
                }
            });
        }


        function confirmBulkDeletePendingTenants() {
            const deleteBtnpending = document.getElementById('deletebtnpending');
            if (deleteBtnpending.disabled) {
                return; // Prevent SweetAlert if button is disabled
            }

            const bulkDeletePendingTenantForm = document.getElementById('bulkDeletePendingTenantForm');
            Swal.fire({
                icon: 'warning',
                title: 'Delete Pending Tenants',
                text: 'Are you sure you want to Delete these Pending Tenants?',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    bulkDeletePendingTenantForm.submit();
                }
            });
        }

        function confirmBulkDeleteLandlord() {
            const deleteBtnlandlord = document.getElementById('bulkDeleteLandlordBtn');
            if (deleteBtnlandlord.disabled) {
                return; // Prevent SweetAlert if button is disabled
            }

            const bulkDeleteLandlordForm = document.getElementById('bulkDeleteLandlordForm');
            Swal.fire({
                icon: 'warning',
                title: 'Delete Landlords',
                text: 'Are you sure you want to Delete these Landlords?',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    bulkDeleteLandlordForm.submit();
                }
            });
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
                    window.location.href = '../logout.php?status=logout';
                } else {
                    location.reload();
                }
            });
        }

        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.toggle-password').forEach(function(icon) {
                icon.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const input = document.getElementById(targetId);
                    if (input.type === 'password') {
                        input.type = 'text';
                        this.classList.remove('bi-eye-slash');
                        this.classList.add('bi-eye');
                    } else {
                        input.type = 'password';
                        this.classList.remove('bi-eye');
                        this.classList.add('bi-eye-slash');
                    }
                });
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const checkboxes = document.querySelectorAll(".pending-tenant-checkbox");
            const selectAll = document.getElementById("selectAllpendingtenants");
            const rejectBtn = document.getElementById("deletebtnpending");

            function toggleRejectButton() {
                const anyChecked = document.querySelectorAll(".pending-tenant-checkbox:checked").length > 0;
                rejectBtn.disabled = !anyChecked;
            }

            // Individual checkbox click
            checkboxes.forEach(cb => {
                cb.addEventListener("change", toggleRejectButton);
            });

            // Select All checkbox
            selectAll.addEventListener("change", function() {
                checkboxes.forEach(cb => cb.checked = selectAll.checked);
                toggleRejectButton();
            });
        });
    </script>

</head>

<body>
    <div class="side-bar">
        <div class="burger-btn" id="burgerBtn">
            <i class="fas fa-bars"></i>
        </div>
        <a href="ownerdashboard.php" class="logo">
            <img src="../images/Logo No Background Black.png" alt="" class="logo-img">
        </a>
        <ul class="nav-link">
            <li><a href="ownerdashboard.php"><i class="fas fa-th-large"></i>
                    <p>DashBoard</p>
                </a></li>
            <li><a href="building_management.php"><i class="fas fa-building"></i>
                    <p>Building Management</p>
                </a></li>
            <li><a href="#"><i class="fas fa-users"></i>
                    <p>User Access Management</p>
                </a></li>
            <li><a href="contractmanage.php"><i class="fas fa-file-contract"></i>
                    <p>Contract Management</p>
                </a></li>
            <li><a href="rules.php"><i class="fas fa-file-lines"></i>
                    <p>Rules Management</p>
                </a></li>
            <li><a href="rentreportowner.php"><i class="fas fa-file-lines"></i>
                    <p>Report Management</p>
                </a></li>
            <li><a href="ownerprofile.php"><i class="fas fa-cog"></i>
                    <p>User Account</p>
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
                <h1>User <br>
                    Management</h1>
            </div>

            <?php if (!empty($warning)): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($warning); ?></div>
            <?php endif; ?>
            <?php if (!empty($warning_signing_landlord)): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($warning_signing_landlord); ?></div>
            <?php endif; ?>
            <div class="topbar-right">
                <button class="help-btn" id="helpBtn" title="Help Center" onclick="openHelpModal();">
                    <i class="fas fa-question-circle"></i>
                    <span>Help</span>
                </button>
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
                                        body.classList.toggle('open');
                                    }

                                    fetch('../tenant/marking_notif/markNotificationAsRead.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/x-www-form-urlencoded'
                                            },
                                            body: `notif_title=${encodeURIComponent(notifTitle)}`
                                        })
                                        .then(response => response.text())
                                        .then(result => {
                                            fetch('../tenant/marking_notif/getUnreadCount.php')
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
            <div class="d-flex justify-content-center mb-4">
                <form method="get" class="d-flex gap-2 flex-wrap" style="max-width: 500px;">
                    <input type="text" class="form-control" name="search" placeholder="Search..."
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" style="flex: 1;">
                    <button class="btn btn-primary" type="submit">Search</button>
                </form>
            </div>


            <form id="tenantCheckboxForm">
                <div class="table-responsive">
                    <table id="rulesTable" class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th style="text-align: center;">
                                    <input type="checkbox" id="selectAllCheckbox">
                                </th>
                                <th>Name</th>
                                <th>Room</th>
                                <th>Email Address</th>
                                <th>Status</th>
                                <th>Deletion Approval</th>
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
                            a.first_name LIKE '%$searchSafe%' OR 
                            a.last_name LIKE '%$searchSafe%' OR 
                            b.room_number LIKE '%$searchSafe%' OR 
                            a.role LIKE '%$searchSafe%' OR
                            a.email LIKE '%$searchSafe%' OR
                            a.deletion_approval LIKE '%$searchSafe%' 

                        )";
                                }

                                $whereParts = [];
                                if (!empty($searchClause)) {
                                    $whereParts[] = $searchClause;
                                }

                                $finalWhere = 'WHERE a.account_status = "Active" OR a.account_status = "Inactive" OR a.account_status = "Pending"';
                                if (!empty($whereParts)) {
                                    $finalWhere = 'WHERE a.account_status = "Active" OR a.account_status = "Inactive" OR a.account_status = "Pending" AND' . implode(' AND ', $whereParts);
                                }

                                $sql = "SELECT a.*, b.room_number
                        FROM userall a
                        JOIN room b ON b.room_id = a.room_id
                                $finalWhere
                                ORDER BY a.room_id ASC $limitClause";



                                $result_query = mysqli_query($db_connection, $sql);
                                while ($result = mysqli_fetch_array($result_query)) {

                                    echo '<tr>
                        <td class="text-center">
                            <input type="checkbox"  class="tenant-checkbox" name="selected_tenant[]" value="' . $result['user_id'] . '">
                        </td>
                            <td>' . $result['first_name'] . ' ' . $result['last_name'] . '</td>
                    <td>' . $result['room_number'] . '</td>
                    <td>' . $result['email'] . '</td>';

                                    if ($result['account_status'] === 'Active') {
                                        $account_status_color = 'success';
                                    } elseif ($result['account_status'] === 'Inactive') {
                                        $account_status_color = 'danger';
                                    } elseif ($result['account_status'] === 'Pending') {
                                        $account_status_color = 'warning';
                                    }
                                    if ($result['deletion_approval'] === 'Not Approved') {
                                        $approval_color = 'warning';
                                        $text_color = 'black';
                                    } elseif ($result['deletion_approval'] === 'Approved') {
                                        $approval_color = 'success';
                                        $text_color = 'light';
                                    }
                                    echo '<td>
                            <span class="badge bg-light text-' . $account_status_color . '">
                            <i class="fas fa-circle text-' . $account_status_color . ' me-1"></i> ' . $result['account_status'] . '
                            </span></td>   
                            <td><span class="badge bg-' . $approval_color . ' text-' . $text_color . '" >' . $result['deletion_approval'] . '
                            </span>
                       </td>             
            </tr>';
                                }

                                $countQuery = "SELECT COUNT(*) as total FROM userall a
               JOIN room b ON b.room_id = a.room_id
               $finalWhere";
                                $countResult = mysqli_query($db_connection, $countQuery);


                                $totalRows = mysqli_fetch_assoc($countResult)['total'];
                                $totalPages = ceil($totalRows / $rowsPerPage);
                                $filter_status = "";
                            } catch (Exception $e) {
                                echo "Error: " . $e->getMessage();
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </form>
            <div class="action-buttons">
                <form id="bulkEditForm" method="post" action="tenant_CRUD/edit_tenant_bulk.php">
                    <button type="submit" name="bulkUpdate" class="btn btn-edit" id="bulkEditBtn" disabled>
                        ✏️ Edit Selected
                    </button>
                </form>

                <form id="bulkDeleteForm" method="post" action="tenant_CRUD/delete_tenant_bulk.php">
                    <button type="submit" name="bulkDelete" class="btn btn-delete" id="bulkDeleteBtn" onclick="confirmBulkDelete()" disabled>
                        🗑️ Delete Selected
                    </button>
                </form>

                <form id="bulkEditUnselectedForm" method="post" action="tenant_CRUD/edit_tenant_bulk.php">
                    <button type="submit" name="UnselectedbulkUpdate" class="btn btn-edit" id="bulkEditUnselectedBtn">
                        ✏️ Edit Unselected
                    </button>
                </form>

                <form id="bulkUnselectedDeleteForm" method="post" action="tenant_CRUD/delete_tenant_bulk.php">
                    <button type="button" name="bulkDelete" class="btn btn-delete" id="bulkUnselectedDeleteBtn" onclick="confirmBulkUnselectedDelete()">
                        🗑️ Delete Unselected
                    </button>
                </form>
            </div>




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

            <div class="container mt-4">
                <div class="row justify-content-center gy-2 gx-3">
                    <div class="col-auto">
                        <form method="get" id="filterForm">
                            <button type="submit" class="btn btn-primary" name="viewAll" value="1">View All Tenants</button>
                        </form>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#deletedUsersModal">
                            Show Deleted Users
                        </button>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#landlordManagementModal">
                            <i class="fas fa-users"></i> Manage Landlords
                        </button>
                    </div>
                    <div class="col-auto">
                        <div class="position-relative">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pendingtenantsModal">
                                Show Pending Tenants
                            </button>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php
                                $sql123 = 'SELECT COUNT(*) as tenant FROM userall WHERE account_status = "Pending"';
                                $rs123  = mysqli_query($db_connection, $sql123);
                                $rw123 = mysqli_fetch_array($rs123);
                                ?>
                                <?php echo $rw123['tenant']; ?>
                                <span class="visually-hidden">Pending Tenants</span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <div id="helpModal" class="help-modal" aria-hidden="true">
        <div class="help-modal-content" role="dialog" aria-modal="true" aria-labelledby="helpTitle">
            <div class="help-modal-header">
                <h2 id="helpTitle"><i class="fa-solid fa-graduation-cap"></i> Help Center - System Tutorial</h2>
                <button id="helpClose" class="help-close" aria-label="Close">&times;</button>
            </div>

            <div class="help-modal-body">

                <div class="tutorial-content">
                    <!-- Step 1 -->
                    <div id="step1" class="tutorial-step">
                        <div class="step-icon"><i class="fa-solid fa-users-gear"></i></div>
                        <h3>User Management Page</h3>
                        <div class="step-layout">
                            <div class="step-content">
                                <img src="../images/usermanage_help.png" alt="Add Building Screenshot" class="step-image">
                                <p>The <strong>User Management</strong> page allows administrators to manage all users within the system. You can view, edit, or delete user accounts, and monitor their status and deletion approval. This section also includes options to manage specific user groups such as <strong>tenants</strong> and <strong>landlords</strong>, as well as view deleted or pending tenant accounts. Use the search bar to quickly locate users by name or email.</p>
                            </div>
                        </div>

                        <div class="step-layout">
                            <div class="step-content">
                                <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                    How to Edit User
                                </h4>
                                <img src="../images/arrow_edituser_help.png" alt="Add Building Screenshot" class="step-image">
                                <p>Use these buttons to edit or delete users. You can edit or delete selected users, or even edit the unselected ones using the Edit Unselected option.</p>





                            </div>
                        </div>

                        <div class="step-layout">
                            <div class="step-content">
                                <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                    Edit User Page
                                </h4>
                                <img src="../images/edit_page_help.png" alt="Add Building Screenshot" class="step-image">
                                <p>When you click “Edit Selected” or “Edit Unselected,” this window will appear. It allows you to view and update tenant details, change account status, or approve deletion. You can then save changes or cancel using the buttons below.</p>





                                <div class="step-highlight">
                                    <i class="fa-solid fa-lightbulb"></i>
                                    <span>You can access this help anytime by clicking the Help button.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- Step 2 -->
                <div id="step2" class="tutorial-step">
                    <div class="step-icon"><i class="fa-solid fa-user-plus"></i></div>
                    <h3>How to Add Landlord</h3>
                    <div class="step-layout">
                        <div class="step-content">
                            <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                Step 1: Click the “Manage Landlords” Button
                            </h4>
                            <img src="../images/arrow_addlandlord_help.png" alt="Add Building Screenshot" class="step-image">
                            <p>The <strong>User Management</strong> page lets you view and manage users. Click <strong>Manage Landlords</strong> to handle landlord accounts or view their details.</p>
                        </div>
                    </div>

                    <div class="step-layout">
                        <div class="step-content">
                            <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                Step 2: Click the “Add Landlord” Button
                            </h4>
                            <img src="../images/arrow_addlandlordbutton_help.png" alt="Add Building Screenshot" class="step-image">
                            <p>This window allows you to manage landlord accounts. Click the <strong>"Add New Landlord"</strong> button to register a new landlord. You can also use the <strong>"Edit Selected"</strong> or <strong>"Delete Selected"</strong> buttons to update or remove existing records.</p>





                        </div>
                    </div>

                    <div class="step-layout">
                        <div class="step-content">
                            <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                Step 2: Adding New Landlord
                            </h4>
                            <img src="../images/addlandlord_modal_help.png" alt="Add Building Screenshot" class="step-image">
                            <p>This window allows you to add a new landlord. Fill in the landlord’s personal and account details, assign a building, and set privileges. Once all fields are complete, click <strong>“Save User”</strong> to register or <strong>“Cancel”</strong> to discard the entry.</p>




                            <div class="step-highlight">
                                <i class="fa-solid fa-lightbulb"></i>
                                <span>You can access this help anytime by clicking the Help button.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="step3" class="tutorial-step">
                    <div class="step-icon"><i class="fa-solid fa-file-contract"></i></div>
                    <h3>How to Add Contracts to a Pending Tenant</h3>
                    <div class="step-layout">
                        <div class="step-content">
                            <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                Step 1: Click the "Show Pending Tenants"
                            </h4>
                            <img src="../images/arrow_pending_help.png" alt="Add Building Screenshot" class="step-image">
                            <p>Click the <strong>“Show Pending Tenants”</strong> button to view tenant accounts that are waiting for approval.</p>
                        </div>
                    </div>

                    <div class="step-layout">
                        <div class="step-content">
                            <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                Step 2: Pending Tenant Modal
                            </h4>
                            <img src="../images/arrow_selecttenant_help.png" alt="Add Building Screenshot" class="step-image">
                            <p>The <strong>Pending Tenants</strong> section displays tenants waiting for approval. You can select a tenant to either create a contract or reject their application.</p>



                        </div>
                    </div>

                    <div class="step-layout">
                        <div class="step-content">
                            <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                Step 2: Adding Contract to a Pending Tenant
                            </h4>
                            <img src="../images/add_contract_help.png" alt="Add Building Screenshot" class="step-image">
                            <p>The <strong>Add a New Contract</strong> form allows you to create a lease agreement for a tenant. Set the start date, lease duration, and terms, then assign a room and payment method before saving.</p>



                            <div class="step-highlight">
                                <i class="fa-solid fa-lightbulb"></i>
                                <span>You can access this help anytime by clicking the Help button.</span>
                            </div>
                        </div>
                    </div>


                </div>

                <div id="step4" class="tutorial-step">
                    <div class="step-icon"><i class="fa-solid fa-user-pen"></i></div>
                    <h3>How to Edit Landlord</h3>
                    <div class="step-layout">
                        <div class="step-content">
                            <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                Step 1: Select a Landlord
                            </h4>
                            <img src="../images/select_landlord_help.png" alt="Add Building Screenshot" class="step-image">
                            <p>Select a landlord from the list by clicking the checkbox beside their name. Once selected, you can use the <strong>“Edit Selected”</strong> or <strong>“Delete Selected”</strong> buttons to manage the chosen landlord’s information.</p>
                        </div>

                        <div class="step-layout">
                            <div class="step-content">
                                <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                    Step 2: Adding New Landlord
                                </h4>
                                <img src="../images/editlandlord_page_help.png" alt="Add Building Screenshot" class="step-image">
                                <p>This window appears when you click <strong>“Edit Selected”</strong>. It allows you to update the selected landlord’s account status and privileges. After making changes, click <strong>“Save All Changes”</strong> to apply them or <strong>“Cancel”</strong> to discard.</p>




                                <div class="step-highlight">
                                    <i class="fa-solid fa-lightbulb"></i>
                                    <span>You can access this help anytime by clicking the Help button.</span>
                                </div>
                            </div>
                        </div>
                    </div>



                </div>

                <div id="step5" class="tutorial-step">
                    <div class="step-icon"><i class="fa-solid fa-user-pen"></i></div>
                    <h3>How to Show Deleted User</h3>
                    <div class="step-layout">
                        <div class="step-content">
                            <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                Step 1: Click the "Show Deleted User"
                            </h4>
                            <img src="../images/arrow_deleteduser_help.png" alt="Add Building Screenshot" class="step-image">
                            <p>Click the <strong>“Show Deleted Users”</strong> button to view all tenant accounts that have been previously deleted. This feature allows you to review, restore, or permanently remove users from the system if needed.</p>
                        </div>
                    </div>

                    <div class="step-layout">
                        <div class="step-content">
                            <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                Step 2: Deleted User Modal
                            </h4>
                            <img src="../images/deleted_user_help.png" alt="Add Building Screenshot" class="step-image">
                            <p>This window shows deleted user accounts. You can search for users, export the list as a PDF, or permanently delete selected accounts.</p>




                            <div class="step-highlight">
                                <i class="fa-solid fa-lightbulb"></i>
                                <span>You can access this help anytime by clicking the Help button.</span>
                            </div>
                        </div>
                    </div>


                </div>

                <div id="step6" class="tutorial-step">
                    <div class="step-icon"><i class="fa-solid fa-chart-line"></i></div>
                    <h3>How to Generate Report on Landlord</h3>
                    <div class="step-layout">
                        <div class="step-content">
                            <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                Step 1: Click the “Manage Landlords” Button
                            </h4>
                            <img src="../images/arrow_addlandlord_help.png" alt="Add Building Screenshot" class="step-image">
                            <p>The <strong>User Management</strong> page lets you view and manage users. Click <strong>Manage Landlords</strong> to handle landlord accounts or view their details.</p>
                        </div>
                    </div>

                    <div class="step-layout">
                        <div class="step-content">
                            <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                Step 2: Click the “Export to PDF” Button
                            </h4>
                            <img src="../images/report_button_help.png" alt="Add Building Screenshot" class="step-image">
                            <p>Click the <strong>“Export to PDF”</strong> button to download and save the landlord list as a PDF file. This allows you to keep a copy of all landlord records for documentation or reporting purposes.</p>
                        </div>
                    </div>
                    <div class="step-highlight">
                        <i class="fa-solid fa-lightbulb"></i>
                        <span>You can access this help anytime by clicking the Help button.</span>
                    </div>
                </div>
            </div>

            <div class="help-modal-footer">
                <button id="helpPrev" class="help-btn-secondary" disabled>Previous</button>
                <div style="flex:1"></div>
                <button id="helpNext" class="help-btn-primary">Next</button>
                <button id="helpFinish" class="help-btn-primary" style="display:none">Finish</button>
            </div>
        </div>
    </div>

    <script>
        // Simple function to open help modal
        function openHelpModal() {
            const helpModal = document.getElementById('helpModal');
            helpModal.classList.add('open');
            helpModal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            // Call initModal if it exists
            if (typeof initModal === 'function') {
                initModal();
            }
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const helpBtn = document.getElementById('helpBtn');
            const helpModal = document.getElementById('helpModal');
            const helpClose = document.getElementById('helpClose');
            const helpNext = document.getElementById('helpNext');
            const helpPrev = document.getElementById('helpPrev');
            const helpFinish = document.getElementById('helpFinish');
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');

            let currentStep = 1;
            const totalSteps = 6;

            function openModal() {
                helpModal.classList.add('open');
                helpModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';

                currentStep = 1; // ensure step is reset
                updateStepUI(); // show Step 1
            }

            function closeModal() {
                helpModal.classList.remove('open');
                helpModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            console.log("Showing step", currentStep);

            function updateStepUI() {
                // hide all
                for (let i = 1; i <= totalSteps; i++) {
                    const el = document.getElementById('step' + i);
                    if (el) {
                        el.classList.remove('active');
                        el.style.display = 'none';
                    }
                }
                // show current
                const cur = document.getElementById('step' + currentStep);
                if (cur) {
                    cur.classList.add('active');
                    cur.style.display = 'block';
                }

                // progress
                const pct = (currentStep / totalSteps) * 100;
                if (progressFill) progressFill.style.width = pct + '%';
                if (progressText) progressText.textContent = `Step ${currentStep} of ${totalSteps}`;

                // buttons
                if (helpPrev) helpPrev.disabled = currentStep === 1;
                if (helpNext) helpNext.style.display = currentStep === totalSteps ? 'none' : 'inline-block';
                if (helpFinish) helpFinish.style.display = currentStep === totalSteps ? 'inline-block' : 'none';
            }

            window.changeStep = function(direction) {
                const newStep = currentStep + (direction || 0);
                if (newStep < 1 || newStep > totalSteps) return;
                currentStep = newStep;
                updateStepUI();
            };

            const cur = document.getElementById('step' + currentStep);
            if (cur) {
                cur.classList.add('active');
                cur.style.display = 'block';
            }

            helpBtn.addEventListener('click', openModal);
            helpClose.addEventListener('click', closeModal);
            helpNext.addEventListener('click', () => changeStep(1));
            helpPrev.addEventListener('click', () => changeStep(-1));
            helpFinish.addEventListener('click', closeModal);

            // click outside (backdrop)
            helpModal.addEventListener('click', (e) => {
                if (e.target === helpModal) closeModal();
            });

            // keyboard
            document.addEventListener('keydown', (e) => {
                if (!helpModal.classList.contains('open')) return;
                if (e.key === 'Escape') closeModal();
                if (e.key === 'ArrowRight') changeStep(1);
                if (e.key === 'ArrowLeft') changeStep(-1);
            });

            // run once to ensure no stray hidden state
            initModal();
        });
    </script>

    <div class="modal fade" id="landlordManagementModal" tabindex="-1" aria-labelledby="landlordManagementLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content shadow-lg">

                <!-- Header -->
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="landlordManagementLabel">Landlord Management</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body" style="max-height: 80vh; overflow-y: auto;">

                    <!-- Top Action Buttons -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <!-- Add New Landlord Button -->
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addlandlordModal">
                            <i class="fas fa-user-plus"></i> Add New Landlord
                        </button>

                        <!-- PDF Export -->
                        <form action="tenant_CRUD/exportpdflandlordlist.php" target="_blank" method="post" class="mb-0">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="fas fa-file-pdf"></i> Export to PDF
                            </button>
                        </form>
                    </div>

                    <!-- Bulk Edit/Delete Buttons -->
                    <div class="d-flex gap-2 mb-3">
                        <form id="bulkEditLandlordForm" method="post" action="tenant_CRUD/edit_landlord_bulk.php">
                            <button type="submit" name="bulkUpdateLandlord" class="btn btn-outline-primary" id="bulkEditLandlordBtn" disabled>
                                <i class="fas fa-edit"></i> Edit Selected
                            </button>
                        </form>

                        <form id="bulkDeleteLandlordForm" method="post" action="tenant_CRUD/delete_bulk_landlord.php">
                            <button type="button" name="bulkDeleteLandlord" class="btn btn-outline-danger" id="bulkDeleteLandlordBtn" onclick="confirmBulkDeleteLandlord()" disabled>
                                <i class="fas fa-trash"></i> Delete Selected
                            </button>
                        </form>
                    </div>

                    <!-- Landlord Table -->
                    <div class="table-responsive">
                        <form id="landlordTableForm">
                            <table class="table table-striped table-bordered align-middle text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>Select</th>
                                        <th>Landlord Name</th>
                                        <th>Building Assigned</th>
                                        <th>Email Address</th>
                                        <th>Contact Number</th>
                                        <th>Date Registered</th>
                                        <th>Payment Privilege</th>
                                        <th>Tenant Privilege</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT a.*, b.name FROM userall a
                                        JOIN building b ON a.building_id = b.building_id 
                                        WHERE role = 'Landlord'";
                                    $result_query = mysqli_query($db_connection, $sql);
                                    while ($result = mysqli_fetch_array($result_query)) {
                                        echo '<tr>
                                        <td>
                                            <input type="checkbox" class="landlord-checkbox" name="selected_landlord[]" value="' . $result['user_id'] . '">
                                        </td>
                                        <td>' . htmlspecialchars($result['first_name'] . ' ' . $result['last_name']) . '</td>
                                        <td>' . htmlspecialchars($result['name']) . '</td>
                                        <td>' . htmlspecialchars($result['email']) . '</td>
                                        <td>' . htmlspecialchars($result['phone_number']) . '</td>
                                        <td>' . htmlspecialchars($result['date_registered']) . '</td>
                                        <td>' . htmlspecialchars($result['payment_priviledge']) . '</td>
                                        <td>' . htmlspecialchars($result['tenant_priviledge']) . '</td>
                                    </tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Deleted Users Modal -->
    <div class="modal fade" id="deletedUsersModal" tabindex="-1" aria-labelledby="deletedUsersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-sm border-0">
                <!-- Header -->
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-semibold" id="deletedUsersModalLabel">Deleted User Accounts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body py-4 px-4">
                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <form action="tenant_CRUD/exportpdfdeletedaccounts.php" target="_blank" method="post">
                            <button type="submit" class="btn btn-outline-primary">
                                <i class="fas fa-file-contract me-2"></i> Generate PDF
                            </button>
                        </form>

                        <button type="button" class="btn btn-outline-danger" onclick="confirmPermanentDelete()">
                            <i class="fas fa-trash-alt me-2"></i> Permanent Delete
                        </button>
                    </div>
                    <form method="GET" class="mb-3">
                        <div class="input-group">
                            <input type="text" name="search_deleted_user" class="form-control" placeholder="Search tenant, email, or role"
                                value="<?php echo isset($_GET['search_deleted_user']) ? htmlspecialchars($_GET['search_deleted_user']) : ''; ?>">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </form>
                    <!-- Deleted Users Table -->
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered text-center align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email Address</th>
                                    <th>Role</th>
                                    <th>Date Registered</th>

                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $search = isset($_GET['search_deleted_user']) ? trim($_GET['search_deleted_user']) : '';

                                $sql = "SELECT first_name, last_name, email, role, date_registered
                                        FROM userall 
                                        WHERE account_status = 'Deleted'";

                                if (!empty($search)) {
                                    $search_escaped = mysqli_real_escape_string($db_connection, $search);
                                    $sql .= " AND (
                                                first_name LIKE '%$search_escaped%' 
                                                OR last_name LIKE '%$search_escaped%' 
                                                OR email LIKE '%$search_escaped%'
                                                OR role LIKE '%$search_escaped%'
                                            )";
                                }

                                $result_query = mysqli_query($db_connection, $sql);
                                while ($result = mysqli_fetch_array($result_query)) {
                                    echo '<tr>
                                    <td>' . htmlspecialchars($result['first_name'] . ' ' . $result['last_name']) . '</td>
                                    <td>' . htmlspecialchars($result['email']) . '</td>
                                    <td>' . htmlspecialchars($result['role']) . '</td>
                                    <td>' . htmlspecialchars($result['date_registered']) . '</td>
                                </tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="addlandlordModal" tabindex="-1" aria-labelledby="addlandlordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h2 class="modal-title fw-bold" id="addlandlordModalLabel"><em>Add a New Landlord</em></h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">

                    <form action="../signuplandlord.php" method="POST">
                        <!-- Personal Information -->
                        <h6 class="fw-bold mb-3">Personal Information</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" placeholder="First Name" value="<?php echo htmlspecialchars($old['first_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" placeholder="Last Name" value="<?php echo htmlspecialchars($old['last_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Middle Name</label>
                                <input type="text" name="middle_name" class="form-control" placeholder="Middle Name" value="<?php echo htmlspecialchars($old['middle_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Contact No.</label>
                                <input type="text" name="phone_number" class="form-control" placeholder="Contact Number" value="<?php echo htmlspecialchars($old['phone_number'] ?? '+639'); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label d-block">Gender</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="gender_male" value="Male"
                                        <?php echo (isset($old['gender']) && $old['gender'] === 'Male') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="gender_male">Male</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="gender" id="gender_female" value="Female"
                                        <?php echo (isset($old['gender']) && $old['gender'] === 'Female') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="gender_female">Female</label>
                                </div>

                            </div>
                        </div>
                        <!-- Account Details -->
                        <h6 class="fw-bold mb-3">Account Details</h6>
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Assigned Building</label>
                                <select name="building_id" id="building_id" class="form-select" required>
                                    <option value="" disabled <?= !isset($old['building_id']) ? 'selected' : '' ?>>Select Building</option>
                                    <?php
                                    // Get all building IDs that already have a landlord
                                    $sqluser = 'SELECT DISTINCT building_id FROM user WHERE role = "Landlord" AND building_id IS NOT NULL';
                                    $resultuser = mysqli_query($db_connection, $sqluser);

                                    $assignedBuildings = [];
                                    while ($rowuser = mysqli_fetch_assoc($resultuser)) {
                                        $assignedBuildings[] = $rowuser['building_id'];
                                    }

                                    // Convert to a comma-separated list for SQL
                                    $excluded = !empty($assignedBuildings) ? implode(',', $assignedBuildings) : 'NULL';

                                    // Select only active buildings not assigned to any landlord
                                    $sql = "SELECT * FROM building WHERE building_is_active = 1";
                                    if (!empty($assignedBuildings)) {
                                        $sql .= " AND building_id NOT IN ($excluded)";
                                    }

                                    $result = mysqli_query($db_connection, $sql);
                                    while ($row = mysqli_fetch_assoc($result)):
                                        $selected = (isset($old['building_id']) && $old['building_id'] == $row['building_id']) ? 'selected' : '';
                                    ?>
                                        <option value="<?= $row['building_id'] ?>" <?= $selected ?>><?= htmlspecialchars($row['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="Email Address" value="<?php echo htmlspecialchars($old['email'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" class="form-control" id="password" placeholder="Password" value="<?php echo htmlspecialchars($old['password'] ?? ''); ?>" required>
                                    <span class="input-group-text">
                                        <i class="bi bi-eye-slash toggle-password" data-target="password" style="cursor: pointer;"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" class="form-control" id="confirm_password" placeholder="Confirm Password" value="<?php echo htmlspecialchars($old['confirm_password'] ?? ''); ?>" required>
                                    <span class="input-group-text">
                                        <i class="bi bi-eye-slash toggle-password" data-target="confirm_password" style="cursor: pointer;"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <!-- Notes -->
                        <h6 class="fw-bold mb-3">Notes</h6>
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <label class="form-label d-block">Status</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="account_status" id="active" value="Active"
                                        <?php echo (isset($old['account_status']) && $old['account_status'] === 'Active') ? 'checked' : ''; ?> required>
                                    <label class="form-check-label" for="active">Active</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="account_status" id="inactive" value="Inactive"
                                        <?php echo (isset($old['account_status']) && $old['account_status'] === 'Inactive') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="inactive">Inactive</label>
                                </div>
                            </div>
                        </div>
                        <h6 class="fw-bold mb-3">Privileges</h6>
                        <div class="row mb-4">
                            <!-- Tenant Privileges -->
                            <div class="col-md-6">
                                <label class="form-label d-block">Privileges for Tenants</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="tenant_privilege" id="tenant_privilege" value="Approved"
                                        <?php echo (isset($old['tenant_privilege']) && $old['tenant_privilege'] === 'Approved') ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="tenant_privilege">
                                        (Allow edit, delete to tenants, etc.)
                                    </label>
                                </div>
                            </div>

                            <!-- Payment Privileges -->
                            <div class="col-md-6">
                                <label class="form-label d-block">Privileges for Payments</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="payment_privilege" id="payment_privilege" value="Approved"
                                        <?php echo (isset($old['payment_privilege']) && $old['payment_privilege'] === 'Approved') ? 'checked' : ''; ?>> <label class="form-check-label" for="payment_privilege">
                                        (Allow downloading payment reports, etc.)
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="d-flex justify-content-end gap-3">
                            <button type="submit" class="btn btn-primary px-4">Save User</button>
                            <button type="button" class="btn btn-dark px-4" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php if (isset($_SESSION['warning_signing_landlord'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var addLandlordModal = new bootstrap.Modal(document.getElementById('addlandlordModal'));
                addLandlordModal.show();
            });
        </script>
    <?php endif; ?>


    <div class="modal fade" id="pendingtenantsModal" tabindex="-1" aria-labelledby="pendingtenantsLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-semibold" id="pendingtenantsLabel">Pending Tenants</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body py-4 px-4" style="max-height: 75vh; overflow-y: auto;">
                    <!-- Action Buttons -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-outline-primary w-100" onclick="window.location.href='contract_crud/addcontracts.php'">
                                <i class="fas fa-file-contract me-2"></i>Make Contract for this Tenant?
                            </button>
                            </form>
                        </div>
                        <div class="col-md-4">
                            <form id="bulkDeletePendingTenantForm" method="post" action="tenant_CRUD/reject_pending_tenant.php">
                                <button type="button" name="bulkDeleteLandlord" class="btn btn-outline-danger w-100" id="deletebtnpending" onclick="confirmBulkDeletePendingTenants()" disabled>
                                    <i class="fas fa-trash-alt me-2"></i>Reject Tenants
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <form id="landlordTableForm">
                            <table class="table table-hover table-bordered align-middle text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">
                                            <input type="checkbox" id="selectAllpendingtenants" />
                                        </th>
                                        <th scope="col">Pending Tenant Name</th>
                                        <th scope="col">Email</th>
                                        <th scope="col">Contact No.</th>
                                        <th scope="col">Date Registered</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT user_id, first_name, last_name, email, date_registered, phone_number FROM userall WHERE role = 'Tenant' AND account_status = 'Pending'";
                                    $result_query = mysqli_query($db_connection, $sql);
                                    while ($result = mysqli_fetch_array($result_query)) {
                                        echo '<tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input pending-tenant-checkbox" name="selected_tenant[]" value="' . $result['user_id'] . '">
                                        </td>
                                        <td>' . htmlspecialchars($result['first_name'] . ' ' . $result['last_name']) . '</td>
                                        <td>' . htmlspecialchars($result['email']) . '</td>
                                        <td>' . htmlspecialchars($result['phone_number']) . '</td>
                                        <td>' . htmlspecialchars($result['date_registered']) . '</td>
                                    </tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <div class="modal fade" id="landlordlistModal" tabindex="-1" aria-labelledby="landlordlistLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title" id="landlordlistLabel">Landlord User Accounts</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body" style="max-height: 75vh; overflow-y: auto;">
                    <!-- PDF Export -->
                    <form action="tenant_CRUD/exportpdflandlordlist.php" target="_blank" method="post" class="mb-3">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-file-contract"></i> Generate PDF
                        </button>
                    </form>

                    <!-- Bulk Edit -->
                    <form id="bulkEditLandlordForm" method="post" action="tenant_CRUD/edit_landlord_bulk.php" class="mb-3">
                        <button type="submit" name="bulkUpdateLandlord" class="btn btn-outline-primary" id="bulkEditLandlordBtn" disabled>
                            <i class="fas fa-edit"></i> Edit Selected
                        </button>
                    </form>

                    <!-- Bulk Delete -->
                    <form id="bulkDeleteLandlordForm" method="post" action="tenant_CRUD/delete_bulk_landlord.php" class="mb-3">
                        <button type="button" name="bulkDeleteLandlord" class="btn btn-outline-danger" id="bulkDeleteLandlordBtn" onclick="confirmBulkDeleteLandlord()" disabled>
                            <i class="fas fa-trash-alt"></i> Delete Selected
                        </button>
                    </form>

                    <!-- Table -->
                    <div class="table-responsive">
                        <form id="landlordTableForm">
                            <table class="table table-bordered align-middle text-center">
                                <thead class="table-light">
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="selectAllLandlordCheckbox" />
                                        </th>
                                        <th>Landlord Name</th>
                                        <th>Building Assigned</th>
                                        <th>Email Address</th>
                                        <th>Contact Number</th>
                                        <th>Date Registered</th>
                                        <th>Payment Privilege</th>
                                        <th>Tenant Privilege</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $sql = "SELECT a.*, b.name FROM userall a
                                    JOIN building b ON a.building_id = b.building_id WHERE role = 'Landlord'";
                                    $result_query = mysqli_query($db_connection, $sql);
                                    while ($result = mysqli_fetch_array($result_query)) {
                                        echo '<tr>
                                        <td>
                                            <input type="checkbox" class="landlord-checkbox" name="selected_landlord[]" value="' . $result['user_id'] . '">
                                        </td>
                                        <td>' . htmlspecialchars($result['first_name'] . ' ' . $result['last_name']) . '</td>
                                        <td>' . htmlspecialchars($result['name']) . '</td>
                                        <td>' . htmlspecialchars($result['email']) . '</td>
                                        <td>' . htmlspecialchars($result['phone_number']) . '</td>
                                        <td>' . htmlspecialchars($result['date_registered']) . '</td>
                                        <td>' . htmlspecialchars($result['payment_priviledge']) . '</td>
                                        <td>' . htmlspecialchars($result['tenant_priviledge']) . '</td>
                                    </tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script>
        const landlordCheckboxes = document.querySelectorAll('.landlord-checkbox');
        const editlandlordForm = document.getElementById('bulkEditLandlordForm');
        const deletelandlordForm = document.getElementById('bulkDeleteLandlordForm');
        const editlandlordBtn = document.getElementById('bulkEditLandlordBtn');
        const deletelandlordBtn = document.getElementById('bulkDeleteLandlordBtn');

        function syncCheckboxes() {
            // Remove old hidden inputs
            editlandlordForm.querySelectorAll('input[name="selected_landlord[]"]').forEach(el => el.remove());
            deletelandlordForm.querySelectorAll('input[name="selected_landlord[]"]').forEach(el => el.remove());

            let anyChecked = false;

            landlordCheckboxes.forEach(cb => {
                if (cb.checked) {
                    anyChecked = true;

                    const hiddenInput1 = document.createElement('input');
                    hiddenInput1.type = 'hidden';
                    hiddenInput1.name = 'selected_landlord[]';
                    hiddenInput1.value = cb.value;
                    editlandlordForm.appendChild(hiddenInput1);

                    const hiddenInput2 = document.createElement('input');
                    hiddenInput2.type = 'hidden';
                    hiddenInput2.name = 'selected_landlord[]';
                    hiddenInput2.value = cb.value;
                    deletelandlordForm.appendChild(hiddenInput2);
                }
            });

            editlandlordBtn.disabled = !anyChecked;
            deletelandlordBtn.disabled = !anyChecked;
        }

        landlordCheckboxes.forEach(cb => {
            cb.addEventListener('change', syncCheckboxes);
        });
    </script>

    <script>
        document.getElementById("selectAllLandlordCheckbox").addEventListener("change", function() {
            const checkboxes = document.querySelectorAll(".landlord-checkbox");
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            syncCheckboxes(); // ✅ Call this manually to update the buttons
        });
    </script>


    <script src="../js/script.js"></script>
    <script>

    </script>
    <?php if (isset($_GET['message']) && $_GET['message'] === 'signing_successful'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Sign Up Success',
                text: 'Emailed and Creation of Landlord Successful',
                confirmButtonText: 'OK'
            });
        </script>
    <?php endif; ?>

    <script>
        const tenantCheckboxes = document.querySelectorAll('.tenant-checkbox');
        const editForm = document.getElementById('bulkEditForm');
        const editUnselectedForm = document.getElementById('bulkEditUnselectedForm');
        const deleteForm = document.getElementById('bulkDeleteForm');
        const deleteUnselectedForm = document.getElementById('bulkUnselectedDeleteForm');
        const editBtn = document.getElementById('bulkEditBtn');
        const deleteBtn = document.getElementById('bulkDeleteBtn');
        const unselectedBtn = document.querySelector('#bulkEditUnselectedForm button');
        const deleteUnselectedBtn = document.getElementById('bulkUnselectedDeleteBtn');
        const selectAll = document.getElementById("selectAllCheckbox");

        function syncCheckboxes() {
            // Clear old hidden inputs
            [editForm, editUnselectedForm, deleteForm, deleteUnselectedForm].forEach(form => {
                form.querySelectorAll('input[name="selected_tenant[]"]').forEach(el => el.remove());
            });

            let anyChecked = false;
            let anyUnchecked = false;

            tenantCheckboxes.forEach(cb => {
                if (cb.checked) {
                    anyChecked = true;

                    // Selected -> Edit + Delete
                    const selectedInput = document.createElement('input');
                    selectedInput.type = 'hidden';
                    selectedInput.name = 'selected_tenant[]';
                    selectedInput.value = cb.value;
                    editForm.appendChild(selectedInput);

                    const deleteInput = selectedInput.cloneNode(true);
                    deleteForm.appendChild(deleteInput);
                } else {
                    anyUnchecked = true;

                    // Unselected -> Unselected Edit + Unselected Delete
                    const unselectedInput = document.createElement('input');
                    unselectedInput.type = 'hidden';
                    unselectedInput.name = 'selected_tenant[]';
                    unselectedInput.value = cb.value;
                    editUnselectedForm.appendChild(unselectedInput);

                    const delUnselectedInput = unselectedInput.cloneNode(true);
                    deleteUnselectedForm.appendChild(delUnselectedInput);
                }
            });

            // Enable/disable buttons
            editBtn.disabled = !anyChecked;
            deleteBtn.disabled = !anyChecked;
            unselectedBtn.disabled = !anyUnchecked;
            deleteUnselectedBtn.disabled = !anyUnchecked;

            // 🔹 Update Select All checkbox dynamically
            selectAll.checked = [...tenantCheckboxes].every(cb => cb.checked);
        }

        // 🔹 Handle Select All clicks
        selectAll.addEventListener("change", function() {
            tenantCheckboxes.forEach(cb => cb.checked = this.checked);
            syncCheckboxes();
        });

        // 🔹 Handle individual tenant checkbox clicks
        tenantCheckboxes.forEach(cb => {
            cb.addEventListener('change', syncCheckboxes);
        });

        // Run once on load (populate forms correctly)
        syncCheckboxes();
    </script>
</body>

</html>