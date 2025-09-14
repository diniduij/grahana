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
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🌱 Landuse Collection</title>
<link rel="icon" type="image/x-icon" href="../assets/img/gis_nwp.ico">
<link href="../assets/css/tailwind.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/ol/ol.css">
<style>
    #map { width: 100%; height: 450px; border-radius: 0.5rem; }
    #formContainer { height: 450px; overflow-y: auto; }
</style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col p-6 space-y-4">

<h2 class="text-2xl font-bold text-gray-800">🌱 Landuse Data Collection</h2>

<!-- Selected GND Info -->
<div class="bg-white p-4 rounded-lg shadow flex flex-col md:flex-row md:items-center md:justify-between gap-2">
    <div>
        <h3 class="font-semibold text-gray-700 mb-1">Selected GND:</h3>
        <p class="text-gray-900"><?= htmlspecialchars($gnd['mc_uc_ps_n'] . " | " . $gnd['gnd_n'] . " | " . $gnd['gnd_c']); ?></p>
        <p class="text-gray-500 text-sm"><?= htmlspecialchars($gnd['province_n'] . ", " . $gnd['district_n'] . ", " . $gnd['dsd_n']); ?></p>
    </div>
    <div class="mt-2 md:mt-0">
        <a href="field_map.php" 
           class="inline-block bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition">
           ← Back to Layer Selection
        </a>
    </div>
</div>

<!-- Map + Form -->
<div class="flex flex-col md:flex-row gap-4">
    <!-- Map -->
    <div class="flex-1 bg-white rounded-lg shadow p-2">
        <div id="map"></div>
    </div>

    <!-- Form / Info -->
    <div class="w-full md:w-1/3 bg-white rounded-lg shadow p-4">
        <div id="formContainer" class="flex flex-col items-start justify-start p-2 text-gray-700">
            <span id="selectedLanduse" class="text-gray-600 font-semibold">Select a feature on the map</span>
        </div>
    </div>
</div>

<!-- Dexie.js -->
<script src="../assets/js/dexie.min.js"></script>
<script>
  const db = new Dexie("LanduseDB");
  db.version(1).stores({
    landuse_types: "++id,[main_type+sub_type+type],main_type,sub_type,type,infor_main_type,infor_sub_type,infor_type"
  });

  async function cacheLanduseTypes() {
    const count = await db.landuse_types.count();
    if (count > 0) return;

    const res = await fetch('forms/api_landuse_types.php');
    if (!res.ok) return;

    const types = await res.json();
    await db.landuse_types.bulkAdd(types);
    console.log("Landuse types cached in Dexie:", types.length);
  }
  cacheLanduseTypes();
</script>

<!-- OpenLayers -->
<script src="../assets/ol/ol.js"></script>
<script>
const gndGeoJSON = <?= $geom_json ?>;

// --- GND Layer ---
const gndFeature = new ol.format.GeoJSON().readFeature(gndGeoJSON, {
  dataProjection: 'EPSG:3857', featureProjection: 'EPSG:3857'
});
const gndSource = new ol.source.Vector({ features: [gndFeature] });
const gndLayer = new ol.layer.Vector({
    source: gndSource,
    style: new ol.style.Style({
        stroke: new ol.style.Stroke({ color: 'rgba(243,0,0,0.5)', width: 3 }),
        fill: new ol.style.Fill({ color: 'rgba(243,0,0,0.1)' })
    })
});

// --- Landuse Layer ---
const landuseSource = new ol.source.Vector();
const landuseLayer = new ol.layer.Vector({ source: landuseSource });
landuseLayer.setStyle(new ol.style.Style({
    stroke: new ol.style.Stroke({ color: 'rgba(15,92,0,0.6)', width: 1 }),
    fill: new ol.style.Fill({ color: 'rgba(8,107,38,0.1)' })
}));

// --- Map ---
const map = new ol.Map({
    target: 'map',
    layers: [ new ol.layer.Tile({ source: new ol.source.OSM() }), gndLayer, landuseLayer ],
    view: new ol.View({ center: ol.extent.getCenter(gndSource.getExtent()), zoom: 13 })
});
map.getView().fit(gndSource.getExtent(), { padding: [20,20,20,20] });

// Load landuse features
fetch(`load_landuse_features.php?layer=landuse&gnd_id=<?= $gnd['gid'] ?>`)
    .then(res => res.json())
    .then(data => {
        if (!data.features) return;
        const features = new ol.format.GeoJSON().readFeatures(data, {
            dataProjection: 'EPSG:3857', featureProjection: 'EPSG:3857'
        });
        landuseSource.addFeatures(features);
    });

