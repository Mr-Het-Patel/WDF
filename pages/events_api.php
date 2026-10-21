<?php
// Practical 13 - Asynchronous Event CRUD Interface consuming PHP JSON API via Fetch API
require_once __DIR__ . '/../php/auth.php';

// Session verification
check_auth('admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Events API CRUD | CHARUSAT Admin</title>
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
                    <li class="nav-item"><a class="nav-link" href="manage_events.php">Manage Events</a></li>
                    <li class="nav-item"><a class="nav-link active" href="events_api.php">Events API CRUD</a></li>
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
        <!-- Dynamic Alert Container -->
        <div id="apiAlertBox" class="d-none"></div>

        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <div>
                <h1 class="h4 fw-bold mb-0 text-primary">Live Events API CRUD</h1>
                <div class="small text-muted">Asynchronous database operations using Fetch API without page reload</div>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addEventModal">+ Add Event via API</button>
                <button class="btn btn-outline-secondary btn-sm px-3" id="refreshBtn">Refresh</button>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card shadow-sm border mb-4">
            <div class="card-body p-3">
                <div class="row g-2 align-items-center">
                    <div class="col-12 col-md-5">
                        <input type="text" class="form-control form-control-sm" id="apiSearchInput" placeholder="Live search title, venue or description...">
                    </div>
                    <div class="col-12 col-md-4">
                        <select class="form-select form-select-sm" id="apiCategoryFilter">
                            <option value="" selected>All Categories</option>
                            <option value="Competition">Competition</option>
                            <option value="Workshop">Workshop</option>
                            <option value="Seminar">Seminar</option>
                            <option value="Cultural">Cultural</option>
                            <option value="Sports">Sports</option>
                            <option value="Academic">Academic</option>
                            <option value="Social">Social</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <select class="form-select form-select-sm" id="apiSortSelect">
                            <option value="earliest" selected>Date: Earliest</option>
                            <option value="latest">Date: Latest</option>
                            <option value="title">Title: A to Z</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="small text-muted fw-semibold mb-2" id="apiEventCount">Loading events from API...</div>

        <!-- Dynamic API Events Table -->
        <div class="card shadow-sm border">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Venue</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="apiEventsTableBody">
                            <!-- Populated asynchronously via Fetch API in js/events_api.js -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Event Modal via API -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="addEventForm">
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title h6">Create Event (Asynchronous API)</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Event Title *</label>
                            <input type="text" class="form-control form-control-sm" id="addEventTitle" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea class="form-control form-control-sm" id="addEventDescription" rows="2"></textarea>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Event Date *</label>
                                <input type="date" class="form-control form-control-sm" id="addEventDate" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Category *</label>
                                <select class="form-select form-select-sm" id="addEventCategory" required>
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
                            <input type="text" class="form-control form-control-sm" id="addEventVenue" required>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="addSubmitBtn">Save Event</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Event Modal via API -->
    <div class="modal fade" id="editEventModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form id="editEventForm">
                    <input type="hidden" id="editEventId">
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title h6">Update Event (Asynchronous API)</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Event Title *</label>
                            <input type="text" class="form-control form-control-sm" id="editEventTitle" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea class="form-control form-control-sm" id="editEventDescription" rows="2"></textarea>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <label class="form-label small fw-bold">Event Date *</label>
                                <input type="date" class="form-control form-control-sm" id="editEventDate" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-bold">Category *</label>
                                <select class="form-select form-select-sm" id="editEventCategory" required>
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
                            <input type="text" class="form-control form-control-sm" id="editEventVenue" required>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm" id="editSubmitBtn">Update Event</button>
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
    <script src="../js/events_api.js"></script>
</body>
</html>
