<?php
// Check if landuse_id is set from PHP include or query param
$landuseId = '';
if (isset($landuse_id)) { // from include
    $landuseId = $landuse_id;
} elseif (isset($_GET['landuse_id'])) { // from fetch with query param
    $landuseId = trim($_GET['landuse_id'], '"');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Landuse Cascade Dropdown</title>
<script src="../../assets/js/dexie.min.js"></script>
<style>
  select { width: 100%; padding: 6px; margin-bottom: 10px; border: 1px solid #ccc; border-radius: 4px; }
  label { font-weight: bold; display: block; margin-bottom: 4px; }
  .container { max-width: 400px; margin: 20px auto; background: #fff; padding: 20px; border-radius: 6px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
</style>
</head>
<body>
<div class="container">
  <h3>Landuse Cascade Dropdown</h3>

  <label>Main Type</label>
  <select id="selMain"><option value="">-- Select Main Type --</option></select>

  <label>Sub Type</label>
  <select id="selSub" disabled><option value="">-- Select Sub Type --</option></select>

  <label>Type</label>
  <select id="selType" disabled><option value="">-- Select Type --</option></select>
</div>

<script>
const db = new Dexie("LanduseDB");
db.version(1).stores({
  landuse_types: "++id,[main_type+sub_type+type],main_type,sub_type,type,infor_main_type,infor_sub_type,infor_type"
});

const selMain = document.getElementById('selMain');
const selSub  = document.getElementById('selSub');
const selType = document.getElementById('selType');

// Use the PHP-provided landuseId
const landuseId = "<?= $landuseId ?>";

async function populateDropdowns() {
  const types = await db.landuse_types.toArray();

  // Create maps
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

  // Fill Main Type dropdown
  selMain.innerHTML = `<option value="">-- Select Main Type --</option>` +
    Object.entries(mainMap).map(([val,label]) => `<option value="${val}">${label}</option>`).join('');

  if (!landuseId) return;

  // Fetch selected landuse record
  const res = await fetch(`./api_get_landuse.php?landuse_id=${landuseId}`);
  const json = await res.json();
  let selected = {};
  if (json.success) selected = json.data;

  // Preselect values if available
  if (selected.main_type) {
    selMain.value = selected.main_type;
    fillSubOptions(selected.main_type, selected.sub_type, selected.type);
  }

  // Event listeners
  selMain.addEventListener('change', () => {
    const m = selMain.value;
    selSub.innerHTML = `<option value="">-- Select Sub Type --</option>`;
    selType.innerHTML = `<option value="">-- Select Type --</option>`;
    selSub.disabled = !m;
    selType.disabled = true;
    if (!m) return;
    fillSubOptions(m);
  });

  selSub.addEventListener('change', () => {
    const m = selMain.value;
    const s = selSub.value;
    selType.innerHTML = `<option value="">-- Select Type --</option>`;
    selType.disabled = !s;
    if (!m || !s) return;
    fillTypeOptions(m, s);
  });

  function fillSubOptions(mainVal, preselectSub, preselectType) {
    selSub.innerHTML = `<option value="">-- Select Sub Type --</option>`;
    Object.entries(subMap[mainVal]).forEach(([subVal, subLabel]) => {
      selSub.insertAdjacentHTML('beforeend', `<option value="${subVal}">${subLabel}</option>`);
    });
    selSub.disabled = false;
    if (preselectSub) {
      selSub.value = preselectSub;
      fillTypeOptions(mainVal, preselectSub, preselectType);
    }
  }

  function fillTypeOptions(mainVal, subVal, preselectType) {
    selType.innerHTML = `<option value="">-- Select Type --</option>`;
    Object.entries(typeMap[mainVal][subVal]).forEach(([typeVal, typeLabel]) => {
      selType.insertAdjacentHTML('beforeend', `<option value="${typeVal}">${typeLabel}</option>`);
    });
    selType.disabled = false;
    if (preselectType) selType.value = preselectType;
  }
}

// Initialize dropdowns
populateDropdowns();
</script>
</body>
</html>
