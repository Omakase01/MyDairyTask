<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = new PDO(
        'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';sslmode=require',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $r = $pdo->query('SELECT current_database() AS db_name, NOW() AS server_time')->fetch();
    echo '<h1>✅ เชื่อมต่อ Supabase สำเร็จ</h1>';
    echo '<p>Database: ' . htmlspecialchars($r['db_name']) . '</p>';
    echo '<p>Server time: ' . htmlspecialchars($r['server_time']) . '</p>';
} catch (PDOException $e) {
    http_response_code(500);
    echo '<h1>❌ เชื่อมต่อ Supabase ไม่สำเร็จ</h1>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}
