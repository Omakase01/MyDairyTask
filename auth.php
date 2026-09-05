<?php
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Returns the logged-in user's row from $_SESSION, or null.
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Redirects to login.php if nobody is logged in.
 */
function require_login(): array
{
    $user = current_user();
    if (!$user) {
        header('Location: pages/login.php');
        exit;
    }
    return $user;
}

/**
 * The existing `users` table stores plain values in `password_hash`
 * for some rows (looks like ID-card numbers) rather than real
 * password_hash() output. This checks either format so existing
 * accounts keep working, while still supporting real bcrypt hashes
 * going forward. New passwords should be stored with password_hash().
 */
function check_password(string $input, string $stored): bool
{
    if (password_get_info($stored)['algo'] !== null && password_verify($input, $stored)) {
        return true;
    }
    return hash_equals($stored, $input);
}

/**
 * Attempts to log a user in. Returns the user row on success, null on failure.
 */
function attempt_login(string $username, string $password): ?array
{
    $stmt = get_db()->prepare(
        'SELECT * FROM users WHERE username = ? AND is_active = TRUE LIMIT 1'
    );
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && check_password($password, $user['password_hash'])) {
        unset($user['password_hash']);
        return $user;
    }
    return null;
}

/**
 * Returns true only for users whose users.role is "admin".
 */
function is_admin(array $user): bool
{
    return strtolower((string)($user['role'] ?? '')) === 'admin';
}

/**
 * Stops access to admin-only pages/actions.
 */
function require_admin(array $user): void
{
    if (!is_admin($user)) {
        http_response_code(403);
        die('ไม่มีสิทธิ์เข้าถึงหน้านี้');
    }
}
