<?php
require "../../db.php";

if (!isset($_GET['landuse_id']) || empty($_GET['landuse_id'])) {
    echo "No landuse_id provided.";
    exit;
}

$landuse_id = $_GET['landuse_id'];

// Fetch crop_id for this landuse_id
$stmt = $pdo->prepare("SELECT crop_id FROM landuse.coconut WHERE landuse_id = :landuse_id LIMIT 1");
$stmt->execute(['landuse_id' => $landuse_id]);
$crop = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$crop) {
    echo "No coconut crop found for this landuse.";
    exit;
}
$crop_id = $crop['crop_id'];

// Fetch existing yield records
$stmt = $pdo->prepare("SELECT * FROM landuse.coconut_yield WHERE crop_id = :crop_id ORDER BY last_harvest_date DESC");
$stmt->execute(['crop_id' => $crop_id]);
$yields = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get enums
$harvesting_methods = $pdo->query("SELECT unnest(enum_range(NULL::landuse.harvesting_method_enum))")->fetchAll(PDO::FETCH_COLUMN);

// Fetch stage data for latest yield (if exists)
$stages = [];
$latest_yield_id = null;
if (!empty($yields)) {
    $latest_yield_id = $yields[0]['yield_id'];
    $stmt = $pdo->prepare("SELECT * FROM landuse.coconut_stages WHERE crop_id = :cid ORDER BY applied_date ASC");
    $stmt->execute(['cid' => $crop_id]);
    $stages = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>🥥 Coconut Yield</title>
    <link rel="icon" type="image/x-icon" href="../../assets/img/gis_nwp.ico">
    <link rel="stylesheet" href="../../assets/css/tailwind.min.css">
</head>
<body class="bg-gray-100 p-6">
<div class="max-w-7xl mx-auto bg-white rounded-xl shadow p-6">
    <h2 class="text-2xl font-bold mb-4">🥥 Coconut Yield Records</h2>

    <div class="mb-4">
        <a href="../collect_landuse.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 inline-block">
            &larr; Back to Land Use
        </a>
    </div>

    <?php if (empty($yields)): ?>
        <p class="text-gray-600">No coconut yield records found.</p>
        <button id="addYieldBtn" class="mt-4 px-4 py-2 bg-green-600 text-white rounded-lg shadow hover:bg-green-700">Add Yield Record</button>
    <?php else: ?>
        <button id="addYieldBtn2" class="mb-4 px-4 py-2 bg-green-600 text-white rounded-lg shadow hover:bg-green-700">Add New Yield</button>
        <div class="overflow-x-auto">
            <table class="min-w-full border border-gray-300 text-sm">
                <thead class="bg-gray-200">
                <tr>
                    <th class="px-2 py-1 border">ID</th>
                    <th class="px-2 py-1 border">Harvest Method</th>
                    <th class="px-2 py-1 border">Last Harvest</th>
                    <th class="px-2 py-1 border">Qty</th>
                    <th class="px-2 py-1 border">Next Harvest</th>
                    <th class="px-2 py-1 border">Expected Yield</th>
                    <th class="px-2 py-1 border">Remarks</th>
                    <th class="px-2 py-1 border">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($yields as $y): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-2 py-1 border"><?= $y['yield_id'] ?></td>
                        <td class="px-2 py-1 border"><?= htmlspecialchars($y['harvesting_method']) ?></td>
                        <td class="px-2 py-1 border"><?= htmlspecialchars($y['last_harvest_date']) ?></td>
                        <td class="px-2 py-1 border"><?= htmlspecialchars($y['last_harvest_qty']) ?></td>
                        <td class="px-2 py-1 border"><?= htmlspecialchars($y['next_harvest_date']) ?></td>
                        <td class="px-2 py-1 border"><?= htmlspecialchars($y['expected_yield']) ?></td>
                        <td class="px-2 py-1 border"><?= htmlspecialchars($y['remarks']) ?></td>
                        <td class="px-2 py-1 border">
                            <button class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600" onclick='editYield(<?= json_encode($y) ?>)'>Edit</button>
                            <button class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600" onclick="deleteYield(<?= $y['yield_id'] ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Yield Form -->
    <div id="yieldFormContainer" class="hidden mt-6 p-4 bg-gray-50 border rounded-lg">
        <h3 id="yieldFormTitle" class="text-xl font-semibold mb-4">Add Yield Record</h3>
        <form id="yieldForm" class="space-y-3">
            <input type="hidden" name="yield_id" id="yield_id">
            <input type="hidden" name="crop_id" value="<?= $crop_id ?>">

            <div>
                <label class="block text-sm">Harvesting Method:</label>
                <select name="harvesting_method" class="w-full border rounded p-2">
                    <option value="">-- Select --</option>
                    <?php foreach ($harvesting_methods as $hm): ?>
                        <option value="<?= $hm ?>"><?= $hm ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm">Last Harvest Date:</label>
                    <input type="date" name="last_harvest_date" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-sm">Last Harvest Qty:</label>
                    <input type="number" name="last_harvest_qty" min="0" class="w-full border rounded p-2">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm">Next Harvest Date:</label>
                    <input type="date" name="next_harvest_date" class="w-full border rounded p-2">
                </div>
                <div>
                    <label class="block text-sm">Expected Yield:</label>
                    <input type="number" name="expected_yield" min="0" class="w-full border rounded p-2">
                </div>
            </div>

            <div>
                <label class="block text-sm">Remarks:</label>
                <textarea name="remarks" class="w-full border rounded p-2"></textarea>
            </div>

            <div class="flex gap-4">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Save</button>
                <button type="button" onclick="cancelYieldForm()" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Stage Section -->
    <h2 class="text-xl font-bold mt-8 mb-4">Stage Data (Coconut Inputs)</h2>
    <?php if (empty($stages)): ?>
        <p class="text-gray-600">No stage inputs recorded for this coconut crop.</p>
        <?php if ($latest_yield_id): ?>
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
                    <th class="px-2 py-1 border">Quantity (Kg/ha or L/Ha)</th>
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
                            <button class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600" onclick="deleteStage(<?= $s['stage_id'] ?>)">Delete</button>
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
            <input type="hidden" name="stage_id" id="stage_id">
            <input type="hidden" name="crop_id" value="<?= $crop_id ?>">

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
                <input type="text" name="stage" placeholder="e.g. Flowering" class="w-full border rounded p-2">
            </div>
            <div>
                <label class="block text-sm">Description:</label>
                <textarea name="description" class="w-full border rounded p-2"></textarea>
            </div>
            <div>
                <label class="block text-sm">Quantity (Kg/ha or L/Ha):</label>
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
// Yield JS
function editYield(data) {
    document.getElementById('yieldFormContainer').classList.remove('hidden');
    document.getElementById('yieldFormTitle').innerText = "Edit Yield Record";
    for (const key in data) {
        if (document.querySelector(`[name=${key}]`)) {
            document.querySelector(`[name=${key}]`).value = data[key];
        }
    }
}

function deleteYield(id) {
    if (!confirm("Are you sure to delete this yield record?")) return;
    
    fetch('api_delete_coconut_yield.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'yield_id=' + encodeURIComponent(id)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else alert("Error: " + data.message);
    });
}

function cancelYieldForm() {
    document.getElementById('yieldFormContainer').classList.add('hidden');
}
document.getElementById('addYieldBtn')?.addEventListener('click', () => {
    document.getElementById('yieldFormContainer').classList.remove('hidden');
    document.getElementById('yieldFormTitle').innerText = "Add Yield Record";
    document.getElementById('yieldForm').reset();
    document.getElementById('yield_id').value = "";
});
document.getElementById('addYieldBtn2')?.addEventListener('click', () => {
    document.getElementById('yieldFormContainer').classList.remove('hidden');
    document.getElementById('yieldFormTitle').innerText = "Add Yield Record";
    document.getElementById('yieldForm').reset();
    document.getElementById('yield_id').value = "";
});
document.getElementById('yieldForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const formData = new FormData(this);
    const res = await fetch('api_save_coconut_yield.php', { method:'POST', body:formData });
    const data = await res.json();
    if (data.success) { alert('Yield saved successfully!'); window.location.reload(); }
    else alert('Error: ' + data.message);
});

