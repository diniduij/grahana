<?php
require "../../db.php";

if (!isset($_GET['crop_id'])) {
    echo "<p class='text-red-600'>No coconut crop selected.</p>";
    exit;
}

$crop_id = $_GET['crop_id'];

// Fetch stage records
$stmt = $pdo->prepare("SELECT * FROM landuse.coconut_stages WHERE crop_id = :cid ORDER BY applied_date ASC");
$stmt->execute(["cid" => $crop_id]);
$stages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="mt-6">
    <h3 class="text-lg font-semibold mb-2">Stage Data (Coconut Inputs)</h3>

    <?php if (empty($stages)): ?>
        <p class="text-gray-600">No stage inputs recorded.</p>
        <button id="addStageBtn" class="mt-2 px-4 py-2 bg-green-600 text-white rounded shadow hover:bg-green-700">
            Add Stage Information
        </button>
    <?php else: ?>
        <button id="addStageBtn2" class="mb-2 px-4 py-2 bg-green-600 text-white rounded shadow hover:bg-green-700">
            Add New Stage
        </button>
        <table class="w-full border border-gray-300 text-sm">
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
                            <button class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600"
                                onclick='editStage(<?= json_encode($s) ?>)'>Edit</button>
                            <button class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600"
                                onclick="deleteStage(<?= $s['stage_id'] ?>)">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
