<?php
session_start();
require "../../db.php";

// --------------------
// 1. User & GND Check
// --------------------
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'field') {
    header("Location: ../../dashboard.php");
    exit;
}

if (!isset($_SESSION['selected_gnd'])) {
    $_SESSION['error'] = "Please select a GND first.";
    header("Location: ../../dashboard.php");
    exit;
}

$gnd = $_SESSION['selected_gnd'];
$area_ha    = $_GET['area_ha'] ?? null;
$landuse_id = $_GET['landuse_id'] ?? null;

// --------------------
// 2. Fetch Enums
// --------------------
function get_enum_values($pdo, $enum_name) {
    $stmt = $pdo->prepare("
        SELECT e.enumlabel AS value
        FROM pg_type t
        JOIN pg_enum e ON t.oid = e.enumtypid
        WHERE t.typname = :enum
        ORDER BY e.enumsortorder
    ");
    $stmt->execute(['enum' => $enum_name]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$water_sources      = get_enum_values($pdo, 'water_source_enum');
$ownership_types    = get_enum_values($pdo, 'ownership_type_enum');
$land_tenures       = get_enum_values($pdo, 'land_tenure_enum');
$flood_risks        = get_enum_values($pdo, 'flood_risk_enum');
$suitability_class  = get_enum_values($pdo, 'suitability_class_enum');
$irrigation_methods = get_enum_values($pdo, 'irrigation_method_enum');

// --------------------
// 3. Existing Data
// --------------------
$coconut = [];
$landuse_geojson = null;
if ($landuse_id) {
    $stmt = $pdo->prepare("SELECT * , ST_AsGeoJSON(geom) as geojson FROM landuse.landuse_master WHERE landuse_id = :lid LIMIT 1");
    $stmt->execute(['lid' => $landuse_id]);
    $coconut = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $landuse_geojson = $coconut['geojson'] ?? null;
}

// --------------------
// 4. Fetch GND geometry
// --------------------
$stmt = $pdo->prepare("SELECT ST_AsGeoJSON(geom) as geojson FROM gnd WHERE gid = :gid LIMIT 1");
$stmt->execute(['gid' => $gnd['gid']]);
$gnd_geo = $stmt->fetch(PDO::FETCH_ASSOC);
$gnd_geojson = $gnd_geo['geojson'] ?? null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🥥 Coconut Data Collection</title>
<link rel="icon" type="image/x-icon" href="../../assets/img/gis_nwp.ico">
<link rel="stylesheet" href="../../assets/css/tailwind.min.css">
<link rel="stylesheet" href="../../assets/ol/ol.css">
<style>
    #map { width: 100%; height: 400px; border-radius: 0.5rem; margin-bottom: 1rem; }
    #ownerResults { max-height: 150px; overflow-y:auto; z-index:999; }
</style>
</head>
<body class="bg-gray-100 min-h-screen p-6">

<h2 class="text-2xl font-bold text-gray-800 mb-4">🥥 Coconut Data Collection</h2>

<!-- Map -->
<div id="map"></div>

<!-- Coconut Form -->
<div class="bg-white p-6 rounded-lg shadow max-w-3xl mx-auto">
    <form id="coconutForm" enctype="multipart/form-data">
        <input type="hidden" name="landuse_id" value="<?= htmlspecialchars($landuse_id ?? '') ?>">
        <?php if (!empty($coconut['crop_id'])): ?>
            <input type="hidden" name="crop_id" value="<?= htmlspecialchars($coconut['crop_id']) ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            
            <!-- Owner -->
            <div class="mb-4 relative">
                <label class="block text-sm font-medium text-gray-700">Owner</label>
                <input type="text" id="ownerSearch" placeholder="Search owner by name..."
                    class="w-full border rounded p-2"
                    autocomplete="off"
                    value="<?= isset($coconut['owner_name']) ? htmlspecialchars($coconut['owner_name']) : '' ?>">
                <input type="hidden" name="owner_id" id="ownerId" value="<?= $coconut['owner_id'] ?? '' ?>">
                <ul id="ownerResults" class="absolute bg-white border rounded w-full hidden"></ul>
            </div>

            <!-- Active -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Active</label>
                <select name="is_active" class="border rounded px-3 py-2">
                    <option value="1" <?= ($coconut['is_active']??1)==1?'selected':'' ?>>Yes</option>
                    <option value="0" <?= ($coconut['is_active']??1)==0?'selected':'' ?>>No</option>
                </select>
            </div>

            <!-- Extent ha -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Extent (ha)</label>
                <input type="text" name="extent_ha" 
                    value="<?= htmlspecialchars($coconut['extent_ha'] ?? $area_ha ?? '') ?>" 
                    class="border rounded px-3 py-2">
            </div>

            <!-- Water Source -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Water Source</label>
                <select name="water_source" class="border rounded px-3 py-2">
                    <option value="">-- Select --</option>
                    <?php foreach($water_sources as $w): ?>
                        <option value="<?= $w ?>" <?= ($coconut['water_source']??'')==$w?'selected':'' ?>><?= $w ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Soil Type -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Soil Type</label>
                <input type="text" name="soil_type" value="<?= htmlspecialchars($coconut['soil_type'] ?? '') ?>" class="border rounded px-3 py-2">
            </div>

            <!-- Coconut Variety -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Coconut Variety</label>
                <input type="text" name="coconut_variety" value="<?= isset($coconut['coconut_variety']) ? implode(', ',$coconut['coconut_variety']) : '' ?>" class="border rounded px-3 py-2">
            </div>

            <!-- Planting Density -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Planting Density (palms/ha)</label>
                <input type="number" min="0" name="planting_density" value="<?= htmlspecialchars($coconut['planting_density'] ?? '') ?>" class="border rounded px-3 py-2">
            </div>

            <!-- Number of Seedlings -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Number of Seedlings 🌱</label>
                <input type="number" min="0" name="no_seedlings" value="<?= htmlspecialchars($coconut['no_seedlings'] ?? '') ?>" class="border rounded px-3 py-2">
            </div>

            <!-- Number of Young Palms -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Number of Young Palms 🌴</label>
                <input type="number" min="0" name="no_youngpalms" value="<?= htmlspecialchars($coconut['no_youngpalms'] ?? '') ?>" class="border rounded px-3 py-2">
            </div>

            <!-- Number of Mature Palms -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Number of Mature Palms 🥥 </label>
                <input type="number" min="0" name="no_maturepalms" value="<?= htmlspecialchars($coconut['no_maturepalms'] ?? '') ?>" class="border rounded px-3 py-2">
            </div>

            <!-- Number of Old Palms -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Number of Old Palms 🏝️</label>
                <input type="number" min="0" name="no_oldpalms" value="<?= htmlspecialchars($coconut['no_oldpalms'] ?? '') ?>" class="border rounded px-3 py-2">
            </div>

            <!-- Ownership Type -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Ownership Type</label>
                <select name="ownership_type" class="border rounded px-3 py-2">
                    <option value="">-- Select --</option>
                    <?php foreach($ownership_types as $o): ?>
                        <option value="<?= $o ?>" <?= ($coconut['ownership_type']??'')==$o?'selected':'' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Land Tenure -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Land Tenure</label>
                <select name="land_tenure" class="border rounded px-3 py-2">
                    <option value="">-- Select --</option>
                    <?php foreach($land_tenures as $l): ?>
                        <option value="<?= $l ?>" <?= ($coconut['land_tenure']??'')==$l?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Deed Number -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Deed Number</label>
                <input type="text" name="deed_number" value="<?= htmlspecialchars($coconut['deed_number'] ?? '') ?>" class="border rounded px-3 py-2">
            </div>

            <!-- Elevation -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Elevation (m)</label>
                <input type="number" step="0.01" name="elevation_m" value="<?= htmlspecialchars($coconut['elevation_m'] ?? '') ?>" class="border rounded px-3 py-2">
            </div>

            <!-- Flood Risk -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Flood Risk Level</label>
                <select name="flood_risk_level" class="border rounded px-3 py-2">
                    <option value="">-- Select --</option>
                    <?php foreach($flood_risks as $f): ?>
                        <option value="<?= $f ?>" <?= ($coconut['flood_risk_level']??'')==$f?'selected':'' ?>><?= $f ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Irrigation Method -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Irrigation Method</label>
                <select name="irrigation_method" class="border rounded px-3 py-2">
                    <option value="">-- Select --</option>
                    <?php foreach($irrigation_methods as $i): ?>
                        <option value="<?= $i ?>" <?= ($coconut['irrigation_method']??'')==$i?'selected':'' ?>><?= $i ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Land Capability Rating -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Land Capability Rating (0-10)</label>
                <input type="number" min="0" max="10" name="land_capability_rating" value="<?= htmlspecialchars($coconut['land_capability_rating'] ?? '') ?>" class="border rounded px-3 py-2">
            </div>

            <!-- Suitability Class -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Suitability Class</label>
                <select name="suitability_class" class="border rounded px-3 py-2">
                    <option value="">-- Select --</option>
                    <?php foreach($suitability_class as $s): ?>
                        <option value="<?= $s ?>" <?= ($coconut['suitability_class']??'')==$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Deed Image -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Deed Image</label>
                <input type="file" name="deed_image" class="border rounded px-3 py-2">
                <?php if (!empty($coconut['deed_image'])): ?>
                    <p class="text-sm text-gray-500 mt-1">Current: <?= htmlspecialchars($coconut['deed_image']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-6">
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Save & Continue</button>
        </div>
    </form>
</div>

<!-- OpenLayers -->
<script src="../../assets/ol/ol.js"></script>
<script>
// --- GND & Landuse Layer ---
const gndGeoJSON = <?= $gnd_geojson ?>;
const landuseGeoJSON = <?= $landuse_geojson ?? 'null' ?>;

const gndFeature = new ol.format.GeoJSON().readFeature(gndGeoJSON, { dataProjection:'EPSG:3857', featureProjection:'EPSG:3857'});
const gndSource = new ol.source.Vector({features:[gndFeature]});
const gndLayer = new ol.layer.Vector({source:gndSource, style:new ol.style.Style({stroke:new ol.style.Stroke({color:'rgba(243,0,0,0.5)',width:3}), fill:new ol.style.Fill({color:'rgba(243,0,0,0)'})})});

let landuseLayer = null;
if(landuseGeoJSON){
    const landuseFeature = new ol.format.GeoJSON().readFeature(landuseGeoJSON, { dataProjection:'EPSG:3857', featureProjection:'EPSG:3857'});
    landuseLayer = new ol.layer.Vector({
        source: new ol.source.Vector({features:[landuseFeature]}),
        style: new ol.style.Style({
            stroke: new ol.style.Stroke({color:'rgba(0,100,0,0.7)',width:2}),
            fill: new ol.style.Fill({color:'rgba(0,200,0,0.2)'})
        })
    });
}

const map = new ol.Map({
    target:'map',
    layers:[new ol.layer.Tile({source:new ol.source.OSM({attributions: '© <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'})}), gndLayer, ...(landuseLayer?[landuseLayer]:[])],
    view:new ol.View({center: ol.extent.getCenter(gndSource.getExtent()), zoom:13})
});

// --- Geolocation ---
const geolocation = new ol.Geolocation({ tracking:false, projection:map.getView().getProjection() });
const positionFeature = new ol.Feature();
positionFeature.setStyle(new ol.style.Style({ image: new ol.style.Circle({ radius:7, fill:new ol.style.Fill({color:'rgba(0,153,255,0.7)'}), stroke:new ol.style.Stroke({color:'#fff',width:2})})}));

const geoSource = new ol.source.Vector({features:[positionFeature]});
const geoLayer = new ol.layer.Vector({source:geoSource});
map.addLayer(geoLayer);

const locateBtn = document.createElement('button');
locateBtn.innerHTML = '📍 My Location';
locateBtn.className = 'absolute top-2 right-2 bg-blue-600 text-white px-2 py-1 rounded';
locateBtn.onclick = ()=>{
    geolocation.setTracking(true);
    geolocation.once('change:position', ()=>{
        const coords = geolocation.getPosition();
        if(coords){positionFeature.setGeometry(new ol.geom.Point(coords)); map.getView().animate({center:coords, zoom:18, duration:1000});}
        geolocation.setTracking(false);
    });
};
document.body.appendChild(locateBtn);
</script>

<!-- Owner Search -->
<script>
const searchInput = document.getElementById('ownerSearch');
const resultsBox = document.getElementById('ownerResults');
const hiddenId = document.getElementById('ownerId');

searchInput.addEventListener('input', async () => {
  const q = searchInput.value.trim();
  if (q.length < 2) { resultsBox.innerHTML=''; resultsBox.classList.add('hidden'); return; }
  const res = await fetch('api_search_owners.php?q='+encodeURIComponent(q));
  const data = await res.json();
  resultsBox.innerHTML='';
  if(data.length){
    data.forEach(owner=>{
      const li=document.createElement('li');
      li.innerHTML=`<span class="font-medium">${owner.owner_name}</span> <span class="text-xs text-gray-500 ml-2">(${owner.owner_code})</span>`;
      li.className="px-3 py-2 hover:bg-blue-100 cursor-pointer";
      li.onclick=()=>{searchInput.value=owner.owner_name; hiddenId.value=owner.owner_id; resultsBox.classList.add('hidden');};
      resultsBox.appendChild(li);
    });
    resultsBox.classList.remove('hidden');
  } else resultsBox.classList.add('hidden');
});

document.addEventListener('click', e => { if(!resultsBox.contains(e.target) && e.target!==searchInput) resultsBox.classList.add('hidden'); });
</script>

<!-- Form Submit -->
<script>
document.getElementById('coconutForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = this;
    const formData = new FormData(form);

    if (!navigator.geolocation) {
        alert("Geolocation not supported by your browser.");
        return;
    }

    navigator.geolocation.getCurrentPosition(
        async (pos) => {
            const lon = pos.coords.longitude;
            const lat = pos.coords.latitude;

            // Append GPS coords to formData
            formData.append("longitude", lon);
            formData.append("latitude", lat);

            try {
                const res = await fetch('api_save_coconut.php', {
                    method: 'POST',
                    body: formData
                });

                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch(err) {
                    alert("Server returned invalid JSON:\n\n" + text);
                    return;
                }

                if(data.success){
                    alert('Coconut data saved!');
                    window.location.href = 'coconut_yield.php?landuse_id=' + encodeURIComponent(formData.get('landuse_id'));
                } else {
                    alert('Error: ' + data.message);
                }
            } catch(err) {
                alert('Request failed: ' + err);
            }
        },
        (err) => {
            alert("Failed to get location: " + err.message);
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
});

</script>

</body>
</html>
