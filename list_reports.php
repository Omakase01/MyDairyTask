<?php
require_once __DIR__ . '/auth.php';
$user = require_login();

$db = get_db();
$admin = is_admin($user);

$filterUser = $_GET['user_id'] ?? '';
$filterDate = $_GET['date'] ?? '';

$sql = 'SELECT dr.id, dr.report_date, u.display_name, u.department
        FROM daily_reports dr
        JOIN users u ON u.id = dr.user_id
        WHERE 1=1';
$params = [];

if (!$admin) {
    // Normal users can see only their own reports.
    $sql .= ' AND dr.user_id = ?';
    $params[] = (int)$user['id'];
} elseif ($filterUser !== '') {
    // Admin can filter by employee.
    $sql .= ' AND dr.user_id = ?';
    $params[] = (int)$filterUser;
}

if ($filterDate !== '') {
    $sql .= ' AND dr.report_date = ?';
    $params[] = $filterDate;
}

$sql .= ' ORDER BY dr.report_date DESC, u.display_name LIMIT 200';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// Only admin needs the employee filter.
$users = [];
if ($admin) {
    $users = $db->query(
        'SELECT id, display_name, department
         FROM users
         WHERE is_active = TRUE
         ORDER BY display_name'
    )->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $admin ? 'รายงานทั้งหมด' : 'รายงานของฉัน' ?> — บันทึกประจำวัน</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css?v=20260905">
</head>
<body class="form-page">
<div class="wrap">
  <header>
    <h1><?= $admin ? 'รายงานทั้งหมด' : 'รายงานของฉัน' ?></h1>
    <nav class="top-nav">
      <span class="who">
        <?= htmlspecialchars($user['display_name']) ?>
        <?= $admin ? ' (Admin)' : '' ?>
      </span>
      <a href="pages/insert_report.php">บันทึกวันนี้</a>
      <a href="logout.php">ออกจากระบบ</a>
    </nav>
  </header>

  <?php if ($admin): ?>
    <form class="card filter-bar" method="get">
      <div class="field">
        <label for="user_id">พนักงาน</label>
        <select id="user_id" name="user_id" onchange="this.form.submit()">
          <option value="">— ทุกคน —</option>
          <?php foreach ($users as $u): ?>
            <option value="<?= (int)$u['id'] ?>"
              <?= ((string)$u['id'] === (string)$filterUser) ? 'selected' : '' ?>>
              <?= htmlspecialchars($u['display_name']) ?>
              (<?= htmlspecialchars($u['department'] ?? '') ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="date">วันที่</label>
        <input type="date" id="date" name="date"
               value="<?= htmlspecialchars($filterDate) ?>"
               onchange="this.form.submit()">
      </div>
    </form>
  <?php else: ?>
    <div class="card report-user-notice">
      แสดงเฉพาะรายงานที่คุณเป็นผู้บันทึก
    </div>

    <form class="card filter-bar" method="get">
      <div class="field">
        <label for="date">วันที่</label>
        <input type="date" id="date" name="date"
               value="<?= htmlspecialchars($filterDate) ?>"
               onchange="this.form.submit()">
      </div>
    </form>
  <?php endif; ?>

  <div class="report-list">
    <?php if (!$reports): ?>
      <p class="hint">ไม่พบรายงานตามเงื่อนไขที่เลือก</p>
    <?php endif; ?>

    <?php foreach ($reports as $r): ?>
      <a class="report-list-row" href="view_report.php?id=<?= (int)$r['id'] ?>">
        <span class="report-list-date">
          <?= date('d/m/y', strtotime($r['report_date'])) ?>
        </span>
        <span class="report-list-name">
          <?= htmlspecialchars($r['display_name']) ?>
        </span>
        <span class="report-list-dept">
          <?= htmlspecialchars($r['department'] ?? '') ?>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>
