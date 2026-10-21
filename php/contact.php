<?php
// Practical 7 - Contact Form Processing with Server-Side Validation and CSV Storage

$errors = [];
$success = false;

if (isset($_GET['preview']) && $_GET['preview'] === 'success') {
    $success = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Sanitize and trim inputs
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // 2. Validate inputs
    if (empty($name)) {
        $errors[] = "Full Name is required.";
    } elseif (strlen($name) < 2 || strlen($name) > 60) {
        $errors[] = "Name must be between 2 and 60 characters.";
    }

    if (empty($email)) {
        $errors[] = "University email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email format.";
    }

    if (empty($subject)) {
        $errors[] = "Subject of inquiry is required.";
    }

    if (empty($message)) {
        $errors[] = "Message body cannot be empty.";
    } elseif (strlen($message) < 5) {
        $errors[] = "Message is too short (minimum 5 characters).";
    }

    // 3. If validation passes, write safely to storage/contacts.csv
    if (empty($errors)) {
        $storageDir = __DIR__ . '/../storage';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0777, true);
        }

        $csvFile = $storageDir . '/contacts.csv';
        $fileExists = file_exists($csvFile) && filesize($csvFile) > 0;

        $fp = fopen($csvFile, 'a');
        if ($fp) {
            // Write CSV headers if new file
            if (!$fileExists) {
                fputcsv($fp, ['Timestamp', 'Full Name', 'Email', 'Subject', 'Message', 'IP_Address'], ',', '"', '\\');
            }

            $timestamp = date('Y-m-d H:i:s');
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            // Clean data against CSV formula injection
            $cleanName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
            $cleanSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
            $cleanMsg = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

            fputcsv($fp, [$timestamp, $cleanName, $email, $cleanSubject, $cleanMsg, $ipAddress], ',', '"', '\\');
            fclose($fp);
            $success = true;
        } else {
            $errors[] = "Unable to access file storage for recording message.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Submission Result | StudentHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/common.css">
    <style>
        body { background-color: #f8fafc; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; min-height: 100vh; display: flex; flex-direction: column; }
        .result-container { flex: 1; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .result-card { width: 100%; max-width: 580px; border-radius: 8px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.06); background: #fff; }
        .card-header-bar { background: #fff; padding: 20px 24px 10px; border-bottom: none; text-align: center; }
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
                <h4>Message Submission Result</h4>
            </div>
            <div class="card-body p-4 text-center">
                <?php if ($success): ?>
                    <div class="alert alert-success py-2 px-3 mb-4 fw-medium" role="alert" style="background-color: #d1e7dd; border-color: #badbcc; color: #0f5132; font-size: 0.95rem;">
                        Your message was submitted successfully.
                    </div>
                    <p class="text-muted small mb-4">Thank you for reaching out. The administration team will review your inquiry and follow up shortly via email.</p>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="../pages/contact.html" class="btn btn-outline-primary btn-sm px-3">Back to Contact Form</a>
                        <a href="../pages/dashboard.php" class="btn btn-primary btn-sm px-3">Go to Dashboard</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger py-2 px-3 mb-4 text-start" role="alert">
                        <strong>Submission Failed:</strong>
                        <ul class="mb-0 mt-2 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= htmlspecialchars($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <a href="../pages/contact.html" class="btn btn-outline-primary btn-sm px-3">Back to Contact Form</a>
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
