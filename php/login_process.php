<?php
// Practical 10 - Secure Login Processor
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db_pdo.php';

start_session_safe();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../pages/login.html");
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']);

if (empty($username) || empty($password)) {
    header("Location: ../pages/login.html?error=" . urlencode("Username and password are required."));
    exit();
}

try {
    // Check credentials against username, email, or student enrollment_no
    $stmt = $pdo->prepare("
        SELECT u.*, s.enrollment_no, s.full_name, s.course, s.year 
        FROM users u 
        LEFT JOIN students s ON u.id = s.user_id 
        WHERE u.username = ? OR u.email = ? OR s.enrollment_no = ?
        LIMIT 1
    ");
    $stmt->execute([$username, $username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        if ((int)$user['is_active'] !== 1) {
            header("Location: ../pages/login.html?error=" . urlencode("Your account is deactivated. Contact administrator."));
            exit();
        }

        // Prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
        $_SESSION['enrollment_no'] = $user['enrollment_no'] ?? 'STU2026001';
        $_SESSION['course'] = $user['course'] ?? 'B.Tech Computer Engineering';
        $_SESSION['year'] = $user['year'] ?? 'Second Year';
        $_SESSION['last_activity'] = time();

        // Handle Remember-Me Token
        if ($remember) {
            $rawToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $rawToken);
            $updateTokenStmt = $pdo->prepare("UPDATE users SET remember_token_hash = ? WHERE id = ?");
            $updateTokenStmt->execute([$tokenHash, $user['id']]);

            // Set cookie for 14 days
            setcookie(
                'remember_token',
                $rawToken,
                [
                    'expires' => time() + (14 * 24 * 3600),
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
        }

        // Record Audit Log
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $logStmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action_name, entity_type, entity_id, details) 
            VALUES (?, 'USER_LOGIN', 'users', ?, ?)
        ");
        $logStmt->execute([$user['id'], $user['id'], "User {$user['username']} logged in successfully from $ip."]);

        // Role-Based Redirection
        if ($user['role'] === 'admin') {
            header("Location: ../pages/admin_dashboard.php");
        } else {
            header("Location: ../pages/dashboard.php");
        }
        exit();
    } else {
        header("Location: ../pages/login.html?error=" . urlencode("Invalid username/enrollment or password."));
        exit();
    }
} catch (PDOException $e) {
    header("Location: ../pages/login.html?error=" . urlencode("System authentication error."));
    exit();
}
