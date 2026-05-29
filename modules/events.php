<?php
$inc = is_dir('../Includes') ? '../Includes' : '../includes';
require_once $inc . '/auth.php';
require_once $inc . '/db.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_attendees') {
        $event_id = (int)($_GET['event_id'] ?? 0);
        $stmt = $pdo->prepare('SELECT u.id, u.full_name, u.avatar FROM event_rsvps r JOIN users u ON r.user_id = u.id WHERE r.event_id = ?');
        $stmt->execute([$event_id]);
        echo json_encode(['attendees' => $stmt->fetchAll()]);
        exit;
    }

    $q = trim($_GET['q'] ?? '');
    
    $sql = '
        SELECT e.*, u.full_name as host_name, u.avatar as host_avatar,
               (SELECT COUNT(*) FROM event_rsvps WHERE event_id = e.id) as rsvp_count,
               (SELECT COUNT(*) FROM event_rsvps WHERE event_id = e.id AND user_id = ?) as user_rsvp
        FROM events e
        JOIN users u ON e.user_id = u.id
    ';
    
    if ($q !== '') {
        $sql .= ' WHERE e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?';
        $sql .= ' ORDER BY e.date ASC, e.time ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id, "%$q%", "%$q%", "%$q%"]);
    } else {
        $sql .= ' ORDER BY e.date ASC, e.time ASC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    }
    
    $events = $stmt->fetchAll();
    
    foreach ($events as &$e) {
        $e['rsvp_count'] = (int)$e['rsvp_count'];
        $e['user_rsvp']  = (int)$e['user_rsvp'] > 0;
    }
    
    echo json_encode(['events' => $events]);
    exit;
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? ($_POST['action'] ?? '');

    if ($action === 'add') {
        if (!is_admin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Only admins can create events.']);
            exit;
        }

        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $date        = trim($_POST['date'] ?? '');
        $time        = trim($_POST['time'] ?? '');
        $location    = trim($_POST['location'] ?? '');

        if (!$title || !$description || !$date || !$time || !$location) {
            http_response_code(400);
            echo json_encode(['error' => 'All fields are required.']);
            exit;
        }

        if (mb_strlen($title) > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'Title too long (max 100 characters)']);
            exit;
        }

        if (mb_strlen($description) > 1000) {
            http_response_code(400);
            echo json_encode(['error' => 'Description too long (max 1000 characters)']);
            exit;
        }

        if (mb_strlen($location) > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'Location name too long (max 100 characters)']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO events (user_id, title, description, date, time, location) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $title, $description, $date, $time, $location]);
        $id = $pdo->lastInsertId();

        $stmt = $pdo->prepare('
            SELECT e.*, u.full_name as host_name, 0 as rsvp_count, 0 as user_rsvp
            FROM events e
            JOIN users u ON e.user_id = u.id
            WHERE e.id = ?
        ');
        $stmt->execute([$id]);
        $new_event = $stmt->fetch();
        echo json_encode(['success' => true, 'event' => $new_event]);
        exit;
    }

    if ($action === 'rsvp') {
        $event_id = (int)($body['event_id'] ?? 0);

        if (!$event_id) {
            http_response_code(400);
            echo json_encode(['error' => 'Event ID required']);
            exit;
        }

        // Verify event exists
        $stmt = $pdo->prepare('SELECT id FROM events WHERE id = ?');
        $stmt->execute([$event_id]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
            exit;
        }

        // Check if already RSVP'd
        $stmt = $pdo->prepare('SELECT id FROM event_rsvps WHERE event_id = ? AND user_id = ?');
        $stmt->execute([$event_id, $user_id]);
        $rsvp = $stmt->fetch();

        if ($rsvp) {
            $stmt = $pdo->prepare('DELETE FROM event_rsvps WHERE id = ?');
            $stmt->execute([$rsvp['id']]);
            $is_attending = false;
        } else {
            $stmt = $pdo->prepare('INSERT INTO event_rsvps (event_id, user_id) VALUES (?, ?)');
            $stmt->execute([$event_id, $user_id]);
            $is_attending = true;

            // Notify event creator
            try {
                $evt_stmt = $pdo->prepare('SELECT user_id, title FROM events WHERE id = ?');
                $evt_stmt->execute([$event_id]);
                $event = $evt_stmt->fetch();
                if ($event && $event['user_id'] != $user_id) {
                    $usr_stmt = $pdo->prepare('SELECT full_name FROM users WHERE id = ?');
                    $usr_stmt->execute([$user_id]);
                    $rsvp_name = $usr_stmt->fetchColumn() ?: 'Someone';
                    
                    require_once $inc . '/notif_helper.php';
                    add_notification($pdo, $event['user_id'], 'rsvp', 'New Event RSVP', "$rsvp_name is attending your event '{$event['title']}'", '#events');
                }
            } catch (Exception $e) {
                // Fail silently
            }
        }

        // Get updated RSVP count
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM event_rsvps WHERE event_id = ?');
        $stmt->execute([$event_id]);
        $rsvp_count = (int)$stmt->fetchColumn();

        echo json_encode([
            'success'      => true,
            'is_attending' => $is_attending,
            'rsvp_count'   => $rsvp_count
        ]);
        exit;
    }

    if ($action === 'edit') {
        $id = (int)($body['id'] ?? 0);
        $title       = trim($_POST['title'] ?? ($body['title'] ?? ''));
        $description = trim($_POST['description'] ?? ($body['description'] ?? ''));
        $date        = trim($_POST['date'] ?? ($body['date'] ?? ''));
        $time        = trim($_POST['time'] ?? ($body['time'] ?? ''));
        $location    = trim($_POST['location'] ?? ($body['location'] ?? ''));

        if (!$id || !$title || !$description || !$date || !$time || !$location) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data or missing fields']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE events SET title = ?, description = ?, date = ?, time = ?, location = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$title, $description, $date, $time, $location, $id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare('
                SELECT e.*, u.full_name as host_name, 
                       (SELECT COUNT(*) FROM event_rsvps WHERE event_id = e.id) as rsvp_count,
                       (SELECT COUNT(*) FROM event_rsvps WHERE event_id = e.id AND user_id = ?) as user_rsvp
                FROM events e
                JOIN users u ON e.user_id = u.id
                WHERE e.id = ?
            ');
            $stmt->execute([$user_id, $id]);
            $event = $stmt->fetch();
            $event['rsvp_count'] = (int)$event['rsvp_count'];
            $event['user_rsvp']  = (int)$event['user_rsvp'] > 0;
            echo json_encode(['success' => true, 'event' => $event]);
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'Event not found or access denied']);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($body['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Event ID required']);
            exit;
        }

        // Check ownership
        $stmt = $pdo->prepare('SELECT id FROM events WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user_id]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Event not found or access denied']);
            exit;
        }

        $pdo->prepare('DELETE FROM event_rsvps WHERE event_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);

        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
