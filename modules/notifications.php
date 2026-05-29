<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    try {
        // Fetch unread count
        $count_stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $count_stmt->execute([$user_id]);
        $unread_count = (int)$count_stmt->fetchColumn();

        // Fetch notifications
        $stmt = $pdo->prepare('
            SELECT id, type, title, message, link, is_read, created_at 
            FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 20
        ');
        $stmt->execute([$user_id]);
        $notifications = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'unread_count' => $unread_count,
            'notifications' => $notifications
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch notifications: ' . $e->getMessage()]);
    }
    exit;
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? ($_POST['action'] ?? '');

    if ($action === 'mark_read') {
        $notif_id = (int)($body['id'] ?? ($_POST['id'] ?? 0));
        try {
            if ($notif_id > 0) {
                $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
                $stmt->execute([$notif_id, $user_id]);
            } else {
                // Mark all read
                $stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?');
                $stmt->execute([$user_id]);
            }
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to update notifications']);
        }
        exit;
    }

    if ($action === 'clear') {
        try {
            $stmt = $pdo->prepare('DELETE FROM notifications WHERE user_id = ?');
            $stmt->execute([$user_id]);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to clear notifications']);
        }
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
