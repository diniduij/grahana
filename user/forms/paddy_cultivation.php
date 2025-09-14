<?php
require "../../db.php";

if (!isset($_GET['landuse_id']) || empty($_GET['landuse_id'])) {
    echo "No landuse_id provided.";
    exit;
}

$landuse_id = $_GET['landuse_id'];

// Fetch crop_id for this landuse_id
$stmt = $pdo->prepare("SELECT crop_id FROM landuse.crop_field WHERE landuse_id = :landuse_id LIMIT 1");
$stmt->execute(['landuse_id' => $landuse_id]);
$crop = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$crop) {
    echo "No crop field found for this landuse.";
    exit;
}
$crop_id = $crop['crop_id'];

// Fetch existing cultivation records
$stmt = $pdo->prepare("SELECT * FROM landuse.paddy_cultivation WHERE crop_id = :crop_id ORDER BY cultivated_date DESC");
$stmt->execute(['crop_id' => $crop_id]);
$cultivations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get enums
$field_types = $pdo->query("SELECT unnest(enum_range(NULL::landuse.paddy_field_type_enum))")->fetchAll(PDO::FETCH_COLUMN);
$irrigation_methods = $pdo->query("SELECT unnest(enum_range(NULL::landuse.irrigation_method_enum))")->fetchAll(PDO::FETCH_COLUMN);
$seasons = $pdo->query("SELECT unnest(enum_range(NULL::landuse.paddyseasons_enum))")->fetchAll(PDO::FETCH_COLUMN);
$harvesting_methods = $pdo->query("SELECT unnest(enum_range(NULL::landuse.harvesting_method_enum))")->fetchAll(PDO::FETCH_COLUMN);

