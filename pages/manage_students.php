<?php
// Practical 11 - Administrative Student Management CRUD with Search, Filter, and CSV Export
require_once __DIR__ . '/../php/auth.php';
require_once __DIR__ . '/../php/db_mysqli.php';

// Check administrator authorization
check_auth('admin');

$adminUserId = $_SESSION['user_id'] ?? 1;
$feedbackMsg = '';
$feedbackType = 'success';

// Handle CSV Data Export (?export=csv)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=students_export_' . date('Ymd_His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Enrollment No', 'Full Name', 'Email', 'Mobile', 'Course', 'Year', 'Gender', 'Created At']);

    $exportQuery = "SELECT id, enrollment_no, full_name, email, mobile, course, year, gender, created_at FROM students ORDER BY id DESC";
    $expRes = $mysqli->query($exportQuery);
    while ($row = $expRes->fetch_assoc()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// Handle POST: Add Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $mobile   = trim($_POST['mobile'] ?? '');
    $course   = trim($_POST['course'] ?? '');
    $year     = trim($_POST['year'] ?? '');
    $gender   = trim($_POST['gender'] ?? 'Male');

    if ($fullName && $email && $mobile && $course && $year) {
        // Generate enrollment
        $countRes = $mysqli->query("SELECT MAX(id) AS max_id FROM students");
        $maxRow = $countRes->fetch_assoc();
        $nextNum = ($maxRow['max_id'] ?? 21) + 1;
        $enrollment = sprintf('STU2026%03d', $nextNum);

        $stmt = $mysqli->prepare("INSERT INTO students (enrollment_no, full_name, email, mobile, course, year, gender) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $enrollment, $fullName, $email, $mobile, $course, $year, $gender);
        if ($stmt->execute()) {
            $newId = $mysqli->insert_id;
            $stmt->close();

            // Audit log
            $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_name, entity_type, entity_id, details) VALUES (?, 'CREATE_STUDENT', 'students', ?, ?)");
            $details = "Admin created student: $fullName ($enrollment)";
            $logStmt->bind_param("iis", $adminUserId, $newId, $details);
            $logStmt->execute();
            $logStmt->close();

            $feedbackMsg = "Student $fullName ($enrollment) added successfully.";
        } else {
            $feedbackMsg = "Error adding student: " . $mysqli->error;
            $feedbackType = 'danger';
        }
    }
}

// Handle POST: Edit Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id       = (int)($_POST['student_id'] ?? 0);
    $fullName = trim($_POST['full_name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $mobile   = trim($_POST['mobile'] ?? '');
    $course   = trim($_POST['course'] ?? '');
    $year     = trim($_POST['year'] ?? '');
    $gender   = trim($_POST['gender'] ?? 'Male');

    if ($id > 0 && $fullName && $email && $mobile) {
        $stmt = $mysqli->prepare("UPDATE students SET full_name = ?, email = ?, mobile = ?, course = ?, year = ?, gender = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $fullName, $email, $mobile, $course, $year, $gender, $id);
        if ($stmt->execute()) {
            $stmt->close();

            // Audit log
            $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_name, entity_type, entity_id, details) VALUES (?, 'UPDATE_STUDENT', 'students', ?, ?)");
            $details = "Admin updated details for student ID: $id ($fullName)";
            $logStmt->bind_param("iis", $adminUserId, $id, $details);
            $logStmt->execute();
            $logStmt->close();

            $feedbackMsg = "Student record updated successfully.";
        } else {
            $feedbackMsg = "Error updating student: " . $mysqli->error;
            $feedbackType = 'danger';
        }
    }
}

// Handle POST: Delete Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)($_POST['student_id'] ?? 0);
    if ($id > 0) {
        // Fetch details for logging
        $infoStmt = $mysqli->prepare("SELECT enrollment_no, full_name FROM students WHERE id = ?");
        $infoStmt->bind_param("i", $id);
        $infoStmt->execute();
        $studentInfo = $infoStmt->get_result()->fetch_assoc();
        $infoStmt->close();

        $stmt = $mysqli->prepare("DELETE FROM students WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $stmt->close();

            // Audit log
            $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_name, entity_type, entity_id, details) VALUES (?, 'DELETE_STUDENT', 'students', ?, ?)");
            $details = "Admin deleted student record: " . ($studentInfo['full_name'] ?? "ID $id");
            $logStmt->bind_param("iis", $adminUserId, $id, $details);
            $logStmt->execute();
            $logStmt->close();

            $feedbackMsg = "Student record deleted successfully.";
        } else {
            $feedbackMsg = "Error deleting student: " . $mysqli->error;
            $feedbackType = 'danger';
        }
    }
}

