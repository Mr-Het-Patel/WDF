<?php
// Practical 12 - Event Management CRUD with Poster Upload and File Validation
require_once __DIR__ . '/../php/auth.php';
require_once __DIR__ . '/../php/db_mysqli.php';

// Check administrator authorization
check_auth('admin');

$adminUserId = $_SESSION['user_id'] ?? 1;
$feedbackMsg = '';
$feedbackType = 'success';

$uploadDir = __DIR__ . '/../uploads/posters';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Function to validate and process file upload
function handlePosterUpload($fileInputName, &$error) {
    if (!isset($_FILES[$fileInputName]) || $_FILES[$fileInputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // No file uploaded
    }

    $file = $_FILES[$fileInputName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = "File upload failed with error code: " . $file['error'];
        return false;
    }

    // 1. Enforce file size limit (≤ 2MB = 2,097,152 bytes)
    $maxSize = 2 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        $error = "File size exceeds maximum limit of 2MB (" . round($file['size'] / 1024 / 1024, 2) . "MB uploaded).";
        return false;
    }

    // 2. Enforce MIME type validation via finfo
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $detectedMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($detectedMime, $allowedMimes)) {
        $error = "Invalid file type ($detectedMime). Allowed types: JPEG, PNG, WebP.";
        return false;
    }

    // 3. Cryptographically random safe filename
    $extMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $ext = $extMap[$detectedMime] ?? 'png';
    $safeFilename = 'poster_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $targetPath = __DIR__ . '/../uploads/posters/' . $safeFilename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return 'uploads/posters/' . $safeFilename;
    } else {
        $error = "Failed to move uploaded file to destination directory.";
        return false;
    }
}

// Handle Add Event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $eventDate   = trim($_POST['event_date'] ?? '');
    $venue       = trim($_POST['venue'] ?? '');
    $category    = trim($_POST['category'] ?? '');

    $uploadError = '';
    $posterPath = handlePosterUpload('poster_file', $uploadError);

    if ($uploadError !== '') {
        $feedbackMsg = $uploadError;
        $feedbackType = 'danger';
    } elseif ($title && $eventDate && $venue && $category) {
        if ($posterPath === null) {
            $posterPath = 'uploads/posters/poster_placeholder.png';
        }

        $stmt = $mysqli->prepare("INSERT INTO events (title, description, event_date, venue, category, poster_path, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", $title, $description, $eventDate, $venue, $category, $posterPath, $adminUserId);
        if ($stmt->execute()) {
            $newId = $mysqli->insert_id;
            $stmt->close();

            // Audit log
            $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_name, entity_type, entity_id, details) VALUES (?, 'CREATE_EVENT', 'events', ?, ?)");
            $details = "Admin created event: $title with poster upload.";
            $logStmt->bind_param("iis", $adminUserId, $newId, $details);
            $logStmt->execute();
            $logStmt->close();

            $feedbackMsg = "Event created successfully.";
        } else {
            $feedbackMsg = "Database error: " . $mysqli->error;
            $feedbackType = 'danger';
        }
    }
}

// Handle Edit Event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id          = (int)($_POST['event_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $eventDate   = trim($_POST['event_date'] ?? '');
    $venue       = trim($_POST['venue'] ?? '');
    $category    = trim($_POST['category'] ?? '');

    $uploadError = '';
    $newPoster = handlePosterUpload('poster_file', $uploadError);

    if ($uploadError !== '') {
        $feedbackMsg = $uploadError;
        $feedbackType = 'danger';
    } elseif ($id > 0 && $title && $eventDate && $venue && $category) {
        if ($newPoster) {
            $stmt = $mysqli->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, venue = ?, category = ?, poster_path = ? WHERE id = ?");
            $stmt->bind_param("ssssssi", $title, $description, $eventDate, $venue, $category, $newPoster, $id);
        } else {
            $stmt = $mysqli->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, venue = ?, category = ? WHERE id = ?");
            $stmt->bind_param("sssssi", $title, $description, $eventDate, $venue, $category, $id);
        }

        if ($stmt->execute()) {
            $stmt->close();

            // Audit log
            $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_name, entity_type, entity_id, details) VALUES (?, 'UPDATE_EVENT', 'events', ?, ?)");
            $details = "Admin updated event ID: $id ($title)";
            $logStmt->bind_param("iis", $adminUserId, $id, $details);
            $logStmt->execute();
            $logStmt->close();

            $feedbackMsg = "Event updated successfully.";
        } else {
            $feedbackMsg = "Database error: " . $mysqli->error;
            $feedbackType = 'danger';
        }
    }
}

