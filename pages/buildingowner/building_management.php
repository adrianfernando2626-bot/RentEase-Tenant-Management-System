<?php
session_start();

// Include database connection
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../includes/database.php')) {
    include_once('../includes/database.php');
}
if (isset($_GET['message']) && $_GET['message'] === 'room_inserted') {
    $warning = 'Room Added';
}
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

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
if (isset($_POST['delete_building_id'])) {
    $delete_building_id = $_POST['delete_building_id'];
    $conn->query("DELETE FROM building WHERE building_id = $delete_building_id");
    header("Location: building_management.php?deleted=1");
    exit();
}

// Handle update
// Handle update (improved)

// Handle search and pagination
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$searchQuery = '';
if (!empty($search)) {
    $escaped = $conn->real_escape_string($search);
    $searchQuery = "WHERE 
            building_id LIKE '%$escaped%' OR 
            name LIKE '%$escaped%' OR 
            street LIKE '%$escaped%' OR 
            barangay LIKE '%$escaped%' OR 
            city LIKE '%$escaped%' OR 
            province LIKE '%$escaped%' OR 
            postal_code LIKE '%$escaped%' OR 
            country LIKE '%$escaped%' OR 
            building_is_active LIKE '%$escaped%' OR 
            number_of_floors LIKE '%$escaped%'";
}
$totalRooms = $conn->query("SELECT COUNT(*) AS total FROM building $searchQuery")->fetch_assoc()['total'];
$totalPages = ceil($totalRooms / $limit);

$rooms = $conn->query("SELECT * FROM building $searchQuery LIMIT $limit OFFSET $offset");
?>

<!-- HTML starts below (unchanged structure except additions in container) -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Building Management</title>
    <link rel="stylesheet" href="../css/styledashowner4.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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

    <style>
        #map,
        [id^="map"] {
            height: 300px;
            width: 100%;
            border-radius: 8px;
        }
    </style>


</head>

