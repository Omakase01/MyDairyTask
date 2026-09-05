<?php
require_once __DIR__ . '/auth.php';
$user = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: insert_report.php');
    exit;
}

$reportDate = $_POST['report_date'] ?? '';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $reportDate)) {
    die('วันที่ไม่ถูกต้อง');
}

$itemsBySection = $_POST['items'] ?? [];
$validSections = array_keys(REPORT_SECTIONS);

$db = get_db();

try {
    $db->beginTransaction();

    // Upsert daily_reports (unique key on user_id + report_date)
    $stmt = $db->prepare(
        'INSERT INTO daily_reports (user_id, report_date)
         VALUES (?, ?)
         ON CONFLICT (user_id, report_date) DO UPDATE SET updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([$user['id'], $reportDate]);

    $stmt = $db->prepare(
        'SELECT id FROM daily_reports WHERE user_id = ? AND report_date = ?'
    );
    $stmt->execute([$user['id'], $reportDate]);
    $reportId = (int)$stmt->fetchColumn();

    // Replace all items for this report
    $del = $db->prepare('DELETE FROM report_items WHERE report_id = ?');
    $del->execute([$reportId]);

    $insert = $db->prepare(
        'INSERT INTO report_items (report_id, section, tag_id, detail, sort_order)
         VALUES (?, ?, ?, ?, ?)'
    );

    foreach ($validSections as $section) {
        $rows = $itemsBySection[$section] ?? [];
        $order = 0;
        foreach ($rows as $row) {
            $detail = trim($row['detail'] ?? '');
            if ($detail === '') {
                continue; // skip blank rows
            }
            $tagId = ($row['tag_id'] ?? '') !== '' ? (int)$row['tag_id'] : null;
            $insert->execute([$reportId, $section, $tagId, $detail, $order]);
            $order++;
        }
    }

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    die('เกิดข้อผิดพลาดในการบันทึก: ' . htmlspecialchars($e->getMessage()));
}

header('Location: pages/insert_report.php?date=' . urlencode($reportDate) . '&saved=1&id=' . $reportId);
exit;