// Fetch stage data for latest cultivation (if exists)
$stages = [];
$latest_cultivation_id = null;
if (!empty($cultivations)) {
    $latest_cultivation_id = $cultivations[0]['cultivation_id'];
    $stmt = $pdo->prepare("SELECT * FROM landuse.paddy_stages WHERE cultivation_id = :cid ORDER BY applied_date ASC");
    $stmt->execute(['cid' => $latest_cultivation_id]);
    $stages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>🌾 Paddy Cultivation</title>
    <link rel="icon" type="image/x-icon" href="../../assets/img/gis_nwp.ico">
    <link rel="stylesheet" href="../../assets/css/tailwind.min.css">
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-7xl mx-auto bg-white rounded-xl shadow p-6">
        <h2 class="text-2xl font-bold mb-4">🌾 Paddy Cultivation Records</h2>

        <div class="mb-4">
            <a href="../collect_landuse.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 inline-block">
                &larr; Back to Land Use
            </a>
        </div>
        <?php if (empty($cultivations)): ?>
            <p class="text-gray-600">No cultivation records found.</p>
            <button id="addSeasonBtn" class="mt-4 px-4 py-2 bg-green-600 text-white rounded-lg shadow hover:bg-green-700">Add Cultivation & Harvesting Season</button>
        <?php else: ?>
            <button id="addSeasonBtn2" class="mb-4 px-4 py-2 bg-green-600 text-white rounded-lg shadow hover:bg-green-700">Add New Season</button>
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-300 text-sm">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="px-2 py-1 border">ID</th>
                            <th class="px-2 py-1 border">Field Type</th>
                            <th class="px-2 py-1 border">Variety</th>
                            <th class="px-2 py-1 border">Duration</th>
                            <th class="px-2 py-1 border">Irrigation</th>
                            <th class="px-2 py-1 border">Season</th>
                            <th class="px-2 py-1 border">Est. Yield</th>
                            <th class="px-2 py-1 border">Method</th>
                            <th class="px-2 py-1 border">Seed Amt</th>
                            <th class="px-2 py-1 border">Cultivated</th>
                            <th class="px-2 py-1 border">Harvested</th>
                            <th class="px-2 py-1 border">Harvesting</th>
                            <th class="px-2 py-1 border">Yield</th>
                            <th class="px-2 py-1 border">Remarks</th>
                            <th class="px-2 py-1 border">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cultivations as $c): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-2 py-1 border"><?= $c['cultivation_id'] ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($c['field_type']) ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($c['paddy_variety']) ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($c['crop_duration']) ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($c['irrigation_method']) ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($c['season']) ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($c['est_yield_kgpha']) ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($c['cultivation_method']) ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($c['seed_amount']) ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($c['cultivated_date']) ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($c['havested_date']) ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($c['harvesting_method']) ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($c['yield_kg']) ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($c['remarks']) ?></td>
                                <td class="px-2 py-1 border">
                                    <button class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600" onclick='editSeason(<?= json_encode($c) ?>)'>Edit</button>
                                    <button class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600" onclick="deleteSeason(<?= $c['cultivation_id'] ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Cultivation Form -->
        <div id="seasonFormContainer" class="hidden mt-6 p-4 bg-gray-50 border rounded-lg">
            <h3 id="formTitle" class="text-xl font-semibold mb-4">Add Cultivation Season</h3>
            <form id="seasonForm" class="space-y-3">
                <input type="hidden" name="cultivation_id" id="cultivation_id">
                <input type="hidden" name="crop_id" value="<?= $crop_id ?>">

                <div>
                    <label class="block text-sm">Field Type:</label>
                    <select name="field_type" required class="w-full border rounded p-2">
                        <?php foreach ($field_types as $ft): ?>
                            <option value="<?= $ft ?>"><?= $ft ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm">Paddy Variety:</label>
                    <input type="text" name="paddy_variety" required class="w-full border rounded p-2">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm">Crop Duration (days):</label>
                        <input type="number" name="crop_duration" min="1" required class="w-full border rounded p-2">
                    </div>
                    <div>
                        <label class="block text-sm">Irrigation Method:</label>
                        <select name="irrigation_method" class="w-full border rounded p-2">
                            <option value="">-- Select --</option>
                            <?php foreach ($irrigation_methods as $im): ?>
                                <option value="<?= $im ?>"><?= $im ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm">Season:</label>
                    <select name="season" required class="w-full border rounded p-2">
                        <?php foreach ($seasons as $s): ?>
                            <option value="<?= $s ?>"><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm">Estimated Yield (kg/ha):</label>
                    <input type="number" step="0.01" name="est_yield_kgpha" class="w-full border rounded p-2">
                </div>

                <div>
                    <label class="block text-sm">Cultivation Method:</label>
                    <input type="text" name="cultivation_method" class="w-full border rounded p-2">
                </div>

                <div>
                    <label class="block text-sm">Seed Amount (kg):</label>
                    <input type="number" step="0.01" name="seed_amount" class="w-full border rounded p-2">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm">Cultivated Date:</label>
                        <input type="date" name="cultivated_date" class="w-full border rounded p-2">
                    </div>
                    <div>
                        <label class="block text-sm">Harvested Date:</label>
                        <input type="date" name="havested_date" class="w-full border rounded p-2">
                    </div>
                </div>

                <div>
                    <label class="block text-sm">Harvesting Method:</label>
                    <select name="harvesting_method" class="w-full border rounded p-2">
                        <option value="">-- Select --</option>
                        <?php foreach ($harvesting_methods as $hm): ?>
                            <option value="<?= $hm ?>"><?= $hm ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm">Yield (kg):</label>
                    <input type="number" step="0.01" name="yield_kg" class="w-full border rounded p-2">
                </div>

                <div>
                    <label class="block text-sm">Remarks:</label>
                    <textarea name="remarks" class="w-full border rounded p-2"></textarea>
                </div>

                <div class="flex gap-4">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
                    <button type="button" onclick="cancelForm()" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Stage Section -->
        <h2 class="text-xl font-bold mt-8 mb-4">Stage Data (Paddy Inputs)</h2>
        <?php if (empty($stages)): ?>
            <p class="text-gray-600">No stage inputs recorded for this cultivation.</p>
            <?php if ($latest_cultivation_id): ?>
                <button id="addStageBtn" class="mt-4 px-4 py-2 bg-green-600 text-white rounded-lg shadow hover:bg-green-700">Add Stage Information</button>
            <?php endif; ?>
        <?php else: ?>
            <button id="addStageBtn2" class="mb-4 px-4 py-2 bg-green-600 text-white rounded-lg shadow hover:bg-green-700">Add New Stage</button>
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-300 text-sm">
                    <thead class="bg-gray-200">
                        <tr>
                            <th class="px-2 py-1 border">Type</th>
                            <th class="px-2 py-1 border">Stage</th>
                            <th class="px-2 py-1 border">Description</th>
                            <th class="px-2 py-1 border">Quantity</th>
                            <th class="px-2 py-1 border">Date</th>
                            <th class="px-2 py-1 border">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stages as $s): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-2 py-1 border"><?= htmlspecialchars($s['input_type']) ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($s['stage']) ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($s['description']) ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($s['quantity']) ?></td>
                                <td class="px-2 py-1 border"><?= htmlspecialchars($s['applied_date']) ?></td>
                                <td class="px-2 py-1 border">
                                    <button class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600" onclick='editStage(<?= json_encode($s) ?>)'>Edit</button>
                                    <button class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600" onclick="deleteStage(<?= $s['input_id'] ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Stage Form -->
        <div id="stageFormContainer" class="hidden mt-6 p-4 bg-gray-50 border rounded-lg">
            <h3 id="stageFormTitle" class="text-xl font-semibold mb-4">Add Stage Information</h3>
            <form id="stageForm" class="space-y-3">
                <input type="hidden" name="input_id" id="input_id">
                <input type="hidden" name="cultivation_id" value="<?= $latest_cultivation_id ?? '' ?>">

                <div>
                    <label class="block text-sm">Input Type:</label>
                    <select name="input_type" required class="w-full border rounded p-2">
                        <option value="">-- Select --</option>
                        <option value="Fertilizer">Fertilizer</option>
                        <option value="Pesticide">Pesticide</option>
                        <option value="Weedicide">Weedicide</option>
                        <option value="Irrigation">Irrigation</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm">Stage:</label>
                    <input type="text" name="stage" placeholder="e.g. Basal, Tillering" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-sm">Description:</label>
                    <textarea name="description" class="w-full border rounded p-2"></textarea>
                </div>
                <div>
                    <label class="block text-sm">Quantity (kg/ha, L/ha, etc.):</label>
                    <input type="number" step="0.01" name="quantity" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-sm">Applied Date:</label>
                    <input type="date" name="applied_date" class="w-full border rounded p-2">
                </div>

                <div class="flex gap-4">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
                    <button type="button" onclick="cancelStageForm()" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Cancel</button>
                </div>
            </form>
        </div>
    </div>

<script>
function editSeason(data) {
    document.getElementById('seasonFormContainer').classList.remove('hidden');
    document.getElementById('formTitle').innerText = "Edit Cultivation Season";
    for (const key in data) {
        if (document.querySelector(`[name=${key}]`)) {
            document.querySelector(`[name=${key}]`).value = data[key];
        }
    }
}
function deleteSeason(id) {
    if (!confirm("Are you sure to delete this record?")) return;
    fetch('api_delete_paddy_cultivation.php?id=' + id)
        .then(res => res.json())
        .then(data => { if (data.success) location.reload(); else alert("Error: " + data.message); });
}
function cancelForm() {
    document.getElementById('seasonFormContainer').classList.add('hidden');
}
document.getElementById('addSeasonBtn')?.addEventListener('click', () => {
    document.getElementById('seasonFormContainer').classList.remove('hidden');
    document.getElementById('formTitle').innerText = "Add Cultivation Season";
    document.getElementById('seasonForm').reset();
    document.getElementById('cultivation_id').value = "";
});
document.getElementById('addSeasonBtn2')?.addEventListener('click', () => {
    document.getElementById('seasonFormContainer').classList.remove('hidden');
    document.getElementById('formTitle').innerText = "Add Cultivation Season";
    document.getElementById('seasonForm').reset();
    document.getElementById('cultivation_id').value = "";
});
document.getElementById('seasonForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const formData = new FormData(this);
    const res = await fetch('api_save_paddy_cultivation.php', { method:'POST', body:formData });
    const data = await res.json();
    if (data.success) { alert('Season saved successfully!'); window.location.reload(); }
    else alert('Error: ' + data.message);
});

