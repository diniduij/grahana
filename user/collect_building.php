<?php
session_start();
require "../db.php";

// Check user role
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'field') {
    header("Location: ../dashboard.php");
    exit;
}

// Check if GND is selected
if (!isset($_SESSION['selected_gnd'])) {
    $_SESSION['error'] = "Please select a GND first.";
    header("Location: ../dashboard.php");
    exit;
}

$gnd = $_SESSION['selected_gnd'];

// Fetch GND geometry
$stmt = $pdo->prepare("SELECT ST_AsGeoJSON(geom) as geojson FROM gnd WHERE gid = :gid LIMIT 1");
$stmt->execute([':gid' => $gnd['gid']]);
$gnd_geo = $stmt->fetch(PDO::FETCH_ASSOC);
$geom_json = $gnd_geo['geojson'] ?? null;

if (!$geom_json) die("GND geometry not available.");

// Fetch ENUM values
function fetch_enum_values($pdo, $schema, $type) {
    $stmt = $pdo->prepare("SELECT unnest(enum_range(NULL::${schema}.${type}))::text AS val");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$building_types = fetch_enum_values($pdo, "buildings", "building_type_enum");
$roof_types = fetch_enum_values($pdo, "buildings", "roof_type_enum");
$construction_types = fetch_enum_values($pdo, "buildings", "construction_type_enum");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🏠 Building Collection</title>
<link rel="icon" type="image/x-icon" href="../assets/img/gis_nwp.ico">
<link href="../assets/css/tailwind.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/ol/ol.css">
<style>
    #map { width: 100%; height: 450px; border-radius: 0.5rem; }
    #formContainer { height: 450px; overflow-y: auto; }
</style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col space-y-4">

<header class="p-4 bg-gray-800 text-white flex justify-between items-center">
  <span>Welcome, <?= htmlspecialchars($_SESSION['user']['full_name']); ?> (<?= $_SESSION['user']['role']; ?>)</span>
  <div class="flex gap-2">
    <a href="field_map.php" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition">← Back to Layer Selection</a>
    <a href="../dashboard.php" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition">← Back to Dashboard</a>
    <a href="../logout.php" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition">Logout</a>
    
  </div>
</header>

<h2 class="text-2xl font-bold text-gray-800">🏠 Building Data Collection</h2>

<!-- Selected GND Info -->
<div class="bg-white p-4 rounded-lg shadow flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <!-- Left: GND Info -->
    <div class="flex flex-col">
        <h3 class="font-semibold text-gray-700 mb-1">Selected GND:</h3>
        <p class="text-gray-900"><?= htmlspecialchars($gnd['mc_uc_ps_n'] . " | " . $gnd['gnd_n'] . " | " . $gnd['gnd_c']); ?></p>
        <p class="text-gray-500 text-sm"><?= htmlspecialchars($gnd['province_n'] . ", " . $gnd['district_n'] . ", " . $gnd['dsd_n']); ?></p>
    </div>

    <!-- Right: Buttons & User Info -->
    
</div>


<!-- Map + Form -->
<div class="flex flex-col gap-4">
    <!-- Map -->
    <div class="bg-white rounded-lg shadow p-2">
        <div class="relative">
            <div class="absolute top-4 right-4 z-20 flex flex-col gap-2 items-end">
                <div class="bg-white shadow p-2 rounded flex items-center gap-2">
                    <input type="checkbox" id="toggleWMS" class="form-checkbox h-4 w-4 text-green-600">
                    <span class="text-gray-700">Show Updates (WMS)</span>
                </div>
                <button id="locateBtn" type="button" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 flex items-center">
                    <img src="../assets/img/location.svg" alt="Locate" class="inline w-5 h-5 mr-2" />
                    Locate Me
                </button>
            </div>
            <div id="map" class="w-full h-96"></div>
        </div>
    </div>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow p-4">
        <div id="formContainer" class="w-full max-w-3xl mx-auto flex flex-col gap-4 text-gray-700">
            <span class="text-gray-500 font-semibold">Click a building to load attributes...</span>
        </div>
    </div>
</div>

<!-- OpenLayers -->
<script src="../assets/ol/ol.js"></script>
<script>
const gndGeoJSON = <?= $geom_json ?>;

// --- GND Layer ---
let gndFeature = new ol.format.GeoJSON().readFeature(gndGeoJSON, {
    dataProjection: 'EPSG:3857', featureProjection: 'EPSG:3857'
});
let gndSource = new ol.source.Vector({ features: [gndFeature] });
let gndLayer = new ol.layer.Vector({ source: gndSource });

// --- Buildings Layer ---
let buildingSource = new ol.source.Vector();
let buildingLayer = new ol.layer.Vector({
    source: buildingSource,
    style: new ol.style.Style({
        stroke: new ol.style.Stroke({ color: 'rgba(0,0,200,0.6)', width: 1 }),
        fill: new ol.style.Fill({ color: 'rgba(0,0,200,0.1)' })
    })
});

// --- Map ---

const map = new ol.Map({
    target: 'map',
    layers: [
        new ol.layer.Tile({ 
            source: new ol.source.OSM({
                attributions: '© <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
            })
        }),
        gndLayer,
        buildingLayer
    ],
    view: new ol.View({ center: [0,0], zoom: 13 })
});

// Fit only if GND has extent
let gndExtent = gndSource.getExtent();
if (gndExtent && !ol.extent.isEmpty(gndExtent)) {
    map.getView().fit(gndExtent, { padding: [20,20,20,20] });
}

// --- Geolocation: Zoom to current GPS location ---
const geolocation = new ol.Geolocation({
    tracking: false,
    projection: map.getView().getProjection()
});

// Optional: show a marker at current location
const positionFeature = new ol.Feature();
positionFeature.setStyle(new ol.style.Style({
    image: new ol.style.Circle({
        radius: 7,
        fill: new ol.style.Fill({ color: 'rgba(0, 153, 255, 0.7)' }),
        stroke: new ol.style.Stroke({ color: '#fff', width: 2 })
    })
}));

const geoSource = new ol.source.Vector({ features: [positionFeature] });
const geoLayer = new ol.layer.Vector({ source: geoSource });
map.addLayer(geoLayer);

// Add a button to locate user (add to DOM if not present)
if (!document.getElementById('locateBtn')) {
    const btn = document.createElement('button');
    btn.id = 'locateBtn';
    btn.textContent = '📍 My Location';
    btn.className = 'bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 absolute top-4 left-4 z-10';
    document.body.appendChild(btn);
}

document.getElementById('locateBtn').addEventListener('click', () => {
    geolocation.setTracking(true); // request GPS

    geolocation.once('change:position', () => {
        const coords = geolocation.getPosition();
        if (coords) {
            // Move marker
            positionFeature.setGeometry(new ol.geom.Point(coords));

            // Zoom map
            map.getView().animate({ center: coords, zoom: 18, duration: 1000 });
        }
        geolocation.setTracking(false); // stop tracking after one update
    });

    geolocation.once('error', (err) => {
        alert('Could not get location: ' + err.message);
        geolocation.setTracking(false);
    });
});

// --- Load building features ---
fetch(`load_building_features.php?gnd_id=<?= $gnd['gid'] ?>`)
.then(res => res.json())
.then(data => {
    if (!data.features) return;
    const features = new ol.format.GeoJSON().readFeatures(data, {
        dataProjection: 'EPSG:3857',
        featureProjection: 'EPSG:3857'
    });
    buildingSource.addFeatures(features);
});

// --- Click handler to load building attributes ---
map.on('singleclick', function(evt) {
    let selectedFeature = null;
    map.forEachFeatureAtPixel(evt.pixel, function(feature, layer) {
        if (layer !== buildingLayer) return;
        selectedFeature = feature;
        const buildingId = feature.get('building_id');
        if (!buildingId) return;

        // Highlight selected feature
        buildingLayer.setStyle(function(feat) {
            if (feat === selectedFeature) {
                return new ol.style.Style({
                    stroke: new ol.style.Stroke({ color: 'rgba(255,0,0,0.9)', width: 3 }),
                    fill: new ol.style.Fill({ color: 'rgba(255,200,200,0.4)' })
                });
            }
            return new ol.style.Style({
                stroke: new ol.style.Stroke({ color: 'rgba(0,0,200,0.6)', width: 1 }),
                fill: new ol.style.Fill({ color: 'rgba(0,0,200,0.1)' })
            });
        });

        const formContainer = document.getElementById('formContainer');
        formContainer.innerHTML = '<span class="text-gray-500 font-semibold">Loading...</span>';

        fetch(`api/api_get_building.php?building_id=${buildingId}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            const b = data.data;

            formContainer.innerHTML = `

<div class="flex flex-col gap-3">
    <div class="flex flex-col"><label class="font-semibold">Building Code</label>
        <input type="text" id="building_code" value="${b.building_code}" disabled class="border rounded px-3 py-2 bg-gray-100"></div>
    <div class="flex flex-col"><label class="font-semibold">Building Type</label>
        <select id="building_type" class="border rounded px-3 py-2">
            <option value="">-- Select Type --</option>
            ${<?= json_encode($building_types) ?>.map(v=>`<option value="${v}" ${b.building_type===v?'selected':''}>${v}</option>`).join('')}
        </select>
    </div>
    <div class="flex flex-col"><label class="font-semibold">No of Floors</label>
        <input type="number" id="no_of_floors" value="${b.no_of_floors||''}" class="border rounded px-3 py-2"></div>
    <div class="flex flex-col"><label class="font-semibold">Building Material</label>
        <input type="text" id="building_material" value="${b.building_material||''}" class="border rounded px-3 py-2"></div>
    <div class="flex flex-col"><label class="font-semibold">Roof Type</label>
        <select id="roof_type" class="border rounded px-3 py-2">
            <option value="">-- Select Roof --</option>
            ${<?= json_encode($roof_types) ?>.map(v=>`<option value="${v}" ${b.roof_type===v?'selected':''}>${v}</option>`).join('')}
        </select></div>
    <div class="flex flex-col"><label class="font-semibold">Electricity Sources</label>
        <div id="electricity_sources_group" class="flex flex-wrap gap-2">
            <label><input type="checkbox" value="Grid"> Grid</label>
            <label><input type="checkbox" value="Solar"> Solar</label>
            <label><input type="checkbox" value="Generator"> Generator</label>
            <label><input type="checkbox" value="None"> None</label>
        </div>
    </div>
    <div class="flex flex-col"><label class="font-semibold">Water Supply</label>
        <input type="text" id="water_supply" value="${b.water_supply||''}" class="border rounded px-3 py-2"></div>
    <div class="flex flex-col"><label class="font-semibold">Liquid Waste Disposal</label>
        <input type="text" id="liquidwaste_disposal" value="${b.liquidwaste_disposal||''}" class="border rounded px-3 py-2"></div>
    <div class="flex flex-col"><label class="font-semibold">Solid Waste Disposal</label>
        <input type="text" id="solidwaste_disposal" value="${b.solidwaste_disposal||''}" class="border rounded px-3 py-2"></div>
    <div class="flex flex-col"><label class="font-semibold">Construction Year</label>
        <input type="number" id="construction_year" value="${b.construction_year||''}" class="border rounded px-3 py-2"></div>
    <div class="flex flex-col"><label class="font-semibold">Construction Type</label>
        <select id="construction_type" class="border rounded px-3 py-2">
            <option value="">-- Select --</option>
            ${<?= json_encode($construction_types) ?>.map(v=>`<option value="${v}" ${b.construction_type===v?'selected':''}>${v}</option>`).join('')}
        </select></div>
    <div class="flex items-center gap-2">
        <input type="checkbox" id="is_occupied" ${b.is_occupied?'checked':''}>
        <label class="font-semibold">Occupied</label>
    </div>
    <div class="flex flex-col"><label class="font-semibold">Remarks</label>
        <textarea id="remarks" class="border rounded px-3 py-2">${b.remarks||''}</textarea></div>
    <div class="mt-4">
        <button id="btnNextBuilding" class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700 transition">Next →</button>
    </div>
</div>
            `;

            // Next button logic
            document.getElementById('btnNextBuilding').addEventListener('click', async function(e) {
                e.preventDefault();
                const buildingType = document.getElementById('building_type').value;
                if (!buildingType) {
                    alert('Please select a Building Type.');
                    return;
                }
                // Collect main attributes
                // Collect checked electricity sources
                const checkedSources = Array.from(document.querySelectorAll('#electricity_sources_group input[type="checkbox"]:checked')).map(cb => cb.value);
                const payload = {
                    building_id: b.building_id,
                    building_code: b.building_code,
                    building_type: buildingType,
                    no_of_floors: document.getElementById('no_of_floors').value,
                    building_material: document.getElementById('building_material').value,
                    roof_type: document.getElementById('roof_type').value,
                    electricity_sources: checkedSources.join(','),
                    water_supply: document.getElementById('water_supply').value,
                    liquidwaste_disposal: document.getElementById('liquidwaste_disposal').value,
                    solidwaste_disposal: document.getElementById('solidwaste_disposal').value,
                    construction_year: document.getElementById('construction_year').value,
                    construction_type: document.getElementById('construction_type').value,
                    is_occupied: document.getElementById('is_occupied').checked ? 1 : 0,
                    remarks: document.getElementById('remarks').value
                };
                try {
                    // Save main attributes (replace with your actual API endpoint)
                    const res = await fetch('api/api_save_building_master.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams(payload)
                    });
                    const result = await res.json();
                    if (!result.success) {
                        alert('Error saving: ' + result.message);
                        return;
                    }
                    // Redirect based on building type
                    let formPage = '';
                    if (buildingType === 'Residential') {
                        formPage = 'forms/building_residential.php';
                    } else if (buildingType === 'Commercial') {
                        formPage = 'forms/building_commercial.php';
                    } else if (buildingType === 'Industrial') {
                        formPage = 'forms/building_industrial.php';
                    } else {
                        alert('No additional form for this building type.');
                        return;
                    }
                    window.location.href = formPage
                        + '?building_id=' + encodeURIComponent(b.building_id)
                        + '&building_type=' + encodeURIComponent(buildingType);
                } catch (err) {
                    alert('Request failed: ' + err);
                }
            });
        });
    });
});
</script>
</body>
</html>
