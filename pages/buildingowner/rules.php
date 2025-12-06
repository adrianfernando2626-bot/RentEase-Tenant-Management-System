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
$message = $_GET['message'] ?? '';
$warning = "";
$sql = 'SELECT * FROM userall WHERE user_id = ' . $user_id;
$rs = mysqli_query($db_connection, $sql);
$rw = mysqli_fetch_array($rs);

if (isset($_GET['message']) && $_GET['message'] === 'rules_updated') {
    $warning = 'Rules has been Updated';
}

if (isset($_GET['message']) && $_GET['message'] === 'rules_deleted') {
    $warning = 'Rules has been Deleted';
}

if (isset($_GET['message']) && $_GET['message'] === 'rules_inserted') {
    $warning = 'Rules has been Added';
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
    <title>Rules Management</title>
    <link rel="stylesheet" href="../css/rule4.css">
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
        function confirmDelete(rules_id) {

            Swal.fire({
                icon: 'warning',
                title: 'Delete Rule',
                text: 'Are you sure you want to delete this rule?',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'rules_CRUD/delete_rules.php?id=' + rules_id;
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
                title: 'Delete Rules',
                text: 'Are you sure you want to Delete these Rules?',
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
                title: 'Delete Rules',
                text: 'Are you sure you want to Delete these Rules?',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    bulkDeleteForm.submit();
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
                <li><a href="contractmanage.php"><i class="fas fa-file-contract"></i>
                        <p>Contract Management</p>
                    </a></li>
                <li><a href="#"><i class="fas fa-file-lines"></i>
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
                <h1>Rules <br>
                    Management</h1>
            </div>
            <?php if (!empty($warning)): ?>
                <div class="alert alert-warning"><?php echo htmlspecialchars($warning); ?></div>
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

        <div class="container mt-4">
            <div class="d-flex justify-content-center align-items-center mb-3">
                <form method="get" class="d-flex gap-2" style="max-width: 400px; width: 100%;">
                    <input type="text" class="form-control" name="search" placeholder="Search..."
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
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
                                <th>Rule Title</th>
                                <th>Rule Description</th>
                                <th>Status</th>
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
                                                    rules_id = '$searchSafe' OR 
                                                    title LIKE '%$searchSafe%' OR 
                                                    rules_status LIKE '%$searchSafe%'
                                                )";
                                }

                                $whereParts = [];
                                if (!empty($searchClause)) {
                                    $whereParts[] = $searchClause;
                                }

                                $finalWhere = '';
                                if (!empty($whereParts)) {
                                    $finalWhere = 'WHERE ' . implode(' AND ', $whereParts);
                                }

                                // Group by building for easy display
                                $sql = "SELECT r.*, b.name 
                                    FROM rules r
                                    JOIN building b ON b.building_id = r.building_id
                                    $finalWhere 
                                    ORDER BY b.name, r.rules_id";

                                $result_query = mysqli_query($db_connection, $sql);

                                $currentBuilding = null;

                                while ($result = mysqli_fetch_array($result_query)) {
                                    // When building changes, print a header with toggle button
                                    if ($currentBuilding !== $result['name']) {
                                        if ($currentBuilding !== null) {
                                            // Close previous building's tbody
                                            echo '</tbody>';
                                        }

                                        $currentBuilding = $result['name'];
                                        $buildingId = preg_replace('/\s+/', '_', strtolower($currentBuilding));

                                        echo '
                                            <tr class="table-secondary">
                                                <td colspan="5" style="font-weight:bold;">
                                                    <button class="btn btn-sm btn-outline-primary toggle-building" 
                                                            type="button" 
                                                            data-target="building-' . $buildingId . '">
                                                        ⬇️ ' . htmlspecialchars($currentBuilding) . '
                                                    </button>
                                                </td>
                                            </tr>
                                            <tbody id="building-' . $buildingId . '" class="building-group">';
                                    }

                                    // Status color
                                    if ($result['rules_status'] === 'Active') {
                                        $status_color = 'success';
                                    } elseif ($result['rules_status'] === 'Inactive') {
                                        $status_color = 'danger';
                                    } elseif ($result['rules_status'] === 'Pending') {
                                        $status_color = 'warning';
                                    } else {
                                        $status_color = 'secondary';
                                    }

                                    echo '
                                            <tr>
                                                <td class="text-center">
                                                    <input type="checkbox" class="tenant-checkbox" 
                                                        name="selected_rules[]" 
                                                        value="' . $result['rules_id'] . '">
                                                </td>
                                                <td>' . htmlspecialchars($result['title']) . '</td>
                                                <td>' . htmlspecialchars($result['rules_description']) . '</td>
                                                <td>
                                                    <span class="badge bg-light text-' . $status_color . '">
                                                        <i class="fas fa-circle text-' . $status_color . ' me-1"></i> 
                                                        ' . htmlspecialchars($result['rules_status']) . '
                                                    </span>
                                                </td>
                                            </tr>';
                                }

                                if ($currentBuilding !== null) {
                                    echo '</tbody>';
                                }
                            } catch (Exception $e) {
                                echo "<tr><td colspan='5'>Error: " . $e->getMessage() . "</td></tr>";
                            }
                            ?>
                        </tbody>

                    </table>
                </div>
                <div class="mb-2">
                    <button type="button" id="showAllBtn" class="btn btn-success btn-sm">Show All Rules</button>
                    <button type="button" id="hideAllBtn" class="btn btn-secondary btn-sm">Hide All Rules</button>
                </div>

            </form>
            <div class="action-buttons">
                <form id="bulkEditForm" method="post" action="rules_CRUD/edit_rules_bulk.php">
                    <button type="submit" name="bulkUpdate" class="btn btn-edit" id="bulkEditBtn" disabled>
                        ✏️ Edit Selected
                    </button>
                </form>

                <form id="bulkDeleteForm" method="post" action="rules_CRUD/delete_rules_bulk.php">
                    <button type="submit" name="bulkDelete" class="btn btn-delete" id="bulkDeleteBtn" onclick="confirmBulkDelete()" disabled>
                        🗑️ Delete Selected
                    </button>
                </form>

                <form id="bulkEditUnselectedForm" method="post" action="rules_CRUD/edit_rules_bulk.php">
                    <button type="submit" name="UnselectedbulkUpdate" class="btn btn-edit" id="bulkEditUnselectedBtn">
                        ✏️ Edit Unselected
                    </button>
                </form>

                <form id="bulkUnselectedDeleteForm" method="post" action="rules_CRUD/delete_rules_bulk.php">
                    <button type="button" name="bulkDelete" class="btn btn-delete" id="bulkUnselectedDeleteBtn" onclick="confirmBulkUnselectedDelete()">
                        🗑️ Delete Unselected
                    </button>
                </form>
            </div>



            <div class="d-flex justify-content-center align-items-center mt-3 gap-2">

                <button type="button" onclick="window.location.href='rules_CRUD/addrules.php'" class="btn btn-success">
                    Add New Rule
                </button>
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
                        <h3>How to Add Rules</h3>
                        <div class="step-layout">
                            <div class="step-content">
                                <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                    Step 1: Click the "Add Rule"
                                </h4>
                                <img src="../images/arrow_addrule_help.png" alt="Add Building Screenshot" class="step-image">
                                <p>The <strong>Add New Rule</strong> button allows administrators to create a new rule for a specific building. This helps in managing and organizing building regulations efficiently.</p>
                            </div>
                        </div>


                        <div class="step-layout">
                            <div class="step-content">
                                <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                    Step 2: Adding Rule
                                </h4>
                                <img src="../images/add_rule_help.png" alt="Add Building Screenshot" class="step-image">
                                <p>The <strong>Add a New Rule</strong> form allows administrators to create a new rule by selecting a building, entering the rule title, and providing a description. Once completed, the changes can be saved or canceled.</p>



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
                    <h3>How to Edit Existing Rule</h3>
                    <div class="step-layout">
                        <div class="step-content">
                            <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                Step 1: Manage Rule Selection
                            </h4>
                            <img src="../images/arrow_editrule_help.png" alt="Add Building Screenshot" class="step-image">
                            <p>The <strong>Rules Management</strong> page allows administrators to manage building rules. Users can select specific rules using checkboxes and perform actions such as <em>Edit Selected</em>, <em>Delete Selected</em>, <em>Edit Unselected</em>, or <em>Delete Unselected</em>.</p>
                        </div>
                    </div>

                    <div class="step-layout">
                        <div class="step-content">
                            <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                Step 2: Editing Rule
                            </h4>
                            <img src="../images/edit_rule_help.png" alt="Add Building Screenshot" class="step-image">
                            <p>The <strong>Edit Selected Rule</strong> page allows the user to modify an existing rule’s details. You can update the rule title, description, and status. Once done, click <em>Save All Changes</em> to apply the updates or <em>Cancel</em> to discard them.</p>




                            <div class="step-highlight">
                                <i class="fa-solid fa-lightbulb"></i>
                                <span>You can access this help anytime by clicking the Help button.</span>
                            </div>
                        </div>
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
            const totalSteps = 2;

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
                form.querySelectorAll('input[name="selected_rules[]"]').forEach(el => el.remove());
            });

            let anyChecked = false;
            let anyUnchecked = false;

            tenantCheckboxes.forEach(cb => {
                if (cb.checked) {
                    anyChecked = true;

                    // Selected -> Edit + Delete
                    const selectedInput = document.createElement('input');
                    selectedInput.type = 'hidden';
                    selectedInput.name = 'selected_rules[]';
                    selectedInput.value = cb.value;
                    editForm.appendChild(selectedInput);

                    const deleteInput = selectedInput.cloneNode(true);
                    deleteForm.appendChild(deleteInput);
                } else {
                    anyUnchecked = true;

                    // Unselected -> Unselected Edit + Unselected Delete
                    const unselectedInput = document.createElement('input');
                    unselectedInput.type = 'hidden';
                    unselectedInput.name = 'selected_rules[]';
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggles = document.querySelectorAll('.toggle-building');
            const showAllBtn = document.getElementById('showAllBtn');
            const hideAllBtn = document.getElementById('hideAllBtn');

            toggles.forEach(btn => {
                btn.addEventListener('click', function() {
                    const target = document.getElementById(this.dataset.target);
                    if (target.style.display === 'none' || !target.style.display) {
                        target.style.display = 'table-row-group';
                        this.textContent = '⬆️ ' + this.textContent.replace('⬇️', '').trim();
                    } else {
                        target.style.display = 'none';
                        this.textContent = '⬇️ ' + this.textContent.replace('⬆️', '').trim();
                    }
                });
            });

            showAllBtn.addEventListener('click', () => {
                document.querySelectorAll('.building-group').forEach(g => g.style.display = 'table-row-group');
                toggles.forEach(b => b.textContent = b.textContent.replace('⬇️', '⬆️'));
            });

            hideAllBtn.addEventListener('click', () => {
                document.querySelectorAll('.building-group').forEach(g => g.style.display = 'none');
                toggles.forEach(b => b.textContent = b.textContent.replace('⬆️', '⬇️'));
            });
        });
    </script>
    <script src="../js/script.js"></script>
</body>

</html>