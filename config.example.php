<?php
// Render / production configuration.
// Values are read from Environment Variables, so no database password is stored in GitHub.
define('DB_HOST', getenv('DB_HOST') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'postgres');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');

const REPORT_SECTIONS = [
    'done_today' => ['label' => 'สิ่งที่ทำวันนี้', 'emoji' => '✅', 'color' => '#2F6F4E'],
    'follow_up' => ['label' => 'สิ่งที่ต้องติดตามต่อ', 'emoji' => '📌', 'color' => '#9C6B1F'],
    'problem' => ['label' => 'ปัญหาที่พบ / ติดปัญหา', 'emoji' => '🚨', 'color' => '#A23E24'],
    'plan_tomorrow' => ['label' => 'แผนงานพรุ่งนี้', 'emoji' => '🎯', 'color' => '#2C5A83'],
];
