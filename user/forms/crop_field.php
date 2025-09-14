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
$drainage_status    = get_enum_values($pdo, 'drainage_status_enum');
$suitability_class  = get_enum_values($pdo, 'suitability_class_enum');

// --------------------
// 3. Existing Data
// --------------------
$crop = [];
if ($landuse_id) {
    $stmt = $pdo->prepare("SELECT * FROM landuse.crop_field WHERE landuse_id = :lid LIMIT 1");
    $stmt->execute(['lid' => $landuse_id]);
    $crop = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>🌾 Crop Field Data</title>
<link rel="icon" type="image/x-icon" href="../../assets/img/gis_nwp.ico">
<link rel="stylesheet" href="../../assets/css/tailwind.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col p-6 space-y-4">

<h2 class="text-2xl font-bold text-gray-800 mb-4">🌾 Crop Field Data</h2>

<div class="bg-white p-6 rounded-lg shadow max-w-3xl mx-auto">
    <form id="cropForm" enctype="multipart/form-data">
        <input type="hidden" name="landuse_id" value="<?= htmlspecialchars($landuse_id ?? '') ?>">
        <?php if (!empty($crop['crop_id'])): ?>
            <input type="hidden" name="crop_id" value="<?= htmlspecialchars($crop['crop_id']) ?>">
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <!-- Owner -->
            <div class="mb-4 relative">
            <label class="block text-sm font-medium text-gray-700">Owner</label>
            <input 
                type="text" 
                id="ownerSearch" 
                placeholder="Search owner by name..." 
                class="w-full border rounded p-2"
                autocomplete="off"
                value="<?= isset($existing['owner_name']) ? htmlspecialchars($existing['owner_name']) : '' ?>"
            >
            <input type="hidden" name="owner_id" id="ownerId" value="<?= isset($existing['owner_id']) ? $existing['owner_id'] : '' ?>">
            <ul id="ownerResults" class="absolute z-10 bg-white border rounded mt-1 max-h-40 overflow-y-auto w-full hidden"></ul>
            </div>


            <!-- Active -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Active</label>
                <select name="is_active" class="border rounded px-3 py-2">
                    <option value="1" <?= ($crop['is_active']??1)==1?'selected':'' ?>>Yes</option>
                    <option value="0" <?= ($crop['is_active']??1)==0?'selected':'' ?>>No</option>
                </select>
            </div>

            <!-- Extent ha -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Extent (ha)</label>
                <input type="text" name="extent_ha" 
                    value="<?= htmlspecialchars($crop['extent_ha'] ?? $area_ha ?? '') ?>" 
                    class="border rounded px-3 py-2">
            </div>

            <!-- Water Source -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Water Source</label>
                <select name="water_source" class="border rounded px-3 py-2">
                    <option value="">-- Select --</option>
                    <?php foreach($water_sources as $w): ?>
                        <option value="<?= $w ?>" <?= ($crop['water_source']??'')==$w?'selected':'' ?>><?= $w ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Soil Type -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Soil Type</label>
                <input type="text" name="soil_type" value="<?= htmlspecialchars($crop['soil_type'] ?? '') ?>" class="border rounded px-3 py-2">
            </div>

            <!-- Ownership Type -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Ownership Type</label>
                <select name="ownership_type" class="border rounded px-3 py-2">
                    <option value="">-- Select --</option>
                    <?php foreach($ownership_types as $o): ?>
                        <option value="<?= $o ?>" <?= ($crop['ownership_type']??'')==$o?'selected':'' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Land Tenure -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Land Tenure</label>
                <select name="land_tenure" class="border rounded px-3 py-2">
                    <option value="">-- Select --</option>
                    <?php foreach($land_tenures as $l): ?>
                        <option value="<?= $l ?>" <?= ($crop['land_tenure']??'')==$l?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Deed Number -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Deed Number</label>
                <input type="text" name="deed_number" value="<?= htmlspecialchars($crop['deed_number'] ?? '') ?>" class="border rounded px-3 py-2">
            </div>

            <!-- Elevation -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Elevation (m)</label>
                <input type="number" step="0.01" name="elevation_m" value="<?= htmlspecialchars($crop['elevation_m'] ?? '') ?>" class="border rounded px-3 py-2">
            </div>

            <!-- Flood Risk -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Flood Risk Level</label>
                <select name="flood_risk_level" class="border rounded px-3 py-2">
                    <option value="">-- Select --</option>
                    <?php foreach($flood_risks as $f): ?>
                        <option value="<?= $f ?>" <?= ($crop['flood_risk_level']??'')==$f?'selected':'' ?>><?= $f ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Drainage Status -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Drainage Status</label>
                <select name="drainage_status" class="border rounded px-3 py-2">
                    <option value="">-- Select --</option>
                    <?php foreach($drainage_status as $d): ?>
                        <option value="<?= $d ?>" <?= ($crop['drainage_status']??'')==$d?'selected':'' ?>><?= $d ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Suitability Class -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Suitability Class</label>
                <select name="suitability_class" class="border rounded px-3 py-2">
                    <option value="">-- Select --</option>
                    <?php foreach($suitability_class as $s): ?>
                        <option value="<?= $s ?>" <?= ($crop['suitability_class']??'')==$s?'selected':'' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Land Capability Rating -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Land Capability Rating (0-10)</label>
                <input type="number" min="0" max="10" name="land_capability_rating" value="<?= htmlspecialchars($crop['land_capability_rating'] ?? '') ?>" class="border rounded px-3 py-2">
            </div>

            <!-- Deed Image -->
            <div class="flex flex-col">
                <label class="font-semibold text-gray-700">Deed Image</label>
                <input type="file" name="deed_image" class="border rounded px-3 py-2">
                <?php if (!empty($crop['deed_image'])): ?>
                    <p class="text-sm text-gray-500 mt-1">Current: <?= htmlspecialchars($crop['deed_image']) ?></p>
                <?php endif; ?>
            </div>

        </div>

        <div class="mt-6">
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Save & Continue</button>
        </div>
    </form>
</div>

<script>
document.getElementById('cropForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    try {
        const res = await fetch('api_save_cropfield.php', {
            method: 'POST',
            body: formData
        });

        const text = await res.text(); // get raw text first
        let data;
        try {
            data = JSON.parse(text);
        } catch (err) {
            alert("Server returned invalid JSON:\n\n" + text);
            return;
        }

        if (data.success) {
            alert('Crop field saved successfully!');
            window.location.href = 'paddy_cultivation.php?landuse_id=' + encodeURIComponent(formData.get('landuse_id'));
        } else {
            alert('Error: ' + data.message);
        }
    } catch(err) {
        alert('Request failed: ' + err);
    }
});
</script>

