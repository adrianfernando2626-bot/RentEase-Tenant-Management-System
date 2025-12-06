<?php
session_start();

// Include database connection
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../includes/database.php')) {
    include_once('../includes/database.php');
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

if (isset($_GET['id'])) {
    $_SESSION['id'] = $_GET['id'] ?? 0;
}
$id = $_SESSION['id']  ?? 0;
$sql = 'SELECT name, number_of_floors FROM building WHERE building_id = ' . $id;
$rs = mysqli_query($db_connection, $sql);
$rw = mysqli_fetch_array($rs);
$disabled_inputs = "";
$host = "localhost";
$username = "root";
$password = "";
$database = "apartment";
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle delete
if (isset($_POST['delete_room_id'])) {
    $room_id = $_POST['delete_room_id'];
    $conn->query("DELETE FROM room WHERE room_id = $room_id");
    header("Location: room_management.php?deleted=1");
    exit();
}

// Handle update
if (isset($_POST['edit_room_id'])) {

    $room_id = $_POST['edit_room_id'];
    $room_no = $_POST['edit_room_no'];
    $room_price = $_POST['edit_room_price'];
    $capacity = $_POST['edit_capacity'];
    $floor_number = $_POST['floor_number'];
    $status = $_POST['edit_status'];


    $stmt = $conn->prepare("UPDATE room SET room_number=?, floor_number = ?, room_amount=?, capacity=?, room_status=? WHERE room_id=?");
    $stmt->bind_param("sidisi", $room_no, $floor_number, $room_price, $capacity, $status, $room_id);
    $stmt->execute();
    header("Location: room_management.php?updated=1");
    exit();
}

// Handle search and pagination
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$searchQuery = 'WHERE building_id = ' . $id;
if (!empty($search)) {
    $escaped = $conn->real_escape_string($search);
    $searchQuery = "WHERE building_id = $id AND (
        room_id LIKE '%$escaped%' OR 
        building_id LIKE '%$escaped%' OR 
        room_amount LIKE '%$escaped%' OR 
        room_number LIKE '%$escaped%' OR 
        capacity LIKE '%$escaped%' OR 
        room_status LIKE '%$escaped%'
    )";
}
$totalRooms = $conn->query("SELECT COUNT(*) AS total FROM room $searchQuery")->fetch_assoc()['total'];
$totalPages = ceil($totalRooms / $limit);

$rooms = $conn->query("SELECT * FROM room $searchQuery LIMIT $limit OFFSET $offset");
?>

<!-- HTML starts below (unchanged structure except additions in container) -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management</title>
    <link rel="stylesheet" href="../css/styledashowner4.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    <script>
        function updateHiddenStatus(id) {
            const select = document.getElementById('selectStatus' + id);
            const hidden = document.getElementById('hiddenStatus' + id);
            hidden.value = select.value;
        }
    </script>

</head>

<body>
    <div class="side-bar">
        <div class="burger-btn" id="burgerBtn">
            <i class="fas fa-bars"></i>
        </div>
        <a href="" class="logo">
            <img src="../images/Logo No Background Black.png" alt="" class="logo-img">
            <img src="../images/Copy of Logo No Background.png" alt="" class="logo-icon">
        </a>
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
                <h2><?php echo $rw['name'] ?> Rooms</h2>
                <p><?php echo date('D, d M Y'); ?></p>
            </div>
            <button class="help-btn" id="helpBtn" title="Help Center" onclick="openHelpModal();">
                <i class="fas fa-question-circle"></i>
                <span>Help</span>
            </button>
        </div>

        <div class="container mt-4">
            <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Successfully deleted.</div><?php endif; ?>
            <?php if (isset($_GET['updated'])): ?><div class="alert alert-info">Successfully updated.</div><?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'room_inserted'): ?><div class="alert alert-warning">Successfully Inserted.</div><?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'room_number_taken'): ?><div class="alert alert-warning">Room Number is already used.</div><?php endif; ?>
            <?php if (isset($_GET['message']) && $_GET['message'] === 'name_taken'): ?><div class="alert alert-warning">Name of Utility Type is already taken.</div><?php endif; ?>

            <!-- Search bar -->

            <div class="d-flex justify-content-center mb-3">
                <form class="d-flex w-50" method="GET" action="room_management.php">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search"
                        value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-secondary" type="submit">Search</button>
                </form>
            </div>

            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-success" style="margin-right: 10px;" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                    <i class="fas fa-plus me-1"></i> Add Room
                </button>

                <!-- Button to Open Manage Utility Modal -->
                <button type="button" style="margin-right: 10px;" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#manageUtilityTypeModal">
                    <i class="bi bi-gear"></i> Manage Utility Types
                </button>

                <a href="building_management.php" class="btn btn-primary">Back</a>

            </div>
            <!-- Manage Utility Types Modal -->

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Room No.</th>
                            <th>Floor No.</th>
                            <th>Price</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $modals = ''; // Store all modals here

                        if ($rooms->num_rows > 0):
                            while ($room = $rooms->fetch_assoc()):
                                $status_color = '';
                                if ($room['room_status'] === 'Occupied') {
                                    $status_color = 'warning';
                                } elseif ($room['room_status'] === 'Available') {
                                    $status_color = 'success';
                                }

                        ?>
                                <tr>
                                    <td><?= htmlspecialchars($room['room_number']) ?></td>
                                    <td><?= htmlspecialchars($room['floor_number']) ?></td>
                                    <td>₱<?= number_format($room['room_amount'], 2) ?></td>
                                    <td><?= $room['capacity'] ?></td>
                                    <td><span class="badge bg-light text-<?php echo  $status_color; ?>">
                                            <i class="fas fa-circle text-<?php echo  $status_color; ?> me-1"></i> <?= $room['room_status'] ?>
                                        </span></td>

                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editRoomModal<?= $room['room_id'] ?>"><i class="fas fa-edit"></i></button>
                                        <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $room['room_id'] ?>"><i class="fas fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php
                                $disabled_inputs = $room['room_status'] === 'Occupied' ? 'readonly' : '';
                                $disabled_status = $room['room_status'] === 'Occupied' ? 'disabled' : '';
                                $floor_number = $rw['number_of_floors'];

                                $options = '';
                                for ($i = $floor_number; $i >= 1; $i--) {
                                    $selected = ($i == $room['floor_number']) ? 'selected' : '';
                                    $options .= "<option value='$i' $selected>$i</option>";
                                }

                                $modals .= '