<body>
    <div class="side-bar"> <a href="ownerdashboard.php" class="logo">
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
            <button class="burger-btn" aria-label="Toggle sidebar" onclick="document.body.classList.toggle('sidebar-open');document.querySelector('.side-bar').classList.toggle('open');">
                <i class="fas fa-bars"></i>
            </button>
            <div>
                <h2>Building Management</h2>
                <p><?php echo date('D, d M Y'); ?></p>
            </div>
            <button class="help-btn" id="helpBtn" title="Help Center" onclick="openHelpModal();">
                <i class="fas fa-question-circle"></i>
                <span>Help</span>
            </button>
        </div>

        <div class="container mt-4">
            <?php if (isset($_GET['deleted'])): ?><div class="alert alert-success">Building successfully deleted.</div><?php endif; ?>
            <?php if (isset($_GET['updated'])): ?><div class="alert alert-info">Building successfully updated.</div><?php endif; ?>
            <?php if (isset($_GET['message'])): ?><div class="alert alert-warning">Building successfully Inserted.</div><?php endif; ?>
            <?php if (isset($_GET['building_message']) && $_GET['building_message'] === "name_taken"): ?><div class="alert alert-warning">Building name is already taken.</div><?php endif; ?>

            <div class="filter-section mb-4">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-6">
                        <form class="d-flex" method="GET" action="building_management.php" role="search">
                            <input type="text" name="search" class="form-control me-2" placeholder="Search buildings..." value="<?php echo htmlspecialchars($search); ?>">
                            <button class="btn btn-secondary" type="submit"><i class="fas fa-search me-1"></i> Search</button>
                        </form>
                    </div>
                    <div class="col-12 col-md-6 d-flex justify-content-md-end">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                            <i class="fas fa-plus me-1"></i> Add Building
                        </button>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h2 class="section-title m-0">Buildings</h2>
                    <span class="text-muted small">Total: <?php echo (int)$totalRooms; ?></span>
                </div>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3 g-md-4">
                    <?php
                    $modals = '';
                    if ($rooms->num_rows > 0):
                        while ($room = $rooms->fetch_assoc()):
                    ?>
                            <div class="col">
                                <?php
                                $disabled_button = "";
                                $building_id = $room['building_id'];
                                $stmt = $conn->prepare("SELECT us.account_status from userall us
                                        JOIN room r ON r.room_id = us.room_id
                                        JOIN building b ON b.building_id = r.building_id
                                        WHERE b.building_id = ? AND us.account_status = 'Active'");
                                $stmt->bind_param("i", $building_id);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                if ($result->num_rows > 0) {
                                    $disabled_button = "disabled";
                                }
                                ?>

                                <div class="card shadow-sm hover-card h-100">
                                    <?php
                                    $stmtcount = $conn->prepare("SELECT COUNT(*) AS total_contract from contract c
                                        JOIN userall us ON us.user_id = c.user_id
                                        JOIN room r ON r.room_id = us.room_id
                                        JOIN building b ON b.building_id = r.building_id
                                        WHERE b.building_id = ? AND c.contract_status = 'Active'");
                                    $stmtcount->bind_param("i", $building_id);
                                    $stmtcount->execute();
                                    $result_get = $stmtcount->get_result();
                                    $result = $result_get->fetch_assoc();
                                    ?>
                                    <div class="card-body">
                                        <h5 class="card-title mb-2"><?= htmlspecialchars($room['name']) ?></h5>
                                        <p class="card-text text-muted mb-0"><strong>Number of Active Contracts:</strong> (<?= htmlspecialchars($result['total_contract']) ?>)</p>
                                        <p class="card-text text-muted mb-0"><strong>Street:</strong> <?= htmlspecialchars($room['street']) ?></p>
                                        <p class="card-text text-muted mb-0"><strong>Barangay:</strong> <?= htmlspecialchars($room['barangay']) ?></p>
                                        <p class="card-text text-muted mb-0"><strong>City:</strong> <?= htmlspecialchars($room['city']) ?></p>
                                        <p class="card-text text-muted mb-0"><strong>Province:</strong> <?= htmlspecialchars($room['province']) ?></p>
                                        <p class="card-text text-muted mb-0"><strong>Postal Code:</strong> <?= htmlspecialchars($room['postal_code']) ?></p>
                                        <p class="card-text text-muted mb-0"><strong>Country:</strong> <?= htmlspecialchars($room['country']) ?></p>
                                        <p class="card-text text-muted"><strong>Number of Floors:</strong> <?= htmlspecialchars($room['number_of_floors']) ?></p>
                                    </div>
                                    <div class="card-footer d-flex gap-2">
                                        <a href="room_management.php?id=<?= $room['building_id'] ?>" class="btn-action btn-primary-color">
                                            <i class="fas fa-door-open"></i> Show Rooms
                                        </a>
                                        <button class="btn-action btn-edit-color" onclick="window.location.href='room_CRUD/edit_building.php?id=<?= $room['building_id'] ?>'">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>

                                        <button class="btn-action btn-danger-color" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $room['building_id'] ?>" <?= $disabled_button ?>>
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>

                        <?php
                            $modals .= '

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal' . $room['building_id'] . '" tabindex="-1">
  <div class="modal-dialog">
    <form method="POST" class="modal-content">
      <input type="hidden" name="delete_building_id" value="' . $room['building_id'] . '">
      <div class="modal-header">
        <h5>Confirm Deletion</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete Building <strong>' . htmlspecialchars($room['name']) . '</strong>?
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-danger">Yes, Delete</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
      </div>
    </form>
  </div>
</div>
';

                        endwhile;
                    else: ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm text-center">
                                <div class="card-body d-flex flex-column justify-content-center">
                                    <h5 class="card-title mb-1">No results found</h5>
                                    <p class="text-muted mb-3">Try adjusting your search.</p>
                                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addRoomModal"><i class="fas fa-plus me-1"></i> Add Building</button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php echo $modals; ?>
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
                        <h3>How to Add Building</h3>
                        <div class="step-layout">
                            <div class="step-content">
                                <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                    Step 1: Click the “Add Building” Button
                                </h4>
                                <img src="../images/arrow_add_building.png" alt="Add Building Screenshot" class="step-image">
                                <p>This section guides you on how to add a new building to the system. Click the <strong>"Add Building"</strong> button to register a new apartment or property in your management dashboard.</p>
                            </div>
                        </div>

                        <div class="step-layout">
                            <div class="step-content">
                                <h4 style="margin-bottom: 16px; color: var(--font-color); font-size: 18px; font-weight: 600;">
                                    Step 2: Adding Building Modal
                                </h4>
                                <img src="../images/add_building_loc_help.jfif" alt="Add Building Screenshot" class="step-image">
                                <p>Use the “Add New Building” modal to enter the building’s Name, set its location using the map or the “Use My Location” button, and provide the Number of Floors. Click “Save Building” to add it to your Building Management list.</p>

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
            const totalSteps = 1;

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
    <!-- Add Room Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" action="room_CRUD/building_add.php" class="modal-content">
                <div class="modal-header">
                    <h5>Add New Building</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label>Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <h4 class="mb-3 text-center text-primary">
                        <i class="bi bi-building"></i> Set Apartment Building Location
                    </h4>
                    <p class="text-muted text-center mb-3">
                        Click on the map or use the “Use My Location” button below.
                    </p>

                    <div id="map"></div>

                    <div class="text-center mt-3">
                        <button type="button" id="locateBtn" class="btn btn-primary">
                            <i class="bi bi-geo"></i> Use My Location
                        </button>
                    </div>

                    <div class="mt-3">
                        <h6 class="fw-bold">Selected Coordinates:</h6>
                        <p id="coords">Latitude: — | Longitude: —</p>
                    </div>

                    <div class="mb-3"><label>Number of Floors</label>
                        <input type="number" name="number_of_floors" class="form-control" required>
                    </div>
                    <!-- Hidden inputs for DB -->
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">
                    <input type="hidden" name="street" id="street">
                    <input type="hidden" name="barangay" id="barangay">
                    <input type="hidden" name="city" id="city">
                    <input type="hidden" name="province" id="province">
                    <input type="hidden" name="postal_code" id="postal_code">
                    <input type="hidden" name="country" id="country">
                    <!-- status will default to Available in PHP -->
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Save Building</button>
                </div>
            </form>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="../js/script.js"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            // 🧠 Function to get address using local proxy
            function fetchAddress(lat, lng, prefix = '') {
                fetch(`room_CRUD/reverse_proxy.php?lat=${lat}&lon=${lng}`)
                    .then(res => res.json())
                    .then(data => {
                        if (!data || !data.address) return;
                        const addr = data.address;

                        const setVal = (id, val) => {
                            const el = document.getElementById(prefix + id);
                            if (el) el.value = val || '';
                        };

                        setVal('street', addr.road || '');
                        setVal('barangay', addr.suburb || addr.village || '');
                        setVal('city', addr.city || addr.town || addr.municipality || '');
                        setVal('province', addr.state || '');
                        setVal('postal_code', addr.postcode || '');
                        setVal('country', addr.country || '');
                    })
                    .catch(err => console.error("Geocoding error:", err));
            }

            // 🏗️ ADD BUILDING MAP
            const addMapEl = document.getElementById("map");
            const addModal = document.getElementById('addRoomModal');
            let addMap, addMarker;

            if (addModal) {
                addModal.addEventListener('shown.bs.modal', function() {
                    if (!addMap) {
                        addMap = L.map(addMapEl).setView([14.5995, 120.9842], 13);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors'
                        }).addTo(addMap);
                    }
                    setTimeout(() => addMap.invalidateSize(), 200);

                    addMap.on('click', function(e) {
                        const lat = e.latlng.lat;
                        const lng = e.latlng.lng;

                        if (addMarker) addMap.removeLayer(addMarker);
                        addMarker = L.marker([lat, lng]).addTo(addMap);

                        document.getElementById('latitude').value = lat;
                        document.getElementById('longitude').value = lng;
                        document.getElementById('coords').innerText =
                            `Latitude: ${lat.toFixed(6)} | Longitude: ${lng.toFixed(6)}`;

                        fetchAddress(lat, lng);
                    });
                });

                const locateBtn = document.getElementById('locateBtn');
                if (locateBtn) {
                    locateBtn.addEventListener('click', function() {
                        if (navigator.geolocation) {
                            navigator.geolocation.getCurrentPosition(function(pos) {
                                const lat = pos.coords.latitude;
                                const lng = pos.coords.longitude;

                                if (addMarker) addMap.removeLayer(addMarker);
                                addMarker = L.marker([lat, lng]).addTo(addMap);
                                addMap.setView([lat, lng], 16);

                                document.getElementById('latitude').value = lat;
                                document.getElementById('longitude').value = lng;
                                document.getElementById('coords').innerText =
                                    `Latitude: ${lat.toFixed(6)} | Longitude: ${lng.toFixed(6)}`;

                                fetchAddress(lat, lng);
                            });
                        }
                    });
                }
            }

            // 🧩 EDIT MODALS
            document.querySelectorAll('[id^="editRoomModal"]').forEach(modal => {
                modal.addEventListener('shown.bs.modal', function() {
                    const id = modal.id.replace('editRoomModal', '');
                    const mapEl = document.getElementById('map' + id);
                    const latInput = document.getElementById('latitude' + id);
                    const lngInput = document.getElementById('longitude' + id);
                    const coordsEl = document.getElementById('coords' + id);
                    const locateBtn = document.getElementById('locateBtn' + id);

                    if (!mapEl || mapEl.dataset.initialized) return;
                    mapEl.dataset.initialized = true; // Prevent multiple maps

                    const editMap = L.map(mapEl).setView([14.5995, 120.9842], 13);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(editMap);

                    let marker;
                    setTimeout(() => editMap.invalidateSize(), 200);

                    editMap.on('click', function(e) {
                        const lat = e.latlng.lat;
                        const lng = e.latlng.lng;

                        if (marker) editMap.removeLayer(marker);
                        marker = L.marker([lat, lng]).addTo(editMap);

                        latInput.value = lat;
                        lngInput.value = lng;
                        coordsEl.innerText =
                            `Latitude: ${lat.toFixed(6)} | Longitude: ${lng.toFixed(6)}`;

                        fetchAddress(lat, lng, id);
                    });

                    if (locateBtn) {
                        locateBtn.addEventListener('click', function() {
                            if (navigator.geolocation) {
                                navigator.geolocation.getCurrentPosition(function(pos) {
                                    const lat = pos.coords.latitude;
                                    const lng = pos.coords.longitude;

                                    if (marker) editMap.removeLayer(marker);
                                    marker = L.marker([lat, lng]).addTo(editMap);
                                    editMap.setView([lat, lng], 16);

                                    latInput.value = lat;
                                    lngInput.value = lng;
                                    coordsEl.innerText =
                                        `Latitude: ${lat.toFixed(6)} | Longitude: ${lng.toFixed(6)}`;

                                    fetchAddress(lat, lng, id);
                                });
                            }
                        });
                    }
                });
            });
        });
    </script>





</body>

</html>