// Handle Delete Event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)($_POST['event_id'] ?? 0);
    if ($id > 0) {
        // Fetch poster path to unlink from disk
        $infoStmt = $mysqli->prepare("SELECT title, poster_path FROM events WHERE id = ?");
        $infoStmt->bind_param("i", $id);
        $infoStmt->execute();
        $eventInfo = $infoStmt->get_result()->fetch_assoc();
        $infoStmt->close();

        if ($eventInfo && !empty($eventInfo['poster_path'])) {
            $fullPath = __DIR__ . '/../' . $eventInfo['poster_path'];
            if (file_exists($fullPath) && !str_contains($fullPath, 'placeholder')) {
                @unlink($fullPath);
            }
        }

        $stmt = $mysqli->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $stmt->close();

            // Audit log
            $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_name, entity_type, entity_id, details) VALUES (?, 'DELETE_EVENT', 'events', ?, ?)");
            $details = "Admin deleted event: " . ($eventInfo['title'] ?? "ID $id");
            $logStmt->bind_param("iis", $adminUserId, $id, $details);
            $logStmt->execute();
            $logStmt->close();

            $feedbackMsg = "Event deleted successfully.";
        } else {
            $feedbackMsg = "Error deleting event: " . $mysqli->error;
            $feedbackType = 'danger';
        }
    }
}

// Query all events
$eventsQuery = "SELECT id, title, description, event_date, venue, category, poster_path FROM events ORDER BY event_date ASC";
$result = $mysqli->query($eventsQuery);
$eventsList = [];
while ($row = $result->fetch_assoc()) {
    $eventsList[] = $row;
}
$totalEvents = count($eventsList);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management & Posters | CHARUSAT Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/common.css">
    <link rel="stylesheet" href="../css/events.css">
    <style>
        .table td, .table th { padding: 6px 10px; font-size: 0.85rem; }
    </style>