<!-- Edit Modal -->
<div class="modal fade" id="editRoomModal' . $room['room_id'] . '" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="edit_room_id" value="' . $room['room_id'] . '">
            <div class="modal-header">
                <h5>Edit Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            
            <div class="modal-body">
                <div class="mb-3"><label>Building</label><input type="text" class="form-control" value="' . $rw['name']  . '" required readonly></div>
                <div class="mb-3"><label>Room No.</label><input type="text" name="edit_room_no" class="form-control" value="' . $room['room_number'] . '" required></div>
                <div class="mb-3"><label>Select Floor Number</label>
                    <select name="floor_number" id="floor_number" class="form-select">
                        ' . $options . '
                    </select>
                </div>
                <div class="mb-3"><label>Room Price</label><input type="number" step="0.01" name="edit_room_price" class="form-control" value="' . $room['room_amount'] . '" ' . $disabled_inputs . ' required></div>
                <div class="mb-3"><label>Capacity</label><input type="number" name="edit_capacity" class="form-control" value="' . $room['capacity'] . '" required ' . $disabled_inputs . '></div>
                <div class="mb-3"><label>Status</label>
                    <input type="text" class="form-control" value="' . $room['room_status']  . '" required readonly>
                    <input type="hidden" name="edit_status" id="hiddenStatus' . $room['room_id'] . '" value="' . $room['room_status'] . '">
                </div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-primary">Update</button></div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal' . $room['room_id'] . '" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <input type="hidden" name="delete_room_id" value="' . $room['room_id'] . '">
            <div class="modal-header">
                <h5>Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Are you sure you want to delete Room <strong>' . $room['room_number'] . '</strong>?</div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-danger">Yes, Delete</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </form>
    </div>
