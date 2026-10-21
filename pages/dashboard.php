<?php
// Practical 1, 3, 4, 10 - Authenticated Student Dashboard
require_once __DIR__ . '/../php/auth.php';
require_once __DIR__ . '/../php/db_pdo.php';

// Verify authentication and session timeout
check_auth('student');

$studentName = $_SESSION['full_name'] ?? 'Demo Student';
$enrollment  = $_SESSION['enrollment_no'] ?? 'STU2026001';
$program     = $_SESSION['course'] ?? 'B.Tech Computer Engineering';
$semester    = '3';
$email       = $_SESSION['email'] ?? 'student@charusat.edu.in';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | CHARUSAT Student Hub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body>
    <!-- Main University Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary-dark shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center gap-2" href="home.html">
                <span class="badge bg-white text-primary fw-bold px-2 py-1">CHARUSAT</span>
                <span class="fw-bold tracking-wide">CHARUSAT STUDENT HUB</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="home.html">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="attendance.html">Attendance</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Assignments</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Courses</a></li>
                    <li class="nav-item"><a class="nav-link" href="timetable.html">Timetable</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Announcements</a></li>
                    <li class="nav-item"><a class="nav-link" href="events.html">Events</a></li>
                    <li class="nav-item"><a class="nav-link" href="profile.html">Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="#">Settings</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.html">Register</a></li>
                </ul>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-outline-light btn-sm" id="themeToggleBtn">Dark</button>
                    <a href="../php/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="container my-3" style="max-width: 1080px;">
        <!-- Dismissible Notice Banner (Practical 4) -->
        <div class="notice-banner d-flex justify-content-between align-items-center mb-3" id="noticeBanner">
            <div>
                <strong>Notice:</strong> Mid Semester Examination will begin from 7 September 2026.
            </div>
            <button type="button" class="btn-close btn-close-sm" id="dismissNoticeBtn" aria-label="Close"></button>
        </div>

        <!-- Welcome Banner Card -->
        <div class="welcome-card d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="h4 fw-bold mb-1">Welcome back, <?= htmlspecialchars($studentName) ?></h1>
                <div class="small opacity-90">
                    Enrollment: <strong><?= htmlspecialchars($enrollment) ?></strong> | 
                    Program: <strong><?= htmlspecialchars($program) ?></strong> | 
                    Semester: <strong><?= htmlspecialchars($semester) ?></strong>
                </div>
            </div>
            <div>
                <button class="btn btn-light btn-sm text-primary fw-semibold px-3 py-2 shadow-sm text-nowrap" data-bs-toggle="modal" data-bs-target="#quickInfoModal">
                    View Quick Information
                </button>
            </div>
        </div>

        <!-- Student Hub Highlights (Interactive Carousel Slider - Practical 4) -->
        <div class="highlights-container mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 fw-bold mb-0 text-dark">Student Hub Highlights</h2>
                <div class="d-flex gap-1">
                    <button class="btn btn-outline-secondary btn-sm py-0 px-2" id="prevHighlightBtn">Previous</button>
                    <button class="btn btn-outline-secondary btn-sm py-0 px-2" id="nextHighlightBtn">Next</button>
                </div>
            </div>
            <div id="highlightSlider">
                <div class="slider-slide active">
                    <h3 class="h6 fw-semibold text-primary mb-1">Upcoming Campus Hackathon 2026</h3>
                    <p class="small text-muted mb-0">Registrations are now open for the 24-hour university coding sprint organized at CSPIT Computer Center. Register early to secure lab terminals.</p>
                </div>
                <div class="slider-slide">
                    <h3 class="h6 fw-semibold text-success mb-1">Mid-Term Examination Seating Arrangement</h3>
                    <p class="small text-muted mb-0">Examination hall tickets and assigned lab seat numbers for Semester 3 have been uploaded to the university server portal.</p>
                </div>
                <div class="slider-slide">
                    <h3 class="h6 fw-semibold text-info mb-1">Central Library Online Journal Access</h3>
                    <p class="small text-muted mb-0">Access over 50,000+ IEEE, ACM, and Springer journals off-campus using your CHARUSAT single sign-on credentials.</p>
                </div>
            </div>
        </div>

        <!-- 4-Column Responsive Attendance & Academic KPI Cards (Practical 3 & 4) -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">Attendance</div>
                    <div class="kpi-value">92%</div>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">Good Standing</span>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">Assignments</div>
                    <div class="kpi-value text-primary">3 Pending</div>
                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Action Required</span>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">Courses</div>
                    <div class="kpi-value text-primary">4 Enrolled</div>
                    <span class="badge bg-primary text-white">16 Credits</span>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <div class="kpi-card">
                    <div class="kpi-label">Upcoming Event</div>
                    <div class="kpi-value text-dark" style="font-size: 1.3rem;">Hackathon</div>
                    <span class="badge bg-primary text-white">7 August</span>
                </div>
            </div>
        </div>

        <!-- Two Columns: Academic Modules Overview & Latest Announcement -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-6">
                <div class="card shadow-sm border h-100">
                    <div class="card-header bg-white py-3 border-bottom">
                        <h2 class="h6 fw-bold mb-0 text-dark">Academic Modules Overview</h2>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Module</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Attendance</td>
                                    <td><span class="badge bg-success">92%</span></td>
                                </tr>
                                <tr>
                                    <td>Assignments</td>
                                    <td><span class="badge bg-warning text-dark">3 Pending</span></td>
                                </tr>
                                <tr>
                                    <td>Examinations</td>
                                    <td><span class="badge bg-primary">Scheduled</span></td>
                                </tr>
                                <tr>
                                    <td>Fee Clearance</td>
                                    <td><span class="badge bg-success">Verified</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="card shadow-sm border h-100">
                    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                        <h2 class="h6 fw-bold mb-0 text-dark">Latest Announcement</h2>
                        <span class="badge bg-danger">Important</span>
                    </div>
                    <div class="card-body p-3">
                        <h3 class="h6 fw-bold mb-2">Mid Semester Examination Schedule</h3>
                        <p class="small text-secondary mb-2">
                            Mid Semester Examination will begin from 7 September 2026. Students must verify their examination seating numbers and carry their physical college identity cards into the testing halls.
                        </p>
                        <p class="small text-muted mb-0">Detailed room-wise seating charts will be released 48 hours prior to the commencement of exams.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Quick Info Modal (Practical 4) -->
    <div class="modal fade" id="quickInfoModal" tabindex="-1" aria-labelledby="quickInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title h6" id="quickInfoModalLabel">Student Academic Profile Summary</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="list-group list-group-flush small">
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Full Name:</span>
                            <span class="fw-semibold"><?= htmlspecialchars($studentName) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Enrollment ID:</span>
                            <span class="fw-semibold"><?= htmlspecialchars($enrollment) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">University Email:</span>
                            <span><?= htmlspecialchars($email) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Degree Program:</span>
                            <span><?= htmlspecialchars($program) ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Affiliated Institute:</span>
                            <span>CSPIT (CHARUSAT)</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span class="text-muted">Current Semester:</span>
                            <span class="badge bg-primary">Semester <?= htmlspecialchars($semester) ?></span>
                        </li>
                    </ul>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <a href="profile.html" class="btn btn-primary btn-sm">Open Full Profile</a>
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-3 text-muted small mt-auto border-top bg-white">
        © 2026 CHARUSAT Student Hub - Charotar University of Science and Technology
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script src="../js/dashboard.js"></script>
</body>
</html>
