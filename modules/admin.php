<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!is_logged_in() || !is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $type = $_GET['type'] ?? '';

    if ($type === 'users') {
        $stmt = $pdo->query('SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC');
        echo json_encode($stmt->fetchAll());
    } elseif ($type === 'notes') {
        $stmt = $pdo->query('
            SELECT n.id, n.content, n.created_at, u.full_name 
            FROM notes n 
            JOIN users u ON n.user_id = u.id 
            ORDER BY n.created_at DESC
        ');
        echo json_encode($stmt->fetchAll());
    } elseif ($type === 'skills') {
        $stmt = $pdo->query('
            SELECT s.id, s.offered, s.needed, u.full_name 
            FROM skills s 
            JOIN users u ON s.user_id = u.id 
            ORDER BY s.created_at DESC
        ');
        echo json_encode($stmt->fetchAll());
    } elseif ($type === 'products') {
        $stmt = $pdo->query('
            SELECT p.id, p.title, p.price, p.created_at, u.full_name 
            FROM products p 
            JOIN users u ON p.user_id = u.id 
            ORDER BY p.created_at DESC
        ');
        echo json_encode($stmt->fetchAll());
    } elseif ($type === 'events') {
        $stmt = $pdo->query('
            SELECT e.id, e.title, e.location, e.date, e.time, u.full_name,
                   (SELECT COUNT(*) FROM event_rsvps WHERE event_id = e.id) as rsvp_count
            FROM events e 
            JOIN users u ON e.user_id = u.id 
            ORDER BY e.created_at DESC
        ');
        echo json_encode($stmt->fetchAll());
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid type']);
    }
    exit;
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';
    $id = (int)($body['id'] ?? 0);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID required']);
        exit;
    }

    if ($action === 'delete_user') {
        // Don't delete yourself
        if ($id === $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete yourself']);
            exit;
        }

        // Delete dependencies first
        $pdo->prepare('DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?')->execute([$id, $id]);
        $pdo->prepare('DELETE FROM skills WHERE user_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM notes WHERE user_id = ?')->execute([$id]);
        
        // Cascade delete products (with images)
        $stmt = $pdo->prepare('SELECT image FROM products WHERE user_id = ?');
        $stmt->execute([$id]);
        $products = $stmt->fetchAll();
        foreach ($products as $p) {
            if ($p['image'] && strpos($p['image'], '/unihub/assets/uploads/marketplace/') === 0) {
                $local_path = __DIR__ . '/..' . str_replace('/unihub', '', $p['image']);
                if (file_exists($local_path)) {
                    @unlink($local_path);
                }
            }
        }
        $pdo->prepare('DELETE FROM products WHERE user_id = ?')->execute([$id]);
        
        // Cascade delete events and RSVPs
        $stmt = $pdo->prepare('SELECT id FROM events WHERE user_id = ?');
        $stmt->execute([$id]);
        $events = $stmt->fetchAll();
        foreach ($events as $ev) {
            $pdo->prepare('DELETE FROM event_rsvps WHERE event_id = ?')->execute([$ev['id']]);
        }
        $pdo->prepare('DELETE FROM events WHERE user_id = ?')->execute([$id]);
        
        // Cascade delete RSVPs this user joined
        $pdo->prepare('DELETE FROM event_rsvps WHERE user_id = ?')->execute([$id]);

        // Delete user
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);

        echo json_encode(['success' => true]);
    } elseif ($action === 'delete_note') {
        $pdo->prepare('DELETE FROM notes WHERE id = ?')->execute([$id]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'delete_skill') {
        $pdo->prepare('DELETE FROM skills WHERE id = ?')->execute([$id]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'delete_product') {
        $stmt = $pdo->prepare('SELECT image FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        if ($product && $product['image'] && strpos($product['image'], '/unihub/assets/uploads/marketplace/') === 0) {
            $local_path = __DIR__ . '/..' . str_replace('/unihub', '', $product['image']);
            if (file_exists($local_path)) {
                @unlink($local_path);
            }
        }
        $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'delete_event') {
        $pdo->prepare('DELETE FROM event_rsvps WHERE event_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM events WHERE id = ?')->execute([$id]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'toggle_admin') {
        if ($id === $_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot change your own role']);
            exit;
        }
        $role = $body['role'] ?? 'user';
        if ($role !== 'admin' && $role !== 'user') $role = 'user';
        
        $pdo->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $id]);
        echo json_encode(['success' => true]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
