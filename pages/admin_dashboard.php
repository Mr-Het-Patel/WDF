<?php
// Practical 14 - Admin Dashboard with Role-Based Access Control, Dynamic SQL Metrics, and System Audit Log
require_once __DIR__ . '/../php/auth.php';
require_once __DIR__ . '/../php/db_mysqli.php';

// Strict Role-Based Access Control
check_auth('admin');

$adminUsername = $_SESSION['username'] ?? 'admin';

// 1. Dynamic KPI calculations via SQL aggregations
$studentCountRes = $mysqli->query("SELECT COUNT(*) AS total FROM students");
$totalStudents = $studentCountRes->fetch_assoc()['total'] ?? 0;

$eventCountRes = $mysqli->query("SELECT COUNT(*) AS total FROM events");
$totalEvents = $eventCountRes->fetch_assoc()['total'] ?? 0;

$regCountRes = $mysqli->query("SELECT COUNT(*) AS total FROM registrations WHERE status = 'confirmed'");
$totalRegistrations = $regCountRes->fetch_assoc()['total'] ?? 0;

$userCountRes = $mysqli->query("SELECT COUNT(*) AS total FROM users");
$totalUsers = $userCountRes->fetch_assoc()['total'] ?? 0;

// 2. Fetch Recently Registered Students (latest 5)
$recentStudentsRes = $mysqli->query("SELECT enrollment_no, full_name, email, course, year FROM students ORDER BY id DESC LIMIT 5");
$recentStudents = [];
while ($row = $recentStudentsRes->fetch_assoc()) {
    $recentStudents[] = $row;
}

// 3. Fetch System Audit Trail Logs (latest 6)
$auditLogsRes = $mysqli->query("
    SELECT a.id, a.action_name, a.entity_type, a.details, a.created_at, u.username 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.id DESC LIMIT 6
");
$auditLogs = [];
while ($row = $auditLogsRes->fetch_assoc()) {
    $auditLogs[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | CHARUSAT Student Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .table td, .table th { padding: 6px 10px; font-size: 0.85rem; }
        .badge-year { font-size: 0.75rem; background-color: #0d6efd; color: #fff; font-weight: 500; }
        .action-card {
            border: 1px solid #bfdbfe;
            border-radius: 8px;
            padding: 16px;
            text-align: center;
            background: #fff;
            height: 100%;
        }
        [data-bs-theme="dark"] .action-card {
            background: #1e293b;
            border-color: #1e3a8a;
        }
    </style>
</head>
<body>
    <!-- Dynamic Role-Based Navbar for Administrators -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary-dark shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center gap-2" href="admin_dashboard.php">
                <span class="badge bg-white text-primary fw-bold px-2 py-1">CHARUSAT</span>
                <span class="fw-bold tracking-wide">CHARUSAT STUDENT HUB</span>
                <span class="badge bg-danger ms-1">Admin</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="admin_dashboard.php">Overview</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_students.php">Manage Students</a></li>
                    <li class="nav-item"><a class="nav-link" href="manage_events.php">Manage Events</a></li>
                    <li class="nav-item"><a class="nav-link" href="events_api.php">Events API CRUD</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Student View</a></li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-light btn-sm" id="themeToggleBtn">Dark</button>
                    <a href="../php/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container my-3" style="max-width: 1180px;">
        <!-- Admin Banner Header -->
        <div class="welcome-card d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h4 fw-bold mb-1">Administrative Control Panel</h1>
                <div class="small opacity-90">
                    Admin Account: <strong><?= htmlspecialchars($adminUsername) ?></strong> | Role: <strong>System Administrator</strong>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="manage_students.php" class="btn btn-light btn-sm text-primary fw-semibold px-3 shadow-sm">+ New Student</a>
                <a href="manage_events.php" class="btn btn-outline-light btn-sm fw-semibold px-3 shadow-sm">+ New Event</a>
            </div>
        </div>

        <!-- 4 KPI Dynamic SQL Metrics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">Total Students</div>
                    <div class="kpi-value"><?= (int)$totalStudents ?></div>
                    <span class="badge bg-primary text-white">Registered</span>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">Active Events</div>
                    <div class="kpi-value text-primary"><?= (int)$totalEvents ?></div>
                    <span class="badge bg-primary text-white">Campus Directory</span>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">Event Bookings</div>
                    <div class="kpi-value text-primary"><?= (int)$totalRegistrations ?></div>
                    <span class="badge bg-primary text-white">Confirmed</span>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">Portal Users</div>
                    <div class="kpi-value text-dark"><?= (int)$totalUsers ?></div>
                    <span class="badge bg-primary text-white">Auth Accounts</span>
                </div>
            </div>
        </div>

        <!-- Quick Module Navigation Links -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="action-card shadow-sm">
                    <h3 class="h6 fw-bold text-primary mb-1">Student Records</h3>
                    <p class="small text-muted mb-3" style="min-height: 38px;">View, search, filter, add, edit, and delete student database records.</p>
                    <a href="manage_students.php" class="btn btn-primary btn-sm w-100">Open Student Management &rarr;</a>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="action-card shadow-sm">
                    <h3 class="h6 fw-bold text-primary mb-1">Event Management</h3>
                    <p class="small text-muted mb-3" style="min-height: 38px;">Create events, edit schedules, and upload official posters with file validation.</p>
                    <a href="manage_events.php" class="btn btn-primary btn-sm w-100">Open Event Management &rarr;</a>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="action-card shadow-sm">
                    <h3 class="h6 fw-bold text-primary mb-1">Fetch API CRUD</h3>
                    <p class="small text-muted mb-3" style="min-height: 38px;">Asynchronous real-time event updates using Fetch API without page reload.</p>
                    <a href="events_api.php" class="btn btn-primary btn-sm w-100">Open Fetch API CRUD &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Recently Registered Students Table -->
        <div class="card shadow-sm border mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h2 class="h6 fw-bold mb-0 text-dark">Recently Registered Students</h2>
                <a href="manage_students.php" class="small text-primary text-decoration-none fw-semibold">View All Students</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Enrollment</th>
                                <th>Student Name</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th>Year</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentStudents as $stu): ?>
                                <tr>
                                    <td class="fw-bold text-nowrap"><?= htmlspecialchars($stu['enrollment_no']) ?></td>
                                    <td><?= htmlspecialchars($stu['full_name']) ?></td>
                                    <td class="small"><?= htmlspecialchars($stu['email']) ?></td>
                                    <td class="small"><?= htmlspecialchars($stu['course']) ?></td>
                                    <td><span class="badge badge-year"><?= htmlspecialchars($stu['year']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- System Audit Trail & Activity Log Table -->
        <div class="card shadow-sm border mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h2 class="h6 fw-bold mb-0 text-dark">System Audit Trail & Activity Log</h2>
                <span class="badge bg-secondary">Security & Operations</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Entity</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auditLogs as $log): ?>
                                <tr>
                                    <td class="small text-nowrap"><?= htmlspecialchars($log['created_at']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($log['username'] ?? 'System') ?></span></td>
                                    <td><span class="badge bg-primary-subtle text-primary fw-semibold"><?= htmlspecialchars($log['action_name']) ?></span></td>
                                    <td class="small text-muted"><?= htmlspecialchars($log['entity_type']) ?></td>
                                    <td class="small"><?= htmlspecialchars($log['details']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <footer class="text-center py-3 text-muted small mt-auto border-top bg-white">
        © 2026 CHARUSAT Student Hub - Charotar University of Science and Technology
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
</body>
</html>