// --- Populate Dropdowns ---
async function populateDropdowns(landuseId) {
    const selMain = document.getElementById('selMain');
    const selSub = document.getElementById('selSub');
    const selType = document.getElementById('selType');

    const types = await db.landuse_types.toArray();

    // Build maps
    const mainMap = {};
    const subMap = {};
    const typeMap = {};

    types.forEach(t => {
        mainMap[t.main_type] = t.infor_main_type || t.main_type;

        if (!subMap[t.main_type]) subMap[t.main_type] = {};
        subMap[t.main_type][t.sub_type] = t.infor_sub_type || t.sub_type;

        if (!typeMap[t.main_type]) typeMap[t.main_type] = {};
        if (!typeMap[t.main_type][t.sub_type]) typeMap[t.main_type][t.sub_type] = {};
        typeMap[t.main_type][t.sub_type][t.type] = t.infor_type || t.type;
    });

    selMain.innerHTML = `<option value="">-- Select Main Type --</option>` +
        Object.entries(mainMap).map(([val,label]) => `<option value="${val}">${label}</option>`).join('');

    // Fetch clicked record
    const res = await fetch(`forms/api_get_landuse.php?landuse_id=${landuseId}`);
    const json = await res.json();
    if (!json.success) return;
    const data = json.data;

    // Preselect values
    selMain.value = data.main_type;

    fillSubOptions(data.main_type, data.sub_type, data.type);

    selMain.onchange = () => {
        fillSubOptions(selMain.value);
        selType.innerHTML = `<option value="">-- Select Type --</option>`;
        selType.disabled = true;
    };
    selSub.onchange = () => fillTypeOptions(selMain.value, selSub.value);

    function fillSubOptions(mainVal, preselectSub, preselectType) {
        selSub.innerHTML = `<option value="">-- Select Sub Type --</option>`;
        Object.entries(subMap[mainVal] || {}).forEach(([val, label]) => {
            selSub.insertAdjacentHTML('beforeend', `<option value="${val}">${label}</option>`);
        });
        selSub.disabled = false;
        if (preselectSub) {
            selSub.value = preselectSub;
            fillTypeOptions(mainVal, preselectSub, preselectType);
        }
    }

    function fillTypeOptions(mainVal, subVal, preselectType) {
        selType.innerHTML = `<option value="">-- Select Type --</option>`;
        Object.entries((typeMap[mainVal] || {})[subVal] || {}).forEach(([val,label]) => {
            selType.insertAdjacentHTML('beforeend', `<option value="${val}">${label}</option>`);
        });
        selType.disabled = false;
        if (preselectType) selType.value = preselectType;
    }

    return data; // so we can fill other textboxes
}

// --- Click Handler ---
map.on('singleclick', async function(evt) {
    map.forEachFeatureAtPixel(evt.pixel, async function(feature, layer) {
        if (layer !== landuseLayer) return;

        const landuseId = feature.get('landuse_id');
        if (!landuseId) return;

        const formContainer = document.getElementById('formContainer');
        formContainer.innerHTML = '<span class="text-gray-500 font-semibold">Loading...</span>';

        // Build HTML including dropdowns
        formContainer.innerHTML = `
            <div class="flex flex-col gap-3">
                <div class="flex flex-col">
                    <label class="font-semibold text-gray-700">Landuse ID</label>
                    <input type="text" value="" id="txtLanduseId" disabled
                           class="border rounded px-3 py-2 bg-gray-100 text-gray-800">
                </div>
                <div class="flex flex-col">
                    <label class="font-semibold text-gray-700">Landuse Code</label>
                    <input type="text" value="" id="txtLanduseCode" disabled
                           class="border rounded px-3 py-2 bg-gray-100 text-gray-800">
                </div>
                <div class="flex flex-col">
                    <label class="font-semibold text-gray-700">Main Type</label>
                    <select id="selMain" class="border rounded px-3 py-2 text-gray-800"></select>
                </div>
                <div class="flex flex-col">
                    <label class="font-semibold text-gray-700">Sub Type</label>
                    <select id="selSub" class="border rounded px-3 py-2 text-gray-800"></select>
                </div>
                <div class="flex flex-col">
                    <label class="font-semibold text-gray-700">Type</label>
                    <select id="selType" class="border rounded px-3 py-2 text-gray-800"></select>
                </div>
                <div class="flex flex-col">
                    <label class="font-semibold text-gray-700">Ownership Type</label>
                    <input type="text" id="txtOwnership" class="border rounded px-3 py-2 text-gray-800">
                </div>
                <div class="flex flex-col">
                    <label class="font-semibold text-gray-700">Area (ha)</label>
                    <input type="text" id="txtArea" class="border rounded px-3 py-2 text-gray-800">
                </div>
                <div class="flex flex-col">
                    <label class="font-semibold text-gray-700">Remarks</label>
                    <textarea id="txtRemarks" class="border rounded px-3 py-2 text-gray-800 w-full" rows="3"></textarea>
                </div>
            </div>
        `;

        const data = await populateDropdowns(landuseId);

        // Fill other textboxes
        document.getElementById('txtLanduseId').value = data.landuse_id;
        document.getElementById('txtLanduseCode').value = data.landuse_code;
        document.getElementById('txtOwnership').value = data.ownership_type || '';
        document.getElementById('txtArea').value = data.area_ha || '';
        document.getElementById('txtRemarks').value = data.remarks || '';
    });
});
</script>
</body>
</html>
