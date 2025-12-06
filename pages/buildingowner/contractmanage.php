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
$status_color = '';
$message = $_GET['message'] ?? '';
$warning = $_SESSION['warning_for_renewal'] ?? '';
if (isset($_GET['message']) && $_GET['message'] === 'contract_updated') {
    $warning = 'Contract Updated';
}
if (isset($_GET['message']) && $_GET['message'] === 'email_successful') {
    $warning = 'Email Successful';
}
if (isset($_GET['message']) && $_GET['message'] === 'contract_terminated_bulk') {
    $warning = 'The contracts that have been approved by the tenants has been terminated and the tenants who do not approved has emailed for their confirmation of approval for termination';
}
if (isset($_GET['message']) && $_GET['message'] === 'contract_deleted') {
    $warning = 'Contract Deleted';
}
if (isset($_GET['message']) && $_GET['message'] === 'contract_inserted') {
    $warning = 'Contract Inserted';
}
if (isset($_GET['message']) && $_GET['message'] === 'permanent_contract_deleted') {
    $warning = 'Contract Archived has been Cleared';
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
    <title>Contract Management</title>
    <link rel="stylesheet" href="../css/rule4.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
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

        function confirmDelete(contract_id) {

            Swal.fire({
                icon: 'warning',
                title: 'Terminate Contract',
                text: 'Are you sure you want to terminate this contract?',
                showCancelButton: true,
                confirmButtonText: 'Terminate',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'contract_CRUD/delete_contract.php?id=' + contract_id;
                }
            });
        }



        function confirmPermanentDelete() {
            Swal.fire({
                icon: 'warning',
                title: 'Delete Contracts',
                text: 'Are you sure you want to delete all the data to this contracts, this will not be reverted?',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'contract_CRUD/delete_permanent_contract.php';
                }
            });
        }



        function confirmEmail(contract_id) {
            var msg = 'It seems this contract is still active, Do you want to notify this tenant to get their consent for deleting this contract?';
            Swal.fire({
                icon: 'question',
                title: 'Delete Contract Confirmation',
                text: msg,
                showCancelButton: true,
                confirmButtonText: 'Yes',
                cancelButtonText: 'No',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'contract_CRUD/emaildelete_contract.php?id=' + contract_id;
                } else {
                    location.reload();

                }
            });
        }
    </script>
    <script>
        document.getElementById('rowsPerPage').addEventListener('change', function() {
            const form = document.getElementById('filterForm');
            const pageInput = document.createElement('input');
            pageInput.type = 'hidden';
            pageInput.name = 'page';
            pageInput.value = 1;
            form.appendChild(pageInput);
            form.submit();
        });

        function confirmRenew(contract_id) {
            const renewalButton = document.getElementById('renewalButton');
            if (renewalButton.disabled) {
                return; // Prevent SweetAlert if button is disabled
            }

            const renewalForm = document.getElementById('renewForm');
            var msg = 'Are you sure, you want to renew your contract to these tenants?';
            Swal.fire({
                icon: 'question',
                title: 'Renew',
                text: msg,
                showCancelButton: true,
                confirmButtonText: 'Renew',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (result.isConfirmed) {
                    renewalForm.submit();
                } else {
                    location.reload();
                }
            });
        }
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
            <ul class="nav-link">
                <li><a href="ownerdashboard.php"><i class="fas fa-th-large"></i>
                        <p>DashBoard</p>
                    </a></li>
                <li><a href="building_management.php"><i class="fas fa-building"></i>
                        <p>Building Management</p>
                    </a></li>
                <li><a href="tenantmanage.php"><i class="fas fa-users"></i>
                        <p>User Access Management</p>
                    </a></li>
                <li><a href="#"><i class="fas fa-file-contract"></i>
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
                <h1>Contract <br>
                    Management</h1>
            </div>

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
                                        body.style.display = (body.style.display === 'none' || body.style.display === '') ? 'block' : 'none';
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
        <?php if (!empty($warning)): ?>
            <div class="alert alert-warning"><?php echo htmlspecialchars($warning); ?></div>
        <?php endif; ?>
        <div class="container mt-4">
            <div class="d-flex justify-content-center align-items-center mb-3">
                <form method="get" class="d-flex gap-2" style="max-width: 400px; width: 100%;">
                    <input type="text" class="form-control" name="search" placeholder="Search..."
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button class="btn btn-primary" type="submit">Search</button>
                </form>
            </div>

            <div class="table-responsive">
                <table id="rulesTable" class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Room Number</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Contract Status</th>
                            <th>Tenants</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $today = date('Y-m-d');
                        try {
                            $search = $_GET['search'] ?? '';
                            $searchClause = '';
                            if (!empty($search)) {
                                $searchSafe = mysqli_real_escape_string($db_connection, $search);
                                $searchClause = "(r.room_number LIKE '%$searchSafe%' OR 
                            c.start_date LIKE '%$searchSafe%' OR 
                            c.end_date LIKE '%$searchSafe%' OR 
                            c.contract_status LIKE '%$searchSafe%'
                        )";
                            }

                            $whereParts = [];
                            if (!empty($searchClause)) {
                                $whereParts[] = $searchClause;
                            }

                            $finalWhere = 'WHERE c.contract_status = "Active" ';
                            if (!empty($whereParts)) {
                                $finalWhere = 'WHERE c.contract_status = "Active" AND' . implode(' AND ', $whereParts);
                            }

                            $sql = "SELECT c.contract_status, c.start_date, c.end_date, r.room_number, us.room_id 
                                FROM contract c
                                JOIN userall us ON us.user_id = c.user_id
                                JOIN room r ON us.room_id = r.room_id
                                $finalWhere
                                GROUP BY us.room_id
                                ORDER BY c.contract_id $limitClause";


                            $tenant_modal = "";
                            $result_query = mysqli_query($db_connection, $sql);

                            while ($result = mysqli_fetch_array($result_query)) {
                                // Status color
                                if ($result['contract_status'] === 'Active') {
                                    $status_color = 'success';
                                } elseif (in_array($result['contract_status'], ['Inactive', 'Expired', 'Deleted'])) {
                                    $status_color = 'danger';
                                } elseif ($result['contract_status'] === 'Pending') {
                                    $status_color = 'warning';
                                }

                                $string_start_date = date("F j, Y", strtotime($result['start_date']));
                                $string_end_date   = date("F j, Y", strtotime($result['end_date']));

                                echo '<tr>
            <td>' . $result['room_number'] . '</td>
            <td>' . $string_start_date . '</td>
            <td>' . $string_end_date . '</td>
            <td>
                <span class="badge bg-light text-' . $status_color . '">
                    <i class="fas fa-circle text-' . $status_color . ' me-1"></i> ' . $result['contract_status'] . '
                </span>
            </td>
            <td>
            
                <button type="button" class="btn btn-outline-primary btn-sm" title="View Room Contracts"
                    data-bs-toggle="modal" data-bs-target="#contractRoomModal' . $result['room_id'] . '">
                    <i class="fas fa-users"></i>
                </button>
            </td>
        </tr>';

                                // Build modal
                                $tenant_modal .= '
                    <div class="modal fade" id="contractRoomModal' . $result['room_id'] . '" tabindex="-1">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content shadow-lg border-0 rounded-3">
                                <div class="modal-header">
                                    <h5 class="modal-title"><i class="fas fa-file-contract me-2"></i> Contracts for Room ' . $result['room_number'] . '</h5>
                                    <button type="button" class="btn-close btn-close-black" data-bs-dismiss="modal"></button>
                                </div>

                                <div class="modal-body" style="font-family: Georgia, serif; line-height:1.6;">
                                    <form id="bulkDeleteForm' . $result['room_id'] . '" method="post" action="contract_CRUD/delete_contract_bulk.php">
                                        <div class="mb-4 p-3 bg-light rounded d-flex align-items-center shadow-sm">
                                            <input type="checkbox" id="selectAllCheckbox' . $result['room_id'] . '" 
                                                class="form-check-input me-2 select-all-room" 
                                                data-room="' . $result['room_id'] . '">
                                            <label for="selectAllCheckbox' . $result['room_id'] . '" 
                                                class="form-check-label fw-semibold">Select All Contracts</label>
                                        </div>

                                        <div class="row g-4">';

                                // ✅ Separate Active & Expired Contracts
                                $sql_room = "SELECT a.*, b.first_name, b.phone_number, b.last_name, b.user_id, c.room_number, d.room_id,
                                                        CASE
                                                            WHEN '$today' BETWEEN a.start_date AND a.end_date THEN 'Active'
                                                            WHEN '$today' > a.end_date THEN 'Expired'
                                                            ELSE 'Upcoming'
                                                        END AS contract_period
                                                    FROM contract a
                                                    JOIN personal_info b ON b.user_id = a.user_id
                                                    JOIN user d ON d.user_id = a.user_id
                                                    JOIN room c ON d.room_id = c.room_id
                                                    WHERE a.contract_status != 'Deleted' 
                                                    AND d.room_id = " . (int)$result['room_id'] . "
                                                    ORDER BY a.contract_id DESC";

                                $contracts_query = mysqli_query($db_connection, $sql_room);

                                $active_cards = '';
                                $expired_cards = '';

                                if ($contracts_query && mysqli_num_rows($contracts_query) > 0) {
                                    while ($contract = mysqli_fetch_array($contracts_query)) {
                                        $contract_period = $contract['contract_period'];

                                        // Only show Active and Expired
                                        if (!in_array($contract_period, ['Active', 'Expired'])) continue;

                                        $termsArray = explode(', ', $contract['terms']);
                                        $termLabels = [
                                            'deposit_return'         => '- Security deposit is refundable upon move-out, minus any damages.',
                                            'on_time_rent'           => '- Rent must be paid on or before the due date each month.',
                                            'no_subleasing'          => '- Subleasing without landlord approval is not allowed.',
                                            'utility_responsibility' => '- Tenant is responsible for all utility bills.',
                                            'notice_required'        => '- A 30-day notice is required before moving out.',
                                            'property_care'          => '- Tenant must maintain cleanliness and avoid damaging the property.'
                                        ];
                                        $displayTerms = array_map(fn($t) => $termLabels[$t] ?? $t, $termsArray);

                                        $status_color = ($contract_period === 'Active') ? 'success' : 'danger';

                                        $card_html = '
                                            <div class="col-md-6">
                                                <div class="card border-0 shadow-sm h-100">
                                                    <div class="card-body d-flex flex-column justify-content-between">
                                                        <div>
                                                            <h6 class="fw-bold text-dark mb-1">' . $contract['first_name'] . ' ' . $contract['last_name'] . '</h6>
                                                            <p class="mb-1"><strong>Contact:</strong> ' . $contract['phone_number'] . '</p>
                                                            <p class="mb-1"><strong>Start:</strong> ' . date("F j, Y", strtotime($contract['start_date'])) . '</p>
                                                            <p class="mb-1"><strong>End:</strong> ' . date("F j, Y", strtotime($contract['end_date'])) . '</p>
                                                            <p class="mb-2"><strong>Terms:</strong><br>' . implode('<br>', $displayTerms) . '</p>
                                                            <span class="badge bg-' . $status_color . '">
                                                                <i class="fas fa-circle me-1"></i> ' . $contract_period . '
                                                            </span>
                                                        </div>
                                                        <div class="mt-3 d-flex justify-content-between align-items-center">';

                                        if ($contract_period === 'Active') {
                                            $card_html .= '<a href="contract_CRUD/export_contract_pdf.php?contract_id=' . $contract['contract_id'] . '" 
                                                    target="_blank" class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-file-pdf me-1"></i> Export PDF
                                                </a>';
                                        }

                                        $card_html .= '<div class="form-check">
                                                            <input type="checkbox" class="form-check-input tenant-checkbox" 
                                                                name="selected_contract[]" 
                                                                value="' . $contract['contract_id'] . '">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            </div>';

                                        if ($contract_period === 'Active') {
                                            $active_cards .= $card_html;
                                        } else {
                                            $expired_cards .= $card_html;
                                        }
                                    }

                                    // --- Output grouped cards ---
                                    if ($active_cards) {
                                        $tenant_modal .= '<h6 class="fw-bold text-success mb-3">🟢 Active Contracts</h6><div class="row g-3">' . $active_cards . '</div><hr>';
                                    }
                                    if ($expired_cards) {
                                        $tenant_modal .= '<h6 class="fw-bold text-danger mb-3">🔴 Expired Contracts</h6><div class="row g-3">' . $expired_cards . '</div>';
                                    }
                                } else {
                                    $tenant_modal .= '
                                        <div class="col-12">
                                            <div class="alert alert-secondary mb-0">No contracts found for this room.</div>
                                        </div>';
                                }

                                $tenant_modal .= '
                                                </div>
                                                <div class="text-end mt-4">
                                                    <button type="button" name="bulkDelete" class="btn btn-danger" 
                                                            id="bulkDeleteBtn' . $result['room_id'] . '" 
                                                            onclick="confirmBulkDelete(' . $result['room_id'] . ')" disabled>
                                                        <i class="fas fa-user-slash"></i> Terminate Selected
                                                    </button>
                                                </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
<script>
function confirmBulkDelete(roomId) {

    const bulkDeleteForm = document.getElementById(`bulkDeleteForm${roomId}`);

    Swal.fire({
        icon: "warning",
        title: "Terminate Contracts",
        text: "Are you sure you want to Terminate these Contracts?",
        showCancelButton: true,
        confirmButtonText: "Yes, delete it!",
    }).then((result) => {
        if (result.isConfirmed) {
            bulkDeleteForm.submit();
        }
    });
}

document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".select-all-room").forEach(selectAll => {
        const roomId = selectAll.dataset.room;
        const tenantCheckboxes = document.querySelectorAll(`#contractRoomModal${roomId} .tenant-checkbox`);
        const bulkDeleteBtn = document.querySelector(`#contractRoomModal${roomId} #bulkDeleteBtn${roomId}`);

        function toggleDeleteButton() {
            const checkedBoxes = document.querySelectorAll(`#contractRoomModal${roomId} .tenant-checkbox:checked`);
            bulkDeleteBtn.disabled = checkedBoxes.length === 0;
        }

        // Select All
        selectAll.addEventListener("change", function() {
            tenantCheckboxes.forEach(cb => cb.checked = this.checked);
            toggleDeleteButton();
        });

        // Individual checkboxes
        tenantCheckboxes.forEach(cb => {
            cb.addEventListener("change", function() {
                if (!this.checked) {
                    selectAll.checked = false;
                } else if (document.querySelectorAll(`#contractRoomModal${roomId} .tenant-checkbox:checked`).length === tenantCheckboxes.length) {
                    selectAll.checked = true;
                }
                toggleDeleteButton();
            });
        });

        toggleDeleteButton();
    });
});
</script>';
                            }

                            echo $tenant_modal;
                        } catch (Exception $e) {
                            echo "Error: " . $e->getMessage();
                        }
                        $countQuery = "SELECT COUNT(*) as total FROM contract c
               JOIN personal_info b ON b.user_id = c.user_id
               JOIN user d ON d.user_id = c.user_id
               JOIN room r ON d.room_id = r.room_id
               $finalWhere";
                        $countResult = mysqli_query($db_connection, $countQuery);


                        $totalRows = mysqli_fetch_assoc($countResult)['total'];
                        $totalPages = ceil($totalRows / $rowsPerPage);
                        $filter_status = "";


                        ?>
                    </tbody>
                    </form>
                </table>
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
                            <button type="submit" class="btn btn-primary" name="viewAll" value="1">
                                View All Contracts
                            </button>
                        </form>
                    </div>

                    <div class="col-auto">
                        <div class="position-relative">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#contractrenewalModal">
                                Renew Contract
                            </button>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php
                                $sql123 = 'SELECT COUNT(*) as tenant FROM userall WHERE account_status = "Waiting to Renew"';
                                $rs123  = mysqli_query($db_connection, $sql123);
                                $rw123 = mysqli_fetch_array($rs123);
                                ?>
                                <?php echo $rw123['tenant']; ?>
                                <span class="visually-hidden">unread messages</span>
                            </span>
                        </div>
                    </div>

                    <div class="col-auto">
                        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#deletedUsersModal">
                            Show Deleted Contracts
                        </button>
                    </div>

                    <div class="col-auto">
                        <button type="button" onclick="window.location.href='contract_CRUD/addcontracts.php'" class="btn btn-success">
                            Add Contract
                        </button>
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
                        <div class="step-icon"><i class="fa-solid fa-user-pen"></i></div>
                        <h3>How to Add Contracts</h3>
                        <div class="step-layout">
                            <div class="step-content">
                                <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                    Step 1: Click the "Add Contract"
                                </h4>
                                <img src="../images/arrow_addContract_help.png" alt="Add Building Screenshot" class="step-image">
                                <p>The <strong>Add Contract</strong> button lets you create a new tenant contract. You can also view all contracts, renew existing ones, or see deleted contracts for better management.</p>
                            </div>
                        </div>


                        <div class="step-layout">
                            <div class="step-content">
                                <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                    Step 2: Adding Contract
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
                </div>


                <!-- Step 2 -->
                <div id="step2" class="tutorial-step">
                    <div class="step-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                    <h3>How to Renew Tenant Contract</h3>
                    <div class="step-layout">
                        <div class="step-content">
                            <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                Step 1: Click the “Renew Contract” Button
                            </h4>
                            <img src="../images/arrow_renew_help.png" alt="Add Building Screenshot" class="step-image">
                            <p>The <strong>Renew Contract</strong> button allows you to extend an existing tenant’s contract. You can also edit both selected and unselected contracts if updates are needed.</p>
                        </div>
                    </div>

                    <div class="step-layout">
                        <div class="step-content">
                            <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                Step 2: Renew Contract
                            </h4>
                            <img src="../images/renew_modal_help.png" alt="Add Building Screenshot" class="step-image">
                            <p>The <strong>Renewal of Contract</strong> modal allows you to view and manage tenant renewal requests. If no tenants are available for renewal, a message will appear indicating that there are no active requests.</p>




                            <div class="step-highlight">
                                <i class="fa-solid fa-lightbulb"></i>
                                <span>You can access this help anytime by clicking the Help button.</span>
                            </div>
                        </div>
                    </div>

                </div>

                <div id="step3" class="tutorial-step">
                    <div class="step-icon"><i class="fa-solid fa-user-pen"></i></div>
                    <h3>How to View Tenant Contracts</h3>
                    <div class="step-layout">
                        <div class="step-content">
                            <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                Step 1: Click the "Icon View Tenant Contract" button
                            </h4>
                            <img src="../images/arrow_viewcontract_help.png" alt="Add Building Screenshot" class="step-image">
                            <p>The <strong>View Room Contracts</strong> button allows you to see detailed information about the tenants assigned to a specific room and their contract details.</p>
                        </div>
                    </div>

                    <div class="step-highlight">
                        <i class="fa-solid fa-lightbulb"></i>
                        <span>You can access this help anytime by clicking the Help button.</span>
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

    <div class="modal fade" id="contractrenewalModal" tabindex="-1" aria-labelledby="renewalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-md">
            <div class="modal-content p-4 border border-primary rounded-4">
                <div class="modal-header border-0">
                    <h2 class="modal-title fw-bold" id="renewalModalLabel">
                        <em>Renewal of Contract</em>
                    </h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-0">
                    <h5 class="fw-semibold mb-4">Contract Details</h5>
                    <div class="mb-4">
                        <form action="tenant_CRUD/renewtenants.php" method="post" id="renewForm">
                            <label for="desired_room" class="form-label">Select Room (Grouped by Tenants who has Requesting to Renew their Contract)</label>
                            <select id="desired_room" name="desired_room" class="form-control" required>
                                <?php
                                $query = mysqli_query($db_connection, 'SELECT a.desired_room, a.first_name, a.last_name, b.room_number, a.role
                                        FROM userall a
                                        JOIN room b ON a.desired_room = b.room_id   
                                        WHERE a.account_status = "Waiting to Renew" AND a.role = "Tenant"');

                                $groupedRooms = [];

                                while ($row = mysqli_fetch_assoc($query)) {
                                    $roomId = $row['desired_room'];
                                    $roomNumber = $row['room_number'];
                                    $fullName = $row['first_name'] . ' ' . $row['last_name'];

                                    if (!isset($groupedRooms[$roomId])) {
                                        $groupedRooms[$roomId] = [
                                            'room_number' => $roomNumber,
                                            'names' => [],
                                        ];
                                    }

                                    if (!in_array($fullName, $groupedRooms[$roomId]['names'])) {
                                        $groupedRooms[$roomId]['names'][] = $fullName;
                                    }
                                }
                                if ($groupedRooms) {
                                    echo '<option value="" disabled selected>-- Select Tenants to Renew --</option>';
                                } else {
                                    echo '<option disabled selected>-- No tenants available for contract renewal --</option>';
                                }
                                foreach ($groupedRooms as $roomId => $data) {
                                    $label = 'Room ' . $data['room_number'] . ': ' . implode(', ', $data['names']);

                                    echo '<option value="' . $roomId . '">' . htmlspecialchars($label) . '</option>';
                                }
                                $disabled_button = "";
                                if ($groupedRooms) {
                                    $disabled_button = "";
                                } else {
                                    $disabled_button = "disabled";
                                }
                                ?>
                            </select>
                    </div>

                    <div class="d-flex justify-content-end gap-3">

                        <button type="button" name="renew_button" id="renewalButton" class="btn btn-primary px-4" onclick="confirmRenew()" <?php echo $disabled_button; ?>>Renew Contracts</button>

                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="deletedUsersModal" tabindex="-1" aria-labelledby="deletedUsersModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content shadow-lg rounded-3">

                <!-- Header -->
                <div class="modal-header">
                    <h5 class="modal-title" id="deletedUsersModalLabel">
                        <i class="fas fa-file-contract me-2"></i><strong>Deleted Contracts</strong>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    <!-- Action Buttons -->
                    <div class="d-flex gap-2 mb-3">
                        <form action="contract_CRUD/exportpdfdeletedcontracts.php" method="post" class="flex-fill" target="_blank">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="fas fa-file-pdf me-2"></i> Generate PDF
                            </button>
                        </form>
                        <button type="button" class="btn btn-outline-danger flex-fill" onclick="confirmPermanentDelete()">
                            <i class="fas fa-trash-alt me-2"></i> Permanent Delete
                        </button>
                    </div>

                    <!-- Search Bar (Adjusted Width) -->
                    <form method="GET" class="mb-3 d-flex justify-content-center">
                        <div class="input-group" style="max-width: 600px; width: 100%;">
                            <input type="text" name="search_delete_contracts" class="form-control"
                                placeholder="Search tenant, email, or contract details"
                                value="<?php echo isset($_GET['search_delete_contracts']) ? htmlspecialchars($_GET['search_delete_contracts']) : ''; ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </form>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr class="text-center">
                                    <th>Tenant Name</th>
                                    <th>Start Date</th>
                                    <th>Lease End Date</th>
                                    <th>Terms</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $search = isset($_GET['search_delete_contracts']) ? trim($_GET['search_delete_contracts']) : '';
                                $sql = "SELECT a.*, b.first_name, b.last_name, b.user_id, b.email, b.role
                      FROM contract a
                      JOIN userall b ON b.user_id = a.user_id
                      WHERE a.contract_status = 'Deleted'";

                                if (!empty($search)) {
                                    $search_escaped = mysqli_real_escape_string($db_connection, $search);
                                    $sql .= " AND (
                      b.first_name LIKE '%$search_escaped%' 
                      OR b.last_name LIKE '%$search_escaped%' 
                      OR b.email LIKE '%$search_escaped%'
                      OR b.role LIKE '%$search_escaped%'
                      OR a.contract_id LIKE '%$search_escaped%'
                  )";
                                }
                                $result_query = mysqli_query($db_connection, $sql);

                                while ($result = mysqli_fetch_array($result_query)) {
                                    $termLabels = [
                                        'deposit_return' => '- Security deposit is refundable upon move-out, minus any damages.',
                                        'on_time_rent' => '- Rent must be paid on or before the due date each month.',
                                        'no_subleasing' => '- Subleasing without landlord approval is not allowed.',
                                        'utility_responsibility' => '- Tenant is responsible for all utility bills.',
                                        'notice_required' => '- A 30-day notice is required before moving out.',
                                        'property_care' => '- Tenant must maintain cleanliness and avoid damaging the property.'
                                    ];

                                    $termsArray = explode(', ', $result['terms']);
                                    $displayTerms = array_map(fn($term) => $termLabels[$term] ?? $term, $termsArray);
                                    $string_start_date = date("F j, Y", strtotime($result['start_date']));
                                    $string_end_date = date("F j, Y", strtotime($result['end_date']));

                                    echo '<tr>
                          <td>' . $result['first_name'] . ' ' . $result['last_name'] . '</td>
                          <td>' . $string_start_date . '</td>
                          <td>' . $string_end_date . '</td>
                          <td>' . implode('<br>', $displayTerms) . '</td>
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



    <script src="../js/script.js"></script>
    <?php if (isset($_GET['message']) && $_GET['message'] === 'contract_updated'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Contracts Updated',
                text: 'Approved tenants were updated. Others were emailed for confirmation.',
                confirmButtonText: 'OK'
            });
        </script>
    <?php endif; ?>
    <?php if (isset($_GET['message']) && $_GET['message'] === 'contract_terminated'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Contracts Terminated',
                text: 'Approved tenants were terminated. Others were emailed for confirmation.',
                confirmButtonText: 'OK'
            });
        </script>
    <?php endif; ?>


</body>

</html>