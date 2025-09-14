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
<body class="bg-gray-100 min-h-screen flex flex-col space-y-4">

<header class="p-4 bg-gray-800 text-white flex justify-between items-center">
  <span>Welcome, <?= htmlspecialchars($_SESSION['user']['full_name']); ?> (<?= $_SESSION['user']['role']; ?>)</span>
  <div class="flex gap-2">
    <a href="field_map.php" class="bg-blue-600 text-white px-4 py-2 rounded shadow hover:bg-blue-700 transition">← Back to Layer Selection</a>
    <a href="../dashboard.php" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition">← Back to Dashboard</a>
    <a href="../logout.php" class="bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition">Logout</a>
    
  </div>
</header>

<h2 class="text-2xl font-bold text-gray-800">🌱 Landuse Data Collection</h2>

<!-- Selected GND Info -->
<div class="bg-white p-4 rounded-lg shadow flex flex-col md:flex-row md:items-center md:justify-between gap-2">
    <div>
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

    <!-- Form / Info -->
    <div class="bg-white rounded-lg shadow p-4">
        <div class="w-full max-w-3xl mx-auto bg-white rounded-lg shadow p-4">
            <div id="formContainer" class="flex flex-col gap-4 text-gray-700"></div>
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

    const res = await fetch('api/api_landuse_types.php');
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
        fill: new ol.style.Fill({ color: 'rgba(243, 0, 0, 0)' })
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
    layers: [ new ol.layer.Tile({ source: new ol.source.OSM({ attributions: '© <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors' }) }), gndLayer, landuseLayer ],
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

// --- GeoServer WMS Layer ---
const geoserverWMS = new ol.layer.Tile({
    source: new ol.source.TileWMS({
        url: 'http://192.168.8.200:8080/geoserver/grahana/wms',
        params: {
            'LAYERS': 'grahana:landuse_master',
            'TILED': true,
            'VERSION': '1.1.1',
            'FORMAT': 'image/png',
            'TRANSPARENT': true
        },
        serverType: 'geoserver',
        transition: 0
    }),
    visible:false, //start hidden
    opacity: 0.6 // adjust transparency
});

// Add to map
map.addLayer(geoserverWMS);

// --- Toggle WMS Layer ---
document.getElementById('toggleWMS').addEventListener('change', function() {
    geoserverWMS.setVisible(this.checked);
});


//
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

// Button click
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
    const res = await fetch(`api/api_get_landuse.php?landuse_id=${landuseId}`);
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
    let selectedFeature = null;
    map.forEachFeatureAtPixel(evt.pixel, async function(feature, layer) {
        if (layer !== landuseLayer) return;
        selectedFeature = feature;
        const landuseId = feature.get('landuse_id');
        if (!landuseId) return;

        // Highlight selected feature
        landuseLayer.setStyle(function(feat) {
            if (feat === selectedFeature) {
                return new ol.style.Style({
                    stroke: new ol.style.Stroke({ color: 'rgba(255,0,0,0.9)', width: 3 }),
                    fill: new ol.style.Fill({ color: 'rgba(255,200,200,0.4)' })
                });
            }
            return new ol.style.Style({
                stroke: new ol.style.Stroke({ color: 'rgba(15,92,0,0.6)', width: 1 }),
                fill: new ol.style.Fill({ color: 'rgba(8,107,38,0.1)' })
            });
        });

        const formContainer = document.getElementById('formContainer');
        formContainer.innerHTML = '<span class="text-gray-500 font-semibold">Loading...</span>';

        // Build HTML including dropdowns
        formContainer.innerHTML = `
            <div class="flex flex-col gap-3">
                <div class="flex flex-col">
                    <label class="font-semibold text-gray-700">Landuse ID</label>
                    <input type="text" value="" id="txtLanduseId" disabled
                           class="w-full  border rounded px-3 py-2 bg-gray-100 text-gray-800">
                </div>
                <div class="flex flex-col">
                    <label class="font-semibold text-gray-700">Landuse Code</label>
                    <input type="text" value="" id="txtLanduseCode" disabled
                           class="w-full  border rounded px-3 py-2 bg-gray-100 text-gray-800">
                </div>
                <div class="flex flex-col">
                    <label class="font-semibold text-gray-700">Main Type</label>
                    <select id="selMain" class="w-full  border rounded px-3 py-2 text-gray-800"></select>
                </div>
                <div class="flex flex-col">
                    <label class="font-semibold text-gray-700">Sub Type</label>
                    <select id="selSub" class="w-full  border rounded px-3 py-2 text-gray-800"></select>
                </div>
                <div class="flex flex-col">
                    <label class="font-semibold text-gray-700">Type</label>
                    <select id="selType" class="w-full  border rounded px-3 py-2 text-gray-800"></select>
                </div>
                <div class="flex flex-col">
                    <label class="font-semibold text-gray-700">Ownership Type</label>
                    <select id="ownership_type" name="ownership_type"
                            class="w-full  border rounded px-3 py-2 text-gray-800">
                        <option value="">Loading...</option>
                    </select>
                </div>
                <div class="flex flex-col">
                    <label class="font-semibold text-gray-700">Area (ha)</label>
                    <input type="text" id="txtArea" class="w-full  border rounded px-3 py-2 text-gray-800">
                </div>
                <div class="flex flex-col">
                    <label class="font-semibold text-gray-700">Remarks</label>
                    <textarea id="txtRemarks" class="w-full  border rounded px-3 py-2 text-gray-800 w-full" rows="3"></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button id="btnNext" 
                    class="bg-green-600 text-white px-4 py-2 rounded shadow hover:bg-green-700 transition">
                    Next →
                </button>
            </div>
        `;

        // Populate dropdowns -- main_type, sub_type and type
        const data = await populateDropdowns(landuseId);

        // Fill other textboxes
        document.getElementById('txtLanduseId').value = data.landuse_id;
        document.getElementById('txtLanduseCode').value = data.landuse_code;
        document.getElementById('txtArea').value = data.area_ha || '';
        document.getElementById('txtRemarks').value = data.remarks || '';

        // --- Ownership dropdown ---
        const selOwnership = document.getElementById('ownership_type');
        selOwnership.innerHTML = `<option value="">Loading...</option>`;
        try {
            const res = await fetch("api/api_ownership_type.php");
            const json = await res.json();
            selOwnership.innerHTML = `<option value="">-- Select Ownership Type --</option>`;
            json.data.forEach(val => {
                const opt = document.createElement("option");
                opt.value = val;
                opt.textContent = val;
                selOwnership.appendChild(opt);
            });
            selOwnership.value = data.ownership_type || '';
        } catch (err) {
            selOwnership.innerHTML = `<option value="">Failed to load</option>`;
        }
    });
});



