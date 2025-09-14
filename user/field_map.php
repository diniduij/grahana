<?php
session_start();
require "../db.php";

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'field') {
    header("Location: ../dashboard.php");
    exit;
}

if (!isset($_SESSION['selected_gnd'])) {
    $_SESSION['error'] = "Please select a GND first.";
    header("Location: ../dashboard.php");
    exit;
}
$user = $_SESSION['user'];
$gnd = $_SESSION['selected_gnd'];

// Fetch GND geometry as GeoJSON
$stmt = $pdo->prepare("SELECT ST_AsGeoJSON(geom) as geojson FROM gnd WHERE gid = :gid LIMIT 1");
$stmt->execute([':gid' => $gnd['gid']]);
$gnd_geo = $stmt->fetch(PDO::FETCH_ASSOC);
$geom_json = $gnd_geo['geojson'] ?? null;

if (!$geom_json) die("GND geometry not available.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="icon" type="image/x-icon" href="../assets/img/gis_nwp.ico">
<title>ග්‍රහණ - Layer Selection</title>
<link href="../assets/css/tailwind.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/ol/ol.css">
<style>
  #map { width: 100%; height: 400px; border: 1px solid #ccc; }
  .tool-section { display: none; }
  .tool-section.active { display: block; }
  .tool-btn.active { background-color: #16a34a !important; } /* green for active */
</style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col space-y-4">



<header class="p-4 bg-gray-800 text-white flex justify-between items-center">
    <span>Welcome, <?= htmlspecialchars($user['full_name']); ?> (<?= $user['role']; ?>)</span>
    <div class="flex gap-2">
        <a href="../dashboard.php" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition">← Back to Dashboard</a>
        <a href="../logout.php" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition">Logout</a>
    </div>
</header>

<h2 class="text-2xl font-bold mb-4">🗺️ Field Data Collection - Main</h2>

<!-- Selected GND Info & Back Button -->
<div class="flex justify-between items-center mb-4 bg-gray-200 p-2 rounded">
     <div class="bg-white p-4 rounded-lg shadow mb-4">
      <h3 class="font-semibold mb-2">Selected GND:</h3>
      <p><?= htmlspecialchars($gnd['mc_uc_ps_n'] . " | " . $gnd['gnd_n'] . " | " . $gnd['gnd_c']); ?></p>
      <p class="text-gray-600 text-sm"><?= htmlspecialchars($gnd['province_n'] . ", " . $gnd['district_n'] . ", " . $gnd['dsd_n']); ?></p>
      <a href="my_gnd.php" class="text-blue-600 hover:underline mt-2 inline-block">Select a different GND</a>
  </div>

</div>

<div class="flex flex-col md:flex-row gap-4">

    <!-- Map -->
    <div class="flex-1 bg-white rounded-lg shadow p-2">
        <div id="map"></div>
    </div>

    <!-- Tool Panel -->
    <div id="tool-panel" class="bg-white rounded-lg shadow p-4 space-y-4">
        <h3 class="font-semibold mb-2">Tools</h3>

        <!-- Navigation Buttons -->
        <div class="flex gap-2 mb-2">
            <button class="tool-btn bg-blue-600 text-white px-3 py-1 rounded active" data-target="layerSelect">Layer Selection</button>
            <button class="tool-btn bg-blue-600 text-white px-3 py-1 rounded" data-target="geoCapture">Capture Location</button>
            <button class="tool-btn bg-blue-600 text-white px-3 py-1 rounded" data-target="ownerTool">Add Owner</button>
        </div>

        <!-- Layer Selection Section -->
        <div id="layerSelect" class="tool-section active">
            <p class="font-semibold mb-2">Select a layer to collect data:</p>
            <select id="layerChoice" class="border p-2 rounded w-full mb-2">
                <option value="landuse">Landuse</option>
                <option value="building">Building</option>
            </select>
            <button id="loadLayerBtn" class="bg-green-600 text-white px-3 py-1 rounded w-full">Load Features in Selected GND</button>
            <p class="text-gray-500 text-sm mt-2">Feature loading happens on the next page.</p>
        </div>

        <!-- Capture Location Section -->
        <div id="geoCapture" class="tool-section">
            <p class="font-semibold mb-1">Capture your current location:</p>
            <textarea id="locationNote" class="border p-2 rounded w-full h-64 mb-2" placeholder="Enter notes..."></textarea>
            <button id="captureBtn" class="bg-green-600 text-white px-3 py-1 rounded w-full">Capture & Save</button>
            <p id="locationStatus" class="text-gray-500 text-sm mt-1"></p>
        </div>

        <!-- Add Owner Section -->
        <div id="ownerTool" class="tool-section">
            <p class="font-semibold mb-2">Manage Owners</p>
            <form id="ownerForm" class="space-y-2">
                <input type="hidden" name="owner_id" id="owner_id">
                <input type="text" name="owner_code" id="owner_code" class="border p-2 rounded w-full" placeholder="Owner Code" required>
                <input type="text" name="owner_type" id="owner_type" class="border p-2 rounded w-full" placeholder="Owner Type" required>
                <input type="text" name="owner_name" id="owner_name" class="border p-2 rounded w-full" placeholder="Owner Name" required>
                <input type="text" name="reference" id="reference" class="border p-2 rounded w-full" placeholder="Reference">
                <textarea name="remarks" id="remarks" class="border p-2 rounded w-full" placeholder="Remarks"></textarea>

                <div class="flex gap-2">
                    <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded">Save</button>
                    <button type="button" id="deleteOwnerBtn" class="bg-red-600 text-white px-3 py-1 rounded">Delete</button>
                    <button type="reset" class="bg-gray-500 text-white px-3 py-1 rounded">Clear</button>
                </div>
            </form>

            <h4 class="font-semibold mt-4">Existing Owners</h4>
            <ul id="ownerList" class="border rounded max-h-40 overflow-y-auto p-2 text-sm"></ul>
        </div>

    </div>
</div>

<script src="../assets/ol/ol.js"></script>
<script>
const gndGeoJSON = <?= $geom_json ?>;

// GND Feature
const gndFeature = new ol.format.GeoJSON().readFeature(gndGeoJSON, {
    dataProjection: 'EPSG:3857',
    featureProjection: 'EPSG:3857'
});

const gndSource = new ol.source.Vector({ features: [gndFeature] });
const gndLayer = new ol.layer.Vector({
    source: gndSource,
    style: new ol.style.Style({
        stroke: new ol.style.Stroke({ color: 'rgba(255, 0, 0, 0.7)', width: 2 }),
        fill: new ol.style.Fill({ color: 'rgba(255, 0, 0, 0.1)' })
    })
});

const pointSource = new ol.source.Vector();
const pointLayer = new ol.layer.Vector({ source: pointSource });
pointLayer.setStyle(new ol.style.Style({
    image: new ol.style.Circle({
        radius: 6,
        fill: new ol.style.Fill({ color: 'green' }),
        stroke: new ol.style.Stroke({ color: 'white', width: 2 })
    })
}));

const map = new ol.Map({
    target: 'map',
    layers: [
        new ol.layer.Tile({ 
            source: new ol.source.OSM({
                attributions: '© <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
            })
        }),
        gndLayer,
        pointLayer
    ],
    view: new ol.View({ center: ol.extent.getCenter(gndSource.getExtent()), zoom: 12 })
});

map.getView().fit(gndSource.getExtent(), { padding: [20,20,20,20] });

// Tool navigation and active highlighting
const buttons = document.querySelectorAll('.tool-btn');
const sections = document.querySelectorAll('.tool-section');
buttons.forEach(btn => {
    btn.addEventListener('click', () => {
        sections.forEach(sec => sec.classList.remove('active'));
        buttons.forEach(b => b.classList.remove('active'));
        document.getElementById(btn.dataset.target).classList.add('active');
        btn.classList.add('active');
    });
});

// Load Layer Button placeholder
// Load Layer Button: redirect based on selected layer
document.getElementById('loadLayerBtn').addEventListener('click', () => {
    const layer = document.getElementById('layerChoice').value;
    const gnd_id = <?= $gnd['gid'] ?>;

    if (!gnd_id) {
        alert("No GND selected!");
        return;
    }

    // Set the selected layer in session via GET or POST (optional)
    // For simplicity, pass as URL parameter
    let targetPage = '';
    if(layer === 'landuse') targetPage = 'collect_landuse.php';
    else if(layer === 'building') targetPage = 'collect_building.php';
    else targetPage = 'collect_generic.php'; // fallback

    // Redirect to the appropriate page
    window.location.href = `${targetPage}?gnd_id=${gnd_id}`;
});


// Capture current location
document.getElementById('captureBtn').addEventListener('click', () => {
    const note = document.getElementById('locationNote').value.trim();

    if (!navigator.geolocation) {
        alert("Geolocation is not supported by your browser.");
        return;
    }

    const statusEl = document.getElementById('locationStatus');
    statusEl.textContent = "Getting current location...";

    navigator.geolocation.getCurrentPosition(
        (pos) => {
            const lon = pos.coords.longitude;
            const lat = pos.coords.latitude;
            const coords = ol.proj.fromLonLat([lon, lat], 'EPSG:3857');

            // Create feature with emoji style
            const ptFeature = new ol.Feature(new ol.geom.Point(coords));
            ptFeature.set('note', note);

            ptFeature.setStyle(new ol.style.Style({
                text: new ol.style.Text({
                    text: '📍',        // Your emoji here
                    font: '28px sans-serif',
                    offsetY: -15       // Moves emoji slightly above point
                })
            }));

            // Add to map
            pointSource.addFeature(ptFeature);

            // Center and zoom map
            map.getView().animate({ center: coords, zoom: 16, duration: 800 });

            // Send to backend
            fetch('capture_location.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ x: lon, y: lat, note: note })
            })
            .then(res => res.text())
            .then(res => {
                statusEl.textContent = "Location captured successfully!";
                document.getElementById('locationNote').value = '';
            })
            .catch(err => {
                statusEl.textContent = "Error saving location.";
                console.error(err);
            });
        },
        (err) => {
            statusEl.textContent = "Unable to get location: " + err.message;
        },
        { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
    );
});


// ----------------- OWNER TOOL -----------------
const ownerForm = document.getElementById("ownerForm");
const ownerList = document.getElementById("ownerList");
const deleteBtn = document.getElementById("deleteOwnerBtn");

async function loadOwners() {
    const res = await fetch("api/api_ownership.php");
    const owners = await res.json();
    ownerList.innerHTML = "";
    owners.forEach(o => {
        const li = document.createElement("li");
        li.textContent = `${o.owner_name} (${o.owner_code})`;
        li.className = "cursor-pointer hover:bg-blue-100 px-2 py-1 rounded";
        li.onclick = () => {
            document.getElementById("owner_id").value = o.owner_id;
            document.getElementById("owner_code").value = o.owner_code;
            document.getElementById("owner_type").value = o.owner_type;
            document.getElementById("owner_name").value = o.owner_name;
            document.getElementById("reference").value = o.reference || "";
            document.getElementById("remarks").value = o.remarks || "";
        };
        ownerList.appendChild(li);
    });
}

ownerForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const data = Object.fromEntries(new FormData(ownerForm).entries());
    await fetch("api/api_ownership.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(data)
    });
    ownerForm.reset();
    loadOwners();
});

deleteBtn.addEventListener("click", async () => {
    const id = document.getElementById("owner_id").value;
    if (!id) return alert("Select an owner to delete");
    await fetch("api/api_ownership.php", {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ owner_id: id })
    });
    ownerForm.reset();
    loadOwners();
});

// Initial load
loadOwners();


</script>

</body>
</html>