</head>
<body>
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
                    <li class="nav-item"><a class="nav-link" href="manage_students.php">Manage Students</a></li>
                    <li class="nav-item"><a class="nav-link active" href="manage_events.php">Manage Events</a></li>
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

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <h1 class="h4 fw-bold mb-0 text-primary">Event Management & Posters</h1>
                <div class="small text-muted">Total Events: <?= $totalEvents ?> recorded</div>
            </div>
            <div class="d-flex gap-2">
                <a href="events.html" class="btn btn-outline-secondary btn-sm px-3">View Public Events</a>
                <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addEventModal">+ Add Event</button>
            </div>
        </div>

        <div class="card shadow-sm border">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Poster</th>
                                <th>Event Title</th>
                                <th>Date</th>
                                <th>Venue</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventsList as $ev): ?>
                                <tr>
                                    <td>
                                        <div class="bg-primary text-white d-flex align-items-center justify-content-center fw-bold rounded" style="width: 50px; height: 32px; font-size: 0.7rem; letter-spacing: 0.5px;">
                                            CHARU
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($ev['title']) ?></div>
                                        <div class="small text-muted" style="max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= htmlspecialchars($ev['description'] ?? '') ?>
                                        </div>
                                    </td>
                                    <td class="text-nowrap"><?= htmlspecialchars($ev['event_date']) ?></td>
                                    <td><?= htmlspecialchars($ev['venue']) ?></td>
                                    <td><span class="badge badge-<?= strtolower($ev['category']) ?>"><?= htmlspecialchars($ev['category']) ?></span></td>
                                    <td><span class="badge bg-success-subtle text-success border border-success-subtle">upcoming</span></td>
                                    <td class="text-center text-nowrap">
                                        <button class="btn btn-sm btn-outline-primary py-0 px-2 me-1" onclick='openEditEventModal(<?= json_encode($ev) ?>)'>Edit</button>
                                        <button class="btn btn-sm btn-outline-danger py-0 px-2" onclick='openDeleteEventModal(<?= $ev["id"] ?>, "<?= htmlspecialchars($ev["title"]) ?>")'>Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Event Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="manage_events.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title h6">Add Campus Event</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Event Title *</label>
                            <input type="text" class="form-control form-control-sm" name="title" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea class="form-control form-control-sm" name="description" rows="2"></textarea>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Event Date *</label>
                                <input type="date" class="form-control form-control-sm" name="event_date" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Category *</label>
                                <select class="form-select form-select-sm" name="category" required>
                                    <option value="Competition">Competition</option>
                                    <option value="Workshop">Workshop</option>
                                    <option value="Seminar">Seminar</option>
                                    <option value="Cultural">Cultural</option>
                                    <option value="Sports">Sports</option>
                                    <option value="Academic">Academic</option>
                                    <option value="Social">Social</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Venue *</label>
                            <input type="text" class="form-control form-control-sm" name="venue" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Event Poster (JPEG, PNG, WebP ≤ 2MB)</label>
                            <input type="file" class="form-control form-control-sm" name="poster_file" accept="image/jpeg,image/png,image/webp">
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">Create Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div class="modal fade" id="editEventModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="manage_events.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="event_id" id="editEventId">
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title h6">Edit Campus Event</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Event Title *</label>
                            <input type="text" class="form-control form-control-sm" name="title" id="editEventTitle" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea class="form-control form-control-sm" name="description" id="editEventDesc" rows="2"></textarea>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Event Date *</label>
                                <input type="date" class="form-control form-control-sm" name="event_date" id="editEventDate" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Category *</label>
                                <select class="form-select form-select-sm" name="category" id="editEventCategory" required>
                                    <option value="Competition">Competition</option>
                                    <option value="Workshop">Workshop</option>
                                    <option value="Seminar">Seminar</option>
                                    <option value="Cultural">Cultural</option>
                                    <option value="Sports">Sports</option>
                                    <option value="Academic">Academic</option>
                                    <option value="Social">Social</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Venue *</label>
                            <input type="text" class="form-control form-control-sm" name="venue" id="editEventVenue" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Replace Poster Image (Optional, ≤ 2MB)</label>
                            <input type="file" class="form-control form-control-sm" name="poster_file" accept="image/jpeg,image/png,image/webp">
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

    <!-- Delete Event Modal -->
    <div class="modal fade" id="deleteEventModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="manage_events.php">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="event_id" id="deleteEventId">
                    <div class="modal-header bg-danger text-white py-2">
                        <h5 class="modal-title h6">Confirm Event Deletion</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <p class="mb-2">Are you sure you want to delete this event?</p>
                        <div class="fw-bold text-danger fs-5" id="deleteEventTitleText"></div>
                        <p class="small text-muted mt-2 mb-0">The event record and its uploaded poster image file will be permanently deleted.</p>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger btn-sm">Yes, Delete Event</button>
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
        const editEventModal = new bootstrap.Modal(document.getElementById('editEventModal'));
        const deleteEventModal = new bootstrap.Modal(document.getElementById('deleteEventModal'));

        function openEditEventModal(ev) {
            document.getElementById('editEventId').value = ev.id;
            document.getElementById('editEventTitle').value = ev.title;
            document.getElementById('editEventDesc').value = ev.description || '';
            document.getElementById('editEventDate').value = ev.event_date;
            document.getElementById('editEventVenue').value = ev.venue;
            document.getElementById('editEventCategory').value = ev.category;
            editEventModal.show();
        }

        function openDeleteEventModal(id, title) {
            document.getElementById('deleteEventId').value = id;
            document.getElementById('deleteEventTitleText').textContent = title;
            deleteEventModal.show();
        }
    </script>
</body>
</html>
