<?php
// ----------------------------------------------------------------
// Database configuration
// Update these 4 values to match your MySQL/MariaDB server.
// This matches the schema in daily_report_db.sql (phpMyAdmin dump).
// ----------------------------------------------------------------
define('DB_HOST', 'YOUR_DB_HOST');
define('DB_NAME', 'YOUR_DB_NAME');
define('DB_USER', 'YOUR_DB_USER');
define('DB_PASS', 'YOUR_DB_PASSWORD');
define('DB_CHARSET', 'utf8mb4');

// Section definitions used across the app.
const REPORT_SECTIONS = [
    'done_today' => ['label' => 'สิ่งที่ทำวันนี้', 'emoji' => '✅', 'color' => '#2F6F4E'],
    'follow_up' => ['label' => 'สิ่งที่ต้องติดตามต่อ', 'emoji' => '📌', 'color' => '#9C6B1F'],
    'problem' => ['label' => 'ปัญหาที่พบ / ติดปัญหา', 'emoji' => '🚨', 'color' => '#A23E24'],
    'plan_tomorrow' => ['label' => 'แผนงานพรุ่งนี้', 'emoji' => '🎯', 'color' => '#2C5A83'],
];
