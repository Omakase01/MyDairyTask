<?php
require_once __DIR__ . '/../auth.php';
$user = require_login();

$reportDate = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
    $reportDate = date('Y-m-d');
}

$db = get_db();

// Tags available for the item dropdowns
$tags = $db->query('SELECT id, code, label, color FROM tags ORDER BY id')->fetchAll();

// Load an existing report for this user + date, if one exists, so the
// form can be used for editing (daily_reports has a unique user_id+date key).
$stmt = $db->prepare('SELECT id FROM daily_reports WHERE user_id = ? AND report_date = ?');
$stmt->execute([$user['id'], $reportDate]);
$existingReportId = $stmt->fetchColumn();

// items[section] = [ ['tag_id' => ..., 'detail' => ...], ... ]
$items = array_fill_keys(array_keys(REPORT_SECTIONS), []);

if ($existingReportId) {
    $stmt = $db->prepare(
        'SELECT section, tag_id, detail FROM report_items
         WHERE report_id = ? ORDER BY section, sort_order, id'
    );
    $stmt->execute([$existingReportId]);
    foreach ($stmt->fetchAll() as $row) {
        $items[$row['section']][] = $row;
    }
}

$flash = $_GET['saved'] ?? null;
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>บันทึกประจำวัน</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="form-page">
<div class="wrap">
  <header>
    <h1>บันทึกประจำวัน</h1>
    <nav class="top-nav">
      <span class="who"><?= htmlspecialchars($user['display_name']) ?></span>
      <a href="../list_reports.php">ดูรายงานทั้งหมด</a>
      <a href="../logout.php">ออกจากระบบ</a>
    </nav>
  </header>

  <?php if ($flash === '1'): ?>
    <div class="alert-success">บันทึกข้อมูลเรียบร้อยแล้ว — <a href="../view_report.php?id=<?= (int)($_GET['id'] ?? 0) ?>">ดูรายงานนี้</a></div>
  <?php endif; ?>

  <form class="card" method="post" action="../save_report.php" id="reportForm">
    <input type="hidden" name="report_id" value="<?= (int)($existingReportId ?: 0) ?>">

    <div class="field">
      <label for="dateInput">วันที่บันทึก</label>
      <input type="date" id="dateInput" name="report_date" value="<?= htmlspecialchars($reportDate) ?>" onchange="location.href='insert_report.php?date='+this.value">
      <?php if ($existingReportId): ?>
        <p class="hint">มีรายงานของวันนี้อยู่แล้ว — การบันทึกจะเป็นการแก้ไขรายงานเดิม</p>
      <?php endif; ?>
    </div>

    <?php foreach (REPORT_SECTIONS as $key => $sec): ?>
      <div class="field section-block <?= $key ?>">
        <label><?= $sec['emoji'] ?> <?= htmlspecialchars($sec['label']) ?></label>
        <div class="rows" data-section="<?= $key ?>">
          <?php if (empty($items[$key])): ?>
            <?php /* start with one blank row */ ?>
            <div class="row" data-row>
              <?= render_tag_select($key, 0, $tags, null) ?>
              <input type="text" name="items[<?= $key ?>][0][detail]" placeholder="รายละเอียด...">
              <button type="button" class="remove-row" onclick="removeRow(this)">ลบ</button>
            </div>
          <?php else: ?>
            <?php foreach ($items[$key] as $i => $it): ?>
              <div class="row" data-row>
                <?= render_tag_select($key, $i, $tags, $it['tag_id']) ?>
                <input type="text" name="items[<?= $key ?>][<?= $i ?>][detail]" value="<?= htmlspecialchars($it['detail']) ?>" placeholder="รายละเอียด...">
                <button type="button" class="remove-row" onclick="removeRow(this)">ลบ</button>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
        <button type="button" class="add-row" onclick="addRow('<?= $key ?>')">+ เพิ่มรายการ</button>
      </div>
    <?php endforeach; ?>

    <div class="actions">
      <button type="submit" id="saveBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
          <polyline points="17 21 17 13 7 13 7 21"/>
          <polyline points="7 3 7 8 15 8"/>
        </svg>
        <?= $existingReportId ? 'บันทึกการแก้ไข' : 'บันทึกข้อมูล' ?>
      </button>
    </div>
  </form>
</div>

<template id="tagOptionsTemplate">
  <?php foreach ($tags as $t): ?>
    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['code']) ?> — <?= htmlspecialchars($t['label']) ?></option>
  <?php endforeach; ?>
</template>

<script>
let rowCounters = <?= json_encode(array_map('count', $items)) ?>;
Object.keys(rowCounters).forEach(k => { if (rowCounters[k] === 0) rowCounters[k] = 1; });

function addRow(section){
  const container = document.querySelector(`.rows[data-section="${section}"]`);
  const idx = rowCounters[section]++;
  const optionsHtml = document.getElementById('tagOptionsTemplate').innerHTML;

  const row = document.createElement('div');
  row.className = 'row';
  row.setAttribute('data-row', '');
  row.innerHTML = `
    <select name="items[${section}][${idx}][tag_id]">
      <option value="">— ไม่ระบุแท็ก —</option>
      ${optionsHtml}
    </select>
    <input type="text" name="items[${section}][${idx}][detail]" placeholder="รายละเอียด...">
    <button type="button" class="remove-row" onclick="removeRow(this)">ลบ</button>
  `;
  container.appendChild(row);
}

function removeRow(btn){
  const row = btn.closest('[data-row]');
  const container = row.parentElement;
  if (container.querySelectorAll('[data-row]').length > 1) {
    row.remove();
  } else {
    row.querySelector('input[type="text"]').value = '';
    row.querySelector('select').value = '';
  }
}
</script>
</body>
</html>
<?php
/**
 * Renders a <select> for tag_id with the given selection.
 */
function render_tag_select(string $section, int $index, array $tags, ?int $selectedId): string
{
    $html = "<select name=\"items[{$section}][{$index}][tag_id]\">";
    $html .= '<option value="">— ไม่ระบุแท็ก —</option>';
    foreach ($tags as $t) {
        $sel = ((int)$t['id'] === (int)$selectedId) ? ' selected' : '';
        $html .= '<option value="' . (int)$t['id'] . '"' . $sel . '>'
               . htmlspecialchars($t['code']) . ' — ' . htmlspecialchars($t['label'])
               . '</option>';
    }
    $html .= '</select>';
    return $html;
}
