<?php
// Practical 10 - Secure Logout Handler
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_pdo.php';

start_session_safe();

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    // Invalidate remember token in DB
    try {
        $stmt = $pdo->prepare("UPDATE users SET remember_token_hash = NULL WHERE id = ?");
        $stmt->execute([$userId]);
    } catch (Exception $e) {}
}

// Clear remember cookie
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Purge session
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

header("Location: ../pages/login.html?msg=logged_out");
exit();
