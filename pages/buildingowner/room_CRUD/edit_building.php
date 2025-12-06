<?php
if (file_exists('includes/database.php')) {
    include_once('includes/database.php');
}
if (file_exists('../../includes/database.php')) {
    include_once('../../includes/database.php');
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
session_start();
$id = $_GET['id'] ?? 0;
if ($id) {
    $_SESSION['building_id'] = $id;
}
$building_id = $_SESSION['building_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $number_of_floors = (int)($_POST['number_of_floors'] ?? 1);

    // New location fields
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    $street = trim($_POST['street'] ?? '');
    $barangay = trim($_POST['barangay'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $province = trim($_POST['province'] ?? '');
    $postal_code = trim($_POST['postal_code'] ?? '');
    $country = trim($_POST['country'] ?? '');

    if ($building_id <= 0 || $name === '') {
        $_SESSION['warning'] = "Please fill required fields.";
        header("Location: building_management.php");
        exit();
    }

    $stmt = $conn->prepare("
        UPDATE building SET 
          name = ?, 
          number_of_floors = ?, 
          latitude = ?, 
          longitude = ?, 
          street = ?, 
          barangay = ?, 
          city = ?, 
          province = ?, 
          postal_code = ?, 
          country = ?
        WHERE building_id = ?
    ");
    $stmt->bind_param(
        "siddssssssi",
        $name,
        $number_of_floors,
        $latitude,
        $longitude,
        $street,
        $barangay,
        $city,
        $province,
        $postal_code,
        $country,
        $building_id
    );

    if ($stmt->execute()) {
        header("Location: ../building_management.php?updated=1");
        exit();
    } else {
        $_SESSION['warning'] = "Update failed: " . $stmt->error;
        header("Location: ../building_management.php");
        exit();
    }
}

$stmt = $pdo->prepare("SELECT * FROM building WHERE building_id = ?");
$stmt->execute([$building_id]);
$building = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../../css/addcontent.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        #map {
            height: 400px;
            width: 100%;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <main class="main-content">
        <div class="form-section">
            <div class="form-header">
                <i class="fas fa-building fa-2x"></i>
                <h1>Edit Building Details</h1>
            </div>
            <form class="rule-form" method="post">
                <label for="title">Building Name</label>
                <input type="text" name="name" id="title" placeholder=" Add New Building Name" value="<?= htmlspecialchars($building['name']) ?>" required>

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
                    <input type="number" name="number_of_floors" class="form-control" value="<?= htmlspecialchars($building['number_of_floors']) ?>" required>
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


                <button type="submit" class="add-btn">Edit</button>
                <a href="../building_management.php" class="back-btn">Back</a>
            </form>
    </main>
    <script src="../../js/script.js"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const map = L.map('map').setView([
                <?= $building['latitude'] ?: '14.5995' ?>,
                <?= $building['longitude'] ?: '120.9842' ?>
            ], 13);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            let marker;
            const latInput = document.getElementById('latitude');
            const lngInput = document.getElementById('longitude');
            const coordsEl = document.getElementById('coords');

            // Add marker if building already has coordinates
            if (latInput.value && lngInput.value) {
                marker = L.marker([latInput.value, lngInput.value]).addTo(map);
                map.setView([latInput.value, lngInput.value], 15);
            }

            // When map clicked
            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;

                if (marker) map.removeLayer(marker);
                marker = L.marker([lat, lng]).addTo(map);

                latInput.value = lat;
                lngInput.value = lng;
                coordsEl.innerText = `Latitude: ${lat.toFixed(6)} | Longitude: ${lng.toFixed(6)}`;
                fetchAddress(lat, lng);
            });

            // Use my location
            document.getElementById('locateBtn').addEventListener('click', function() {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function(pos) {
                        const lat = pos.coords.latitude;
                        const lng = pos.coords.longitude;

                        if (marker) map.removeLayer(marker);
                        marker = L.marker([lat, lng]).addTo(map);
                        map.setView([lat, lng], 16);

                        latInput.value = lat;
                        lngInput.value = lng;
                        coordsEl.innerText = `Latitude: ${lat.toFixed(6)} | Longitude: ${lng.toFixed(6)}`;
                        fetchAddress(lat, lng);
                    });
                }
            });

            // Reverse geocoding
            function fetchAddress(lat, lng) {
                fetch(`reverse_proxy.php?lat=${lat}&lon=${lng}`)
                    .then(res => res.json())
                    .then(data => {
                        if (!data || !data.address) return;
                        const addr = data.address;
                        document.getElementById('street').value = addr.road || '';
                        document.getElementById('barangay').value = addr.suburb || addr.village || '';
                        document.getElementById('city').value = addr.city || addr.town || addr.municipality || '';
                        document.getElementById('province').value = addr.state || '';
                        document.getElementById('postal_code').value = addr.postcode || '';
                        document.getElementById('country').value = addr.country || '';
                    })
                    .catch(err => console.error("Geocoding error:", err));
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.querySelector(".rule-form");
            const latInput = document.getElementById("latitude");
            const lngInput = document.getElementById("longitude");

            form.addEventListener("submit", function(e) {
                if (!latInput.value || !lngInput.value) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Location Required',
                        text: 'Please set the building location on the map before updating.',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#3085d6'
                    });
                }
            });
        });
    </script>

</body>

</html>