// Stage JS
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

    fetch('api_delete_coconut_stage.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ stage_id: id })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) location.reload();
        else alert("Error: " + (data.error || "Unknown error"));
    });
}

function cancelStageForm() {
    document.getElementById('stageFormContainer').classList.add('hidden');
}
document.getElementById('addStageBtn')?.addEventListener('click', () => {
    document.getElementById('stageFormContainer').classList.remove('hidden');
    document.getElementById('stageFormTitle').innerText = "Add Stage Data";
    document.getElementById('stageForm').reset();
    document.getElementById('stage_id').value = "";
});
document.getElementById('addStageBtn2')?.addEventListener('click', () => {
    document.getElementById('stageFormContainer').classList.remove('hidden');
    document.getElementById('stageFormTitle').innerText = "Add Stage Data";
    document.getElementById('stageForm').reset();
    document.getElementById('stage_id').value = "";
});

document.getElementById('stageForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const formData = new FormData(this);
    const obj = Object.fromEntries(formData.entries()); // Convert FormData to JS object
    const res = await fetch('api_save_coconut_stage.php', {
        method:'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(obj)
    });
    const data = await res.json();
    if (data.success) { 
        alert('Stage data saved successfully!'); 
        window.location.reload(); 
    } else {
        alert('Error: ' + (data.error || 'Unknown error'));
    }
});

</script>
</body>
</html>
