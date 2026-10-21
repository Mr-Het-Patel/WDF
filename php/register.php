<?php
// Practical 9 - Secure Student Registration Processor with MySQLi Transaction
require_once __DIR__ . '/db_mysqli.php';

$errors = [];
$success = false;
$newEnrollment = '';
$newUsername = '';

if (isset($_GET['preview']) && $_GET['preview'] === 'success') {
    $success = true;
    $newEnrollment = 'STU2026022';
    $newUsername = 'candidate99';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize and retrieve POST parameters
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $mobile   = trim($_POST['mobile'] ?? '');
    $gender   = trim($_POST['gender'] ?? '');
    $course   = trim($_POST['course'] ?? '');
    $year     = trim($_POST['year'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';
    $terms    = isset($_POST['terms']);

    // 2. Consistent Server-Side Validation (matching Practical 5 Regex)
    if (!preg_match('/^[A-Za-z ]{2,50}$/', $fullName)) {
        $errors[] = "Full Name must be 2 to 50 alphabetic characters.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid university email address is required.";
    }

    if (!preg_match('/^[6-9]\d{9}$/', $mobile)) {
        $errors[] = "Mobile number must be a valid 10-digit number starting with 6, 7, 8, or 9.";
    }

    if (empty($gender)) {
        $errors[] = "Please select gender.";
    }

    if (empty($course)) {
        $errors[] = "Please select an academic course.";
    }

    if (empty($year)) {
        $errors[] = "Please select an academic year.";
    }

    if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d).{6,}$/', $password)) {
        $errors[] = "Password must have at least 6 characters including letters and digits.";
    }

    if ($password !== $confirmPass) {
        $errors[] = "Password confirmation does not match.";
    }

    if (!$terms) {
        $errors[] = "You must accept the terms and conditions.";
    }

    // 3. Duplicate checks & Database Transaction
    if (empty($errors)) {
        // Generate automatic username & enrollment ID
        // Check current max student ID to form STU20260XX
        $countResult = $mysqli->query("SELECT MAX(id) AS max_id FROM students");
        $maxRow = $countResult->fetch_assoc();
        $nextNum = ($maxRow['max_id'] ?? 21) + 1;
        $newEnrollment = sprintf('STU2026%03d', $nextNum);

        // Derived username: e.g. candidate22 or derived from email
        $emailPrefix = explode('@', $email)[0];
        $newUsername = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($emailPrefix));
        if (strlen($newUsername) < 3) {
            $newUsername = 'student' . $nextNum;
        }

        // Check duplicate email or username in users or students
        $checkStmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
        $checkStmt->bind_param("ss", $email, $newUsername);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows > 0) {
            $errors[] = "An account with email '$email' or username '$newUsername' already exists.";
        }
        $checkStmt->close();

        if (empty($errors)) {
            // Cryptographic password hashing
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Execute atomic transaction
            $mysqli->begin_transaction();
            try {
                // Insert into users table
                $role = 'student';
                $userStmt = $mysqli->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
                $userStmt->bind_param("ssss", $newUsername, $email, $passwordHash, $role);
                $userStmt->execute();
                $newUserId = $mysqli->insert_id;
                $userStmt->close();

                // Insert into students table
                $studentStmt = $mysqli->prepare("
                    INSERT INTO students (user_id, enrollment_no, full_name, email, mobile, course, year, gender) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $studentStmt->bind_param("isssssss", $newUserId, $newEnrollment, $fullName, $email, $mobile, $course, $year, $gender);
                $studentStmt->execute();
                $newStudentId = $mysqli->insert_id;
                $studentStmt->close();

                // Insert into audit logs
                $actionName = 'CREATE_STUDENT';
                $entityType = 'students';
                $details = "Student $fullName ($newEnrollment) registered via self-service portal.";
                $auditStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_name, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?)");
                $auditStmt->bind_param("issis", $newUserId, $actionName, $entityType, $newStudentId, $details);
                $auditStmt->execute();
                $auditStmt->close();

                // Append record to CSV storage for dual-tier persistence (Practical 7 & 9)
                $csvStorageDir = __DIR__ . '/../storage';
                if (!is_dir($csvStorageDir)) {
                    mkdir($csvStorageDir, 0777, true);
                }
                $regCsv = $csvStorageDir . '/registrations.csv';
                $isNewCsv = !file_exists($regCsv) || filesize($regCsv) === 0;
                $fp = fopen($regCsv, 'a');
                if ($fp) {
                    if ($isNewCsv) {
                        fputcsv($fp, ['Timestamp', 'Enrollment', 'Username', 'Full Name', 'Email', 'Mobile', 'Course', 'Year', 'Gender'], ',', '"', '\\');
                    }
                    fputcsv($fp, [date('Y-m-d H:i:s'), $newEnrollment, $newUsername, $fullName, $email, $mobile, $course, $year, $gender], ',', '"', '\\');
                    fclose($fp);
                }

                $mysqli->commit();
                $success = true;
            } catch (Exception $e) {
                $mysqli->rollback();
                $errors[] = "Registration transaction failed: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Status | StudentHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/common.css">
    <style>
        body { background-color: #f8fafc; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; min-height: 100vh; display: flex; flex-direction: column; }
        .result-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .result-card { width: 100%; max-width: 620px; border-radius: 8px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.06); background: #fff; }
        .card-header-bar { background: #fff; padding: 24px 24px 12px; border-bottom: none; text-align: center; }
        .card-header-bar h4 { font-weight: 600; color: #0d6efd; font-size: 1.35rem; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary-dark shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center gap-2" href="../pages/home.html">
                <span class="badge bg-white text-primary fw-bold px-2 py-1">CHARUSAT</span>
                <span class="fw-bold tracking-wide">CHARUSAT STUDENT HUB</span>
            </a>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-outline-light btn-sm" id="themeToggleBtn">Dark</button>
            </div>
        </div>
    </nav>

    <div class="result-container">
        <div class="card result-card">
            <div class="card-header-bar">
                <h4>Registration Status</h4>
            </div>
            <div class="card-body p-4 text-center">
                <?php if ($success): ?>
                    <div class="alert alert-success py-2 px-3 mb-4 fw-medium" role="alert" style="background-color: #d1e7dd; border-color: #badbcc; color: #0f5132; font-size: 0.95rem;">
                        Student registration completed successfully. Your enrollment ID is <strong><?= htmlspecialchars($newEnrollment) ?></strong> and your login username is <strong><?= htmlspecialchars($newUsername) ?></strong>.
                    </div>
                    <p class="text-muted small mb-4">Your student credentials have been securely stored in the relational database. You can now use your credentials to sign in to the StudentHub portal.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="../pages/register.html" class="btn btn-outline-primary btn-sm px-3">Back to Registration</a>
                        <a href="../pages/login.html" class="btn btn-primary btn-sm px-3">Go to Login</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger py-2 px-3 mb-4 text-start" role="alert">
                        <strong>Registration Failed:</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="../pages/register.html" class="btn btn-outline-primary btn-sm px-3">Back to Registration</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="text-center py-3 text-muted small mt-auto border-top bg-white">
        © 2026 CHARUSAT Student Hub - Charotar University of Science and Technology
    </footer>
    <script src="../js/common.js"></script>
</body>
</html>
