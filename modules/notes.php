<?php
require_once '../includes/auth.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!verify_session()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt  = $pdo->prepare('SELECT id, content, created_at FROM notes WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user_id]);
    $notes = $stmt->fetchAll();
    echo json_encode(['notes' => $notes]);
    exit;
}

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';

    if ($action === 'add') {
        $content = trim($body['content'] ?? '');

        if ($content === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Note content cannot be empty']);
            exit;
        }

        if (mb_strlen($content) > 1000) {
            http_response_code(400);
            echo json_encode(['error' => 'Note is too long (max 1000 characters)']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO notes (user_id, content) VALUES (?, ?)');
        $stmt->execute([$user_id, $content]);

        $id = $pdo->lastInsertId();
        $stmt = $pdo->prepare('SELECT id, content, created_at FROM notes WHERE id = ?');
        $stmt->execute([$id]);
        $note = $stmt->fetch();

        echo json_encode(['success' => true, 'note' => $note]);
        exit;
    }

    if ($action === 'edit') {
        $id      = (int)($body['id'] ?? 0);
        $content = trim($body['content'] ?? '');

        if ($content === '' || !$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            exit;
        }

        if (mb_strlen($content) > 1000) {
            http_response_code(400);
            echo json_encode(['error' => 'Note is too long (max 1000 characters)']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE notes SET content = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$content, $id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare('SELECT id, content, created_at FROM notes WHERE id = ?');
            $stmt->execute([$id]);
            $note = $stmt->fetch();
            echo json_encode(['success' => true, 'note' => $note]);
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'Note not found or access denied']);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($body['id'] ?? 0);

        $stmt = $pdo->prepare('DELETE FROM notes WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user_id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Note not found']);
            exit;
        }

        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
