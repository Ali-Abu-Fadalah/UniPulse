<?php
$inc = is_dir('../Includes') ? '../Includes' : '../includes';
require_once $inc . '/auth.php';
require_once $inc . '/db.php';

header('Content-Type: application/json');

if (!verify_session()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method  = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $with = isset($_GET['with']) ? (int)$_GET['with'] : 0;

    if ($with > 0 && $with !== $user_id) {
        $stmt = $pdo->prepare('
            SELECT m.id, m.sender_id, m.receiver_id, m.message, m.created_at,
                   u.avatar as sender_avatar, u.full_name as sender_name
            FROM messages m
            JOIN users u ON u.id = m.sender_id
            WHERE (m.sender_id = ? AND m.receiver_id = ?)
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ');
        $stmt->execute([$user_id, $with, $with, $user_id]);
        $messages = $stmt->fetchAll();

        $stmt2 = $pdo->prepare('SELECT id, full_name, email, role, bio, avatar, created_at FROM users WHERE id = ?');
        $stmt2->execute([$with]);
        $other = $stmt2->fetch();

        echo json_encode(['messages' => $messages, 'other_user' => $other]);
    } else {
        $stmt = $pdo->prepare('
            SELECT
                CASE WHEN m.sender_id = :uid THEN m.receiver_id ELSE m.sender_id END AS other_id,
                u.full_name,
                u.avatar,
                m.message AS last_message,
                m.created_at AS last_at
            FROM messages m
            JOIN users u ON u.id = CASE WHEN m.sender_id = :uid THEN m.receiver_id ELSE m.sender_id END
            WHERE m.id IN (
                SELECT MAX(id) FROM messages
                WHERE sender_id = :uid OR receiver_id = :uid
                GROUP BY CASE WHEN sender_id = :uid THEN receiver_id ELSE sender_id END
            )
            ORDER BY m.created_at DESC
        ');
        $stmt->execute([':uid' => $user_id]);
        $convs = $stmt->fetchAll();

        echo json_encode(['conversations' => $convs]);
    }
    exit;
}

if ($method === 'POST') {
    $body        = json_decode(file_get_contents('php://input'), true);
    $receiver_id = (int)($body['receiver_id'] ?? 0);
    $message     = trim($body['message'] ?? '');

    if (!$receiver_id || $receiver_id === $user_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid recipient']);
        exit;
    }

    if ($message === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Message cannot be empty']);
        exit;
    }

    if (mb_strlen($message) > 1000) {
        http_response_code(400);
        echo json_encode(['error' => 'Message too long (max 1000 characters)']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
    $stmt->execute([$receiver_id]);
    if (!$stmt->fetch()) {
        http_response_code(400);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)');
    $stmt->execute([$user_id, $receiver_id, $message]);

    // Send notification to the receiver
    try {
        require_once $inc . '/notif_helper.php';
        $sender_stmt = $pdo->prepare('SELECT full_name FROM users WHERE id = ?');
        $sender_stmt->execute([$user_id]);
        $sender_name = $sender_stmt->fetchColumn() ?: 'Someone';
        $preview = mb_strlen($message) > 60 ? mb_substr($message, 0, 57) . '...' : $message;
        add_notification($pdo, $receiver_id, 'message', 'New Message', "$sender_name: $preview", '#community');
    } catch (Exception $e) {
        // Fail silently
    }

    echo json_encode([
        'success' => true,
        'message' => [
            'id'          => (int)$pdo->lastInsertId(),
            'sender_id'   => $user_id,
            'receiver_id' => $receiver_id,
            'message'     => $message,
            'created_at'  => date('Y-m-d H:i:s'),
        ]
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
