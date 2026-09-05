<?php
require_once __DIR__ . '/auth.php';
$user = require_login();

$db = get_db();
$reportId = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare(
    'SELECT dr.*, u.display_name, u.department
     FROM daily_reports dr
     JOIN users u ON u.id = dr.user_id
     WHERE dr.id = ?'
);
$stmt->execute([$reportId]);
$report = $stmt->fetch();

if (!$report) {
    http_response_code(404);
    die('ไม่พบรายงานนี้');
}

// Security: normal users may view only their own reports.
// Admins may view every report.
if (!is_admin($user) && (int)$report['user_id'] !== (int)$user['id']) {
    http_response_code(403);
    die('คุณไม่มีสิทธิ์ดูรายงานของผู้ใช้อื่น');
}

$stmt = $db->prepare(
    'SELECT ri.section, ri.detail, t.code AS tag_code, t.color AS tag_color
     FROM report_items ri
     LEFT JOIN tags t ON t.id = ri.tag_id
     WHERE ri.report_id = ?
     ORDER BY CASE ri.section WHEN \'done_today\' THEN 1 WHEN \'follow_up\' THEN 2 WHEN \'problem\' THEN 3 WHEN \'plan_tomorrow\' THEN 4 ELSE 5 END, ri.sort_order, ri.id'
);
$stmt->execute([$reportId]);

$items = array_fill_keys(array_keys(REPORT_SECTIONS), []);
foreach ($stmt->fetchAll() as $row) {
    $items[$row['section']][] = $row;
}

$displayDate = date('d/m/y', strtotime($report['report_date']));
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>อัปเดตประจำวัน — <?= htmlspecialchars($displayDate) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=20260905">
</head>
<body class="report-page">
<div class="report-page-wrap">
  <div class="report-toolbar">
    <a href="list_reports.php">&larr; รายงานทั้งหมด</a>
    <a href="pages/insert_report.php?date=<?= htmlspecialchars($report['report_date']) ?>">แก้ไขรายงานนี้</a>
  </div>

  <div class="discord-card">
    <div class="discord-head">
      <div class="discord-avatar">📋</div>
      <div>
        <div class="discord-name"><?= htmlspecialchars($report['display_name']) ?>
          <span class="discord-time">รายงานวันที่ <?= htmlspecialchars($displayDate) ?></span>
        </div>
        <div class="discord-title">📅 อัปเดตประจำวัน — <?= htmlspecialchars($displayDate) ?></div>
      </div>
    </div>

    <?php foreach (REPORT_SECTIONS as $key => $sec): ?>
      <div class="discord-section">
        <div class="discord-section-title"><?= $sec['emoji'] ?> <?= htmlspecialchars($sec['label']) ?></div>
        <?php if (empty($items[$key])): ?>
          <div class="discord-empty">-</div>
        <?php else: ?>
          <ul class="discord-list">
            <?php foreach ($items[$key] as $it): ?>
              <li>
                <?php if ($it['tag_code']): ?>
                  <span class="discord-tag" style="color: <?= htmlspecialchars($it['tag_color']) ?>">[<?= htmlspecialchars($it['tag_code']) ?>]</span>
                <?php endif; ?>
                <?= nl2br(htmlspecialchars($it['detail'])) ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