</div>';

                            endwhile;
                        else:
                            ?>
                            <tr>
                                <td colspan="5" class="text-center">No results found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>

                </table>
            </div>
            <?php echo $modals; ?>
            <!-- Manage Utility Types Modal -->
            <div class="modal fade" id="manageUtilityTypeModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">

                        <div class="modal-header">
                            <h5 class="modal-title">Manage Utility Types</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <!-- Add New Utility Type -->
                            <form id="addUtilityTypeForm" method="POST" action="utility_processing/process_utility_type.php">
                                <div class="input-group mb-3">
                                    <input type="hidden" name="building_id" value="<?php echo $id; ?>">
                                    <input type="text" name="utility_name" class="form-control" placeholder="Enter new utility type..." required>
                                    <button class="btn btn-primary" type="submit" name="add_utility">Add</button>
                                </div>
                            </form>

                            <!-- Utility Types List -->
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Utility Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $result = mysqli_query($db_connection, "SELECT * FROM utility_type");
                                    while ($row = mysqli_fetch_assoc($result)) {
                                        echo "<tr>
                      <td>{$row['utility_type_id']}</td>
                      <td>{$row['utility_name']}</td>
                      <td>
                        <button class='btn btn-sm btn-warning editBtn' 
                                data-id='{$row['utility_type_id']}' 
                                data-name='{$row['utility_name']}'
                                data-bs-toggle='modal' 
                                data-bs-target='#editUtilityModal'>
                          Edit
                        </button>
                        <a href='utility_processing/process_utility_type.php?delete_id={$row['utility_type_id']}' 
                           class='btn btn-sm btn-danger' 
                           onclick='return confirm(\"Delete this utility?\")'>Delete</a>
                      </td>
                    </tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Single Edit Utility Modal -->
            <div class="modal fade" id="editUtilityModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">

                        <div class="modal-header">
                            <h5 class="modal-title">Edit Utility Type</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <form method="POST" action="utility_processing/process_utility_type.php">
                            <div class="modal-body">
                                <input type="hidden" name="utility_type_id" id="editUtilityId">
                                <input type="text" class="form-control" name="utility_name" id="editUtilityName" required>
                            </div>
                            <div class="modal-footer">
                                <button type="submit" class="btn btn-success" name="edit_utility">Save</button>
                            </div>
                        </form>

                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>

        </div>
    </main>

    <div class="modal fade" id="addRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="room_CRUD/room_add.php" class="modal-content">
                <div class="modal-header">
                    <h5>Add New Room</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label>Building Name </label>
                        <input type="text" class="form-control" value="<?php echo $rw['name'] ?>" required readonly>
                    </div>
                    <div class="mb-3"><label>Room No.</label>
                        <input type="text" name="room_no" class="form-control" required>
                    </div>
                    <div class="mb-3"><label>Room Price</label>
                        <input type="number" name="room_price" step="0.01" class="form-control" required>
                    </div>
                    <div class="mb-3"><label>Select Floor Number</label>
                        <select name="floor_number" id="floor_number" class="form-select">
                            <?php
                            $floor_number = $rw['number_of_floors'];
                            for ($i = $floor_number; $i >= 1; $i--) {
                                echo "<option value='$i'>$i</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3"><label>Capacity</label>
                        <input type="number" name="capacity" class="form-control" required>
                    </div>

                    <!-- status will default to Available in PHP -->
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Room</button>
                </div>
            </form>
        </div>
    </div>

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
                        <div class="step-icon"><i class="fa-solid fa-building"></i></div>
                        <h3>How to Add Room in a Building</h3>
                        <div class="step-layout">
                            <div class="step-content">
                                <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                    Step 1: Click the “Add Building” Button
                                </h4>
                                <img src="../images/arrow_room_help.png" alt="Add Building Screenshot" class="step-image">
                                <p>This section guides you on how to add a new room in a building to the system. Click the <strong>"Add Room"</strong> button to register a new room in the building in your management dashboard.</p>
                            </div>
                        </div>


                        <div class="step-layout">
                            <div class="step-content">
                                <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                    Step 2: Adding Room Modal
                                </h4>
                                <img src="../images/roommodal_help.png" alt="Add Building Screenshot" class="step-image">
                                <p>This section demonstrates how to use the <strong>"Add New Room"</strong> modal. Fill in the room details such as the <strong>Building Name</strong>, <strong>Room Number</strong>, <strong>Room Price</strong>, <strong>Floor Number</strong>, and <strong>Capacity</strong>. Once all information is entered, click <strong>"Save Room"</strong> to add it to the selected building’s room list.</p>




                                <div class="step-highlight">
                                    <i class="fa-solid fa-lightbulb"></i>
                                    <span>You can access this help anytime by clicking the Help button.</span>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Step 2 -->
                    <div id="step2" class="tutorial-step">
                        <div class="step-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                        <h3>How to Add Utility Bills</h3>
                        <div class="step-layout">
                            <div class="step-content">
                                <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                    Step 1: Click the “Manage Utility Types” Button
                                </h4>
                                <img src="../images/arrow_utility_help.png" alt="Add Building Screenshot" class="step-image">
                                <p>This section guides you on how to manage utility types within a building. Click the <strong>"Manage Utility Types"</strong> button to add, edit, or remove utilities such as water, electricity, or internet for your rooms.</p>
                            </div>
                        </div>

                        <div class="step-layout">
                            <div class="step-content">
                                <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                    Step 2: Adding Utility Bills
                                </h4>
                                <img src="../images/utility_modal_help.png" alt="Add Building Screenshot" class="step-image">
                                <p>This window allows you to manage utility types for your building. To add a new utility, type the name of the utility (e.g., Water, Electricity, or Internet) in the input field and click the <strong>"Add"</strong> button. The added utilities will appear in the list below for management.</p>




                                <div class="step-highlight">
                                    <i class="fa-solid fa-lightbulb"></i>
                                    <span>You can access this help anytime by clicking the Help button.</span>
                                </div>
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

    <!-- Add Room Modal -->

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let editButtons = document.querySelectorAll(".editBtn");

            editButtons.forEach(function(btn) {
                btn.addEventListener("click", function() {
                    let id = this.getAttribute("data-id");
                    let name = this.getAttribute("data-name");

                    document.getElementById("editUtilityId").value = id;
                    document.getElementById("editUtilityName").value = name;
                });
            });
        });
    </script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/script.js"></script>
</body>

</html>