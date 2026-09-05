<?php
require_once __DIR__ . '/../auth.php';

if (current_user()) {
    header('Location: insert_report.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
    } else {
        $user = attempt_login($username, $password);

        if ($user) {
            $_SESSION['user'] = $user;
            header('Location: insert_report.php');
            exit;
        }

        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
    }
}
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เข้าสู่ระบบ — RSM Group</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@400;500;600;700&family=Kanit:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
<div id="loginScreen">
    <div class="login-card">
      <div class="diary-icon-badge">
        <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7C10.3 5.3 7.6 4.8 4.5 4.8V17.6C7.6 17.6 10.3 18.1 12 19.8C13.7 18.1 16.4 17.6 19.5 17.6V4.8C16.4 4.8 13.7 5.3 12 7Z"/><path d="M12 7V19.8"/></svg>
      </div>
      <h1>My Diary Task</h1>
      <p class="login-sub">บันทึกงานประจำวันของคุณ</p>

      <?php if ($error !== ''): ?>
      <div id="loginError" class="login-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form id="authForm" method="POST" action="">
  <div class="field">
    <label for="username">ชื่อผู้ใช้</label>

    <div class="input-icon-wrap">
      <span class="input-icon">
        <svg width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="8" r="4"/>
          <path d="M4 21c0-4 4-6 8-6s8 2 8 6"/>
        </svg>
      </span>

      <input
        id="username"
        name="username"
        type="text"
        placeholder="ระบุชื่อผู้ใช้"
        autocomplete="username"
        required
      >
    </div>
  </div>

  <div class="field">
    <label for="password">รหัสผ่าน</label>

    <div class="input-icon-wrap has-toggle">
      <span class="input-icon">
        <svg width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <rect x="5" y="11" width="14" height="9" rx="2"/>
          <path d="M8 11V7a4 4 0 0 1 8 0v4"/>
        </svg>
      </span>

      <input
        id="password"
        name="password"
        type="password"
        placeholder="ระบุรหัสผ่าน"
        autocomplete="current-password"
        required
      >

      <button
        type="button"
        class="input-eye-btn"
        id="togglePasswordBtn"
        aria-label="แสดงรหัสผ่าน"
      >
        <svg width="16" height="16" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/>
          <circle cx="12" cy="12" r="3"/>
        </svg>
      </button>
    </div>
  </div>

  <button type="submit" class="btn-primary" id="authSubmit">
    <svg width="16" height="16" viewBox="0 0 24 24"
         fill="none" stroke="currentColor" stroke-width="2"
         stroke-linecap="round" stroke-linejoin="round">
      <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/>
      <polyline points="10 17 15 12 10 7"/>
      <line x1="15" y1="12" x2="3" y2="12"/>
    </svg>
    เข้าสู่ระบบ
  </button>
</form>

<script>
document.getElementById('togglePasswordBtn').addEventListener('click', function () {
    const input = document.getElementById('password');

    if (input.type === 'password') {
        input.type = 'text';
        this.setAttribute('aria-label', 'ซ่อนรหัสผ่าน');
    } else {
        input.type = 'password';
        this.setAttribute('aria-label', 'แสดงรหัสผ่าน');
    }
});
</script>
</body>
</html>
