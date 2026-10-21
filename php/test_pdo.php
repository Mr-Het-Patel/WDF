<?php
// Practical 8 - PDO Connectivity Diagnostic Test Script
require_once __DIR__ . '/db_pdo.php';

$success = false;
$dbName = 'studenthub_db';
$driver = 'mysql';
$serverVersion = 'Unknown';
$totalStudents = 0;
$adminEmail = 'Not Found';

try {
    $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    $serverVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

    // Query seeded students count using prepared statement
    $stmt1 = $pdo->prepare("SELECT COUNT(*) AS total FROM students");
    $stmt1->execute();
    $totalStudents = $stmt1->fetchColumn();

    // Query admin account using prepared statement
    $stmt2 = $pdo->prepare("SELECT email FROM users WHERE role = ? LIMIT 1");
    $stmt2->execute(['admin']);
    $admin = $stmt2->fetch();
    if ($admin) {
        $adminEmail = $admin['email'];
    }

    $success = true;
} catch (PDOException $e) {
    $errorMessage = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDO Database Connection Test | StudentHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/common.css">
    <style>
        body { background-color: #f4f6f9; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
        .card-custom { max-width: 650px; margin: 60px auto; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: none; }
        .card-header-custom { background: #0d6efd; color: #fff; padding: 14px 20px; font-weight: 600; border-top-left-radius: 8px; border-top-right-radius: 8px; }
        .table-custom th { width: 45%; color: #333; font-weight: 600; background: #fff; }
        .table-custom td { color: #555; background: #fff; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card card-custom">
            <div class="card-header-custom">
                PDO Database Connection Test
            </div>
            <div class="card-body p-4">
                <?php if ($success): ?>
                    <div class="alert alert-success py-2 px-3 mb-4" role="alert" style="background-color: #d1e7dd; border-color: #badbcc; color: #0f5132; font-size: 0.95rem;">
                        <strong>Success:</strong> Connected to MySQL database via PHP PDO.
                    </div>

                    <table class="table table-bordered table-custom mb-4">
                        <tbody>
                            <tr>
                                <th>Database Name</th>
                                <td><?= htmlspecialchars($dbName) ?></td>
                            </tr>
                            <tr>
                                <th>PDO Driver</th>
                                <td><?= htmlspecialchars($driver) ?></td>
                            </tr>
                            <tr>
                                <th>Server Version</th>
                                <td><?= htmlspecialchars($serverVersion) ?></td>
                            </tr>
                            <tr>
                                <th>Total Students Seeded</th>
                                <td><?= (int)$totalStudents ?></td>
                            </tr>
                            <tr>
                                <th>Admin Account Found</th>
                                <td><?= htmlspecialchars($adminEmail) ?></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="d-flex gap-2">
                        <a href="../pages/home.html" class="btn btn-outline-primary btn-sm px-3">Home</a>
                        <a href="../pages/login.html" class="btn btn-primary btn-sm px-3">Login</a>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger" role="alert">
                        <strong>Error:</strong> Failed to connect to database.<br>
                        <?= htmlspecialchars($errorMessage ?? 'Unknown error') ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
