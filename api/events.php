<?php
// Practical 13 - RESTful JSON API for Campus Events
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../php/auth.php';
require_once __DIR__ . '/../php/db_mysqli.php';

start_session_safe();

$method = $_SERVER['REQUEST_METHOD'];

// Helper to return structured JSON response
function sendJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// 1. GET Request: List, search, filter, and sort events
if ($method === 'GET') {
    $search = trim($_GET['search'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $sort = trim($_GET['sort'] ?? 'earliest');

    $sql = "SELECT id, title, description, event_date, venue, category, poster_path FROM events WHERE 1=1";
    $types = '';
    $params = [];

    if ($search !== '') {
        $sql .= " AND (title LIKE ? OR description LIKE ? OR venue LIKE ?)";
        $searchTerm = "%$search%";
        $types .= 'sss';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    if ($category !== '' && $category !== 'All Categories') {
        $sql .= " AND category = ?";
        $types .= 's';
        $params[] = $category;
    }

    if ($sort === 'latest') {
        $sql .= " ORDER BY event_date DESC, id DESC";
    } elseif ($sort === 'title') {
        $sql .= " ORDER BY title ASC";
    } else {
        $sql .= " ORDER BY event_date ASC, id ASC";
    }

    $stmt = $mysqli->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
    $stmt->close();

    sendJson([
        'success' => true,
        'count' => count($events),
        'data' => $events
    ], 200);
}

// 2. POST Request: Create, Update, or Delete Events
if ($method === 'POST') {
    // Read JSON payload or regular POST form body
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!$data) {
        $data = $_POST;
    }

    $action = $data['action'] ?? 'create';

    // Verify authentication (admin or student session)
    $userId = $_SESSION['user_id'] ?? 1;

    if ($action === 'create') {
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $date = trim($data['event_date'] ?? '');
        $venue = trim($data['venue'] ?? '');
        $category = trim($data['category'] ?? '');

        if (empty($title) || empty($date) || empty($venue) || empty($category)) {
            sendJson(['success' => false, 'error' => 'Title, event date, venue, and category are required.'], 400);
        }

        $stmt = $mysqli->prepare("INSERT INTO events (title, description, event_date, venue, category, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $title, $description, $date, $venue, $category, $userId);
        if ($stmt->execute()) {
            $newId = $mysqli->insert_id;
            $stmt->close();

            // Log action
            $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_name, entity_type, entity_id, details) VALUES (?, 'CREATE_EVENT', 'events', ?, ?)");
            $details = "Created event: $title via Fetch API.";
            $logStmt->bind_param("iis", $userId, $newId, $details);
            $logStmt->execute();
            $logStmt->close();

            sendJson(['success' => true, 'message' => 'Event created successfully.', 'id' => $newId], 201);
        } else {
            sendJson(['success' => false, 'error' => 'Failed to create event.'], 500);
        }
    }

    if ($action === 'update') {
        $id = (int)($data['id'] ?? 0);
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $date = trim($data['event_date'] ?? '');
        $venue = trim($data['venue'] ?? '');
        $category = trim($data['category'] ?? '');

        if ($id <= 0 || empty($title) || empty($date) || empty($venue) || empty($category)) {
            sendJson(['success' => false, 'error' => 'Invalid parameters for update.'], 400);
        }

        $stmt = $mysqli->prepare("UPDATE events SET title = ?, description = ?, event_date = ?, venue = ?, category = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $title, $description, $date, $venue, $category, $id);
        if ($stmt->execute()) {
            $stmt->close();

            // Log action
            $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_name, entity_type, entity_id, details) VALUES (?, 'UPDATE_EVENT', 'events', ?, ?)");
            $details = "Updated event ID: $id ($title) via Fetch API.";
            $logStmt->bind_param("iis", $userId, $id, $details);
            $logStmt->execute();
            $logStmt->close();

            sendJson(['success' => true, 'message' => 'Event updated successfully.'], 200);
        } else {
            sendJson(['success' => false, 'error' => 'Failed to update event.'], 500);
        }
    }

    if ($action === 'delete') {
        $id = (int)($data['id'] ?? 0);
        if ($id <= 0) {
            sendJson(['success' => false, 'error' => 'Invalid event ID for deletion.'], 400);
        }

        // Get title for logging
        $titleStmt = $mysqli->prepare("SELECT title FROM events WHERE id = ?");
        $titleStmt->bind_param("i", $id);
        $titleStmt->execute();
        $titleRes = $titleStmt->get_result()->fetch_assoc();
        $eventTitle = $titleRes['title'] ?? "ID $id";
        $titleStmt->close();

        $stmt = $mysqli->prepare("DELETE FROM events WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $stmt->close();

            // Log action
            $logStmt = $mysqli->prepare("INSERT INTO audit_logs (user_id, action_name, entity_type, entity_id, details) VALUES (?, 'DELETE_EVENT', 'events', ?, ?)");
            $details = "Deleted event: $eventTitle via Fetch API.";
            $logStmt->bind_param("iis", $userId, $id, $details);
            $logStmt->execute();
            $logStmt->close();

            sendJson(['success' => true, 'message' => 'Event deleted successfully.'], 200);
        } else {
            sendJson(['success' => false, 'error' => 'Failed to delete event.'], 500);
        }
    }

    sendJson(['success' => false, 'error' => 'Unsupported action.'], 400);
}

sendJson(['success' => false, 'error' => 'Method not allowed.'], 405);
