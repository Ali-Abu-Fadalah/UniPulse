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

    // Fetch details for profile popup of an arbitrary user
    if (isset($_GET['id'])) {
        $target_id = (int)$_GET['id'];
        $stmt = $pdo->prepare('SELECT id, full_name, email, role, bio, avatar, created_at FROM users WHERE id = ?');
        $stmt->execute([$target_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notes WHERE user_id = ?');
        $stmt->execute([$target_id]);
        $notes_count = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM skills WHERE user_id = ?');
        $stmt->execute([$target_id]);
        $skills_count = (int) $stmt->fetchColumn();

        echo json_encode([
            'id'           => $user['id'],
            'full_name'    => $user['full_name'],
            'email'        => $user['email'],
            'role'         => $user['role'] ?? 'Student',
            'bio'          => $user['bio'] ?? '',
            'avatar'       => $user['avatar'] ?? '',
            'created_at'   => $user['created_at'],
            'notes_count'  => $notes_count,
            'skills_count' => $skills_count,
        ]);
        exit;
    }

    // User search endpoint for New Chat feature
    if (isset($_GET['search'])) {
        $q = trim($_GET['q'] ?? '');
        if ($q === '') {
            echo json_encode(['users' => []]);
            exit;
        }
        $like = '%' . $q . '%';
        $stmt = $pdo->prepare('
            SELECT id, full_name, avatar
            FROM users
            WHERE id != ? AND (full_name LIKE ? OR email LIKE ?)
            ORDER BY full_name ASC
            LIMIT 15
        ');
        $stmt->execute([$user_id, $like, $like]);
        echo json_encode(['users' => $stmt->fetchAll()]);
        exit;
    }

    $stmt = $pdo->prepare('SELECT full_name, email, bio, avatar, created_at FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notes WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $notes_count = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM skills WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $skills_count = (int) $stmt->fetchColumn();

    echo json_encode([
        'full_name'    => $user['full_name'],
        'email'        => $user['email'],
        'bio'          => $user['bio'] ?? '',
        'avatar'       => $user['avatar'] ?? '',
        'created_at'   => $user['created_at'],
        'notes_count'  => $notes_count,
        'skills_count' => $skills_count,
    ]);
    exit;
}

if ($method === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $bio       = trim($_POST['bio'] ?? '');

    if ($full_name === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Name cannot be empty']);
        exit;
    }

    if (mb_strlen($full_name) > 80) {
        http_response_code(400);
        echo json_encode(['error' => 'Name is too long (max 80 characters)']);
        exit;
    }

    if (mb_strlen($bio) > 300) {
        http_response_code(400);
        echo json_encode(['error' => 'Bio must be under 300 characters']);
        exit;
    }

    $avatar_path = null;

    if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file    = $_FILES['avatar'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo   = new finfo(FILEINFO_MIME_TYPE);
        $mime    = $finfo->file($file['tmp_name']);

        if (!in_array($mime, $allowed)) {
            http_response_code(400);
            echo json_encode(['error' => 'Only JPG, PNG, GIF, or WebP images allowed']);
            exit;
        }

        if ($file['size'] > 2 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'Image must be under 2MB']);
            exit;
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $user_id . '_' . time() . '.' . $ext;
        $dir      = __DIR__ . '/../assets/uploads/avatars/';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
            $avatar_path = 'assets/uploads/avatars/' . $filename;
        }
    }

    if ($avatar_path) {
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, bio = ?, avatar = ? WHERE id = ?');
        $stmt->execute([$full_name, $bio, $avatar_path, $user_id]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET full_name = ?, bio = ? WHERE id = ?');
        $stmt->execute([$full_name, $bio, $user_id]);
    }

    $_SESSION['full_name'] = $full_name;
    if ($avatar_path) {
        $_SESSION['avatar'] = $avatar_path;
    }

    $stmt = $pdo->prepare('SELECT full_name, email, bio, avatar FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $updated = $stmt->fetch();

    echo json_encode([
        'success'    => true,
        'full_name'  => $updated['full_name'],
        'bio'        => $updated['bio'] ?? '',
        'avatar'     => $updated['avatar'] ?? '',
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