// Next button function
 document.addEventListener("click", async function(e) {
    if (e.target && e.target.id === "btnNext") {
        e.preventDefault();

        const btnNext = document.getElementById('btnNext');
        //btnNext.disabled = true;
        //btnNext.classList.add("bg-gray-400", "cursor-not-allowed");
        //btnNext.classList.remove("bg-green-600", "hover:bg-green-700");

        const landuseId = document.getElementById('txtLanduseId').value;
        const selectedMainType = document.getElementById('selMain').value;
        const selectedType = document.getElementById('selType').value;
        const selectedSubType = document.getElementById('selSub').value;
        //const type = document.getElementById('selType').value;
        const ownershipType = document.getElementById('ownership_type').value;
        //const area = document.getElementById('txtArea').value.trim();

        if (!selectedMainType) {
            alert("Please select a Main Type.");
            return;
        }

       if (!selectedSubType) {
            alert("Please select a Sub Type.");
            return;
        }

        if (!selectedType) {
            alert("Please select a Type.");
            return;
        }

        if (!ownershipType) {
            alert("Please select a Ownership Type.");
            return;
        }

        
        const payload = {
            landuse_id: landuseId,
            main_type: document.getElementById('selMain').value,
            sub_type: document.getElementById('selSub').value,
            type: selectedType,
            ownership_type: document.getElementById('ownership_type').value,
            area_ha: document.getElementById('txtArea').value,
            remarks: document.getElementById('txtRemarks').value
        };

        try {
            // Save main attributes first
            const res = await fetch("api/api_save_landuse_master.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams(payload)
            });
            const data = await res.json();

            if (!data.success) {
                alert("Error saving: " + data.message);
                return;
            }

            // This is for testing
            //alert("Main attributes saved for Landuse ID: " + data.landuse_id + " \n Selected Landuse Sub Type: "+ selectedSubType +" \n Selected Landuse Type: "+ selectedType);

           // Redirect to type-specific form page
            let formPage = '';
            if (selectedSubType === 'fld' && selectedType === 'pdy') {
                alert("You are now entering to Paddy Database");
                formPage = 'forms/crop_field.php';
            }
            else if (selectedSubType === 'plt' && selectedType === 'coc') {
                alert("You are now entering to Coconut Database");
                formPage = 'forms/coconut.php';
            }
            else if (selectedType === 'frs') {
                formPage = 'forms/forest_form.php';
            }
            else if (selectedType === 'urb') {
                formPage = 'forms/residential.php';
            }
            else return; // no additional form for this type

            // Redirect to the selected form
            window.location.href = formPage
                + "?landuse_id=" + encodeURIComponent(landuseId)
                + "&area_ha=" + encodeURIComponent(document.getElementById('txtArea').value);
 

        } catch(err) {
            alert("Request failed: " + err);
        }
    }
});

</script>
</body>
</html>