// Filter and Search parameters
$search = trim($_GET['search'] ?? '');
$courseFilter = trim($_GET['course'] ?? '');

$sql = "SELECT id, enrollment_no, full_name, email, mobile, course, year, gender FROM students WHERE 1=1";
$params = [];
$types = '';

if ($search !== '') {
    $sql .= " AND (enrollment_no LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $like = "%$search%";
    $types .= 'sss';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($courseFilter !== '' && $courseFilter !== 'All Courses') {
    $sql .= " AND course = ?";
    $types .= 's';
    $params[] = $courseFilter;
}

$sql .= " ORDER BY id DESC";

$stmt = $mysqli->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$students = [];
while ($row = $result->fetch_assoc()) {
    $students[] = $row;
}
$stmt->close();
$totalRecords = count($students);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management | CHARUSAT Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/common.css">
    <style>
        .table td, .table th { padding: 6px 10px; font-size: 0.85rem; }
        .badge-year { font-size: 0.75rem; background-color: #0d6efd; color: #fff; font-weight: 500; }
    </style>
</head>
<body>
    <!-- Admin Navigation Bar -->
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
                    <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Overview</a></li>
                    <li class="nav-item"><a class="nav-link active" href="manage_students.php">Manage Students</a></li>
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

    <main class="container my-4" style="max-width: 1180px;">
        <?php if ($feedbackMsg): ?>
            <div class="alert alert-<?= $feedbackType ?> alert-dismissible fade show py-2 px-3 small" role="alert">
                <?= htmlspecialchars($feedbackMsg) ?>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Title & Action Buttons -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <h1 class="h4 fw-bold mb-0 text-primary">Student Management</h1>
                <div class="small text-muted">Total Records: <?= $totalRecords ?> student(s)</div>
            </div>
            <div class="d-flex gap-2">
                <a href="manage_students.php?export=csv" class="btn btn-outline-secondary btn-sm px-3">Export to CSV</a>
                <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addStudentModal">+ Add Student</button>
            </div>
        </div>

        <!-- Search & Filter Card -->
        <div class="card shadow-sm border mb-4">
            <div class="card-body p-3">
                <form method="GET" action="manage_students.php" class="row g-2 align-items-center">
                    <div class="col-12 col-md-6">
                        <input type="text" class="form-control form-control-sm" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, enrollment, or email...">
                    </div>
                    <div class="col-12 col-md-4">
                        <select class="form-select form-select-sm" name="course">
                            <option value="" <?= empty($courseFilter) ? 'selected' : '' ?>>All Courses</option>
                            <option value="BCA" <?= $courseFilter === 'BCA' ? 'selected' : '' ?>>BCA</option>
                            <option value="B.Tech Computer Engineering" <?= $courseFilter === 'B.Tech Computer Engineering' ? 'selected' : '' ?>>B.Tech Computer Engineering</option>
                            <option value="B.Tech IT" <?= $courseFilter === 'B.Tech IT' ? 'selected' : '' ?>>B.Tech IT</option>
                            <option value="MCA" <?= $courseFilter === 'MCA' ? 'selected' : '' ?>>MCA</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                        <a href="manage_students.php" class="btn btn-outline-secondary btn-sm w-100">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Student Records Table -->
        <div class="card shadow-sm border">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Enrollment</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Mobile</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Gender</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No student records found matching the filter criteria.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $stu): ?>
                                    <tr>
                                        <td class="fw-bold text-nowrap"><?= htmlspecialchars($stu['enrollment_no']) ?></td>
                                        <td><?= htmlspecialchars($stu['full_name']) ?></td>
                                        <td class="small"><?= htmlspecialchars($stu['email']) ?></td>
                                        <td class="small"><?= htmlspecialchars($stu['mobile']) ?></td>
                                        <td class="small"><?= htmlspecialchars($stu['course']) ?></td>
                                        <td><span class="badge badge-year"><?= htmlspecialchars($stu['year']) ?></span></td>
                                        <td><?= htmlspecialchars($stu['gender']) ?></td>
                                        <td class="text-center text-nowrap">
                                            <button class="btn btn-sm btn-outline-primary py-0 px-2 me-1" 
                                                    onclick='openEditModal(<?= json_encode($stu) ?>)'>Edit</button>
                                            <button class="btn btn-sm btn-outline-danger py-0 px-2" 
                                                    onclick='openDeleteModal(<?= $stu["id"] ?>, "<?= htmlspecialchars($stu["full_name"]) ?>", "<?= htmlspecialchars($stu["enrollment_no"]) ?>")'>Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Student Modal -->
    <div class="modal fade" id="addStudentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="manage_students.php">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title h6">Add New Student</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" class="form-control form-control-sm" name="full_name" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Email</label>
                            <input type="email" class="form-control form-control-sm" name="email" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Mobile</label>
                            <input type="tel" class="form-control form-control-sm" name="mobile" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Course</label>
                            <select class="form-select form-select-sm" name="course" required>
                                <option value="B.Tech Computer Engineering">B.Tech Computer Engineering</option>
                                <option value="B.Tech IT">B.Tech IT</option>
                                <option value="BCA">BCA</option>
                                <option value="MCA">MCA</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Year</label>
                            <select class="form-select form-select-sm" name="year" required>
                                <option value="First Year">First Year</option>
                                <option value="Second Year">Second Year</option>
                                <option value="Third Year">Third Year</option>
                                <option value="Fourth Year">Fourth Year</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Gender</label>
                            <select class="form-select form-select-sm" name="gender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Add Student</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Student Modal -->
    <div class="modal fade" id="editStudentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="manage_students.php">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="student_id" id="editStudentId">
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title h6">Edit Student Record</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Enrollment No</label>
                            <input type="text" class="form-control form-control-sm bg-light" id="editEnrollment" readonly>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" class="form-control form-control-sm" name="full_name" id="editFullName" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Email</label>
                            <input type="email" class="form-control form-control-sm" name="email" id="editEmail" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Mobile</label>
                            <input type="tel" class="form-control form-control-sm" name="mobile" id="editMobile" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Course</label>
                            <select class="form-select form-select-sm" name="course" id="editCourse">
                                <option value="B.Tech Computer Engineering">B.Tech Computer Engineering</option>
                                <option value="B.Tech IT">B.Tech IT</option>
                                <option value="BCA">BCA</option>
                                <option value="MCA">MCA</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Year</label>
                            <select class="form-select form-select-sm" name="year" id="editYear">
                                <option value="First Year">First Year</option>
                                <option value="Second Year">Second Year</option>
                                <option value="Third Year">Third Year</option>
                                <option value="Fourth Year">Fourth Year</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Gender</label>
                            <select class="form-select form-select-sm" name="gender" id="editGender">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteStudentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="manage_students.php">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="student_id" id="deleteStudentId">
                    <div class="modal-header bg-danger text-white py-2">
                        <h5 class="modal-title h6">Confirm Deletion</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <p class="mb-2">Are you sure you want to delete this student record?</p>
                        <div class="fw-bold text-danger fs-5" id="deleteStudentText"></div>
                        <p class="small text-muted mt-2 mb-0">This operation is irreversible and will remove associated enrollments.</p>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm">Yes, Delete Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <footer class="text-center py-3 text-muted small mt-auto border-top bg-white">
        © 2026 CHARUSAT Student Hub - Charotar University of Science and Technology
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/common.js"></script>
    <script>
        const editModal = new bootstrap.Modal(document.getElementById('editStudentModal'));
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteStudentModal'));

        function openEditModal(student) {
            document.getElementById('editStudentId').value = student.id;
            document.getElementById('editEnrollment').value = student.enrollment_no;
            document.getElementById('editFullName').value = student.full_name;
            document.getElementById('editEmail').value = student.email;
            document.getElementById('editMobile').value = student.mobile;
            document.getElementById('editCourse').value = student.course;
            document.getElementById('editYear').value = student.year;
            document.getElementById('editGender').value = student.gender;
            editModal.show();
        }

        function openDeleteModal(id, name, enrollment) {
            document.getElementById('deleteStudentId').value = id;
            document.getElementById('deleteStudentText').textContent = `${name} (${enrollment})`;
            deleteModal.show();
        }
    </script>
</body>
</html>