<!-- Ownership Search -->
<script>
const searchInput = document.getElementById('ownerSearch');
const resultsBox = document.getElementById('ownerResults');
const hiddenId = document.getElementById('ownerId');

searchInput.addEventListener('input', async () => {
  const q = searchInput.value.trim();
  if (q.length < 2) {
    resultsBox.innerHTML = '';
    resultsBox.classList.add('hidden');
    return;
  }

  const res = await fetch('api_search_owners.php?q=' + encodeURIComponent(q));
  const data = await res.json();

  resultsBox.innerHTML = '';
  if (data.length) {
    data.forEach(owner => {
      const li = document.createElement('li');
      li.innerHTML = `<span class="font-medium">${owner.owner_name}</span> 
                      <span class="text-xs text-gray-500 ml-2">(${owner.owner_code})</span>`;
      li.className = "px-3 py-2 hover:bg-blue-100 cursor-pointer";
      li.onclick = () => {
        searchInput.value = owner.owner_name;
        hiddenId.value = owner.owner_id;
        resultsBox.classList.add('hidden');
      };
      resultsBox.appendChild(li);
    });
    resultsBox.classList.remove('hidden');
  } else {
    resultsBox.classList.add('hidden');
  }
});

// Hide dropdown when clicking outside
document.addEventListener('click', e => {
  if (!resultsBox.contains(e.target) && e.target !== searchInput) {
    resultsBox.classList.add('hidden');
  }
});
</script>


</body>
</html>