// Stage section
function editStage(data) {
    document.getElementById('stageFormContainer').classList.remove('hidden');
    document.getElementById('stageFormTitle').innerText = "Edit Stage Data";
    for (const key in data) {
        if (document.querySelector(`#stageForm [name=${key}]`)) {
            document.querySelector(`#stageForm [name=${key}]`).value = data[key];
        }
    }
}
function deleteStage(id) {
    if (!confirm("Are you sure to delete this stage record?")) return;
    fetch('api_delete_paddy_stage.php?id=' + id)
        .then(res => res.json())
        .then(data => { if (data.success) location.reload(); else alert("Error: " + data.message); });
}
function cancelStageForm() {
    document.getElementById('stageFormContainer').classList.add('hidden');
}
document.getElementById('addStageBtn')?.addEventListener('click', () => {
    document.getElementById('stageFormContainer').classList.remove('hidden');
    document.getElementById('stageFormTitle').innerText = "Add Stage Data";
    document.getElementById('stageForm').reset();
    document.getElementById('input_id').value = "";
});
document.getElementById('addStageBtn2')?.addEventListener('click', () => {
    document.getElementById('stageFormContainer').classList.remove('hidden');
    document.getElementById('stageFormTitle').innerText = "Add Stage Data";
    document.getElementById('stageForm').reset();
    document.getElementById('input_id').value = "";
});
document.getElementById('stageForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const formData = new FormData(this);
    const res = await fetch('api_save_paddy_stage.php', { method:'POST', body:formData });
    const data = await res.json();
    if (data.success) { alert('Stage data saved successfully!'); window.location.reload(); }
    else alert('Error: ' + data.message);
});
</script>
</body>
</html>
