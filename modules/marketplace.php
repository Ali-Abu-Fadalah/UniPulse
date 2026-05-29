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
    $my_listings = isset($_GET['my_listings']) && $_GET['my_listings'] === '1';
    $q = trim($_GET['q'] ?? '');

    if ($my_listings) {
        $stmt = $pdo->prepare('SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$user_id]);
        $products = $stmt->fetchAll();
    } else {
        if ($q !== '') {
            $stmt = $pdo->prepare('
                SELECT p.*, u.full_name as seller_name, u.avatar as seller_avatar 
                FROM products p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.user_id != ? AND (p.title LIKE ? OR p.description LIKE ?) 
                ORDER BY p.created_at DESC
            ');
            $stmt->execute([$user_id, "%$q%", "%$q%"]);
        } else {
            $stmt = $pdo->prepare('
                SELECT p.*, u.full_name as seller_name, u.avatar as seller_avatar 
                FROM products p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.user_id != ? 
                ORDER BY p.created_at DESC
            ');
            $stmt->execute([$user_id]);
        }
        $products = $stmt->fetchAll();
    }

    echo json_encode(['products' => $products]);
    exit;
}

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';

    // Handle AJAX JSON requests for actions like delete
    if (empty($action)) {
        $body = json_decode(file_get_contents('php://input'), true);
        $action = $body['action'] ?? '';
    }

    if ($action === 'add') {
        $title       = trim($_POST['title'] ?? '');
        $price       = floatval($_POST['price'] ?? 0.0);
        $description = trim($_POST['description'] ?? '');

        if ($title === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Product title cannot be empty']);
            exit;
        }

        if ($price <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Please enter a valid price greater than 0']);
            exit;
        }

        if ($description === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Product description cannot be empty']);
            exit;
        }

        if (mb_strlen($title) > 100) {
            http_response_code(400);
            echo json_encode(['error' => 'Title is too long (max 100 characters)']);
            exit;
        }

        if (mb_strlen($description) > 1000) {
            http_response_code(400);
            echo json_encode(['error' => 'Description is too long (max 1000 characters)']);
            exit;
        }

        $image_path = '';

        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file    = $_FILES['image'];
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
            $filename = $user_id . '_' . time() . '_' . uniqid() . '.' . $ext;
            $dir      = __DIR__ . '/../assets/uploads/marketplace/';

            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
                $image_path = '/unihub/assets/uploads/marketplace/' . $filename;
            } else {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to save product image']);
                exit;
            }
        }

        $stmt = $pdo->prepare('INSERT INTO products (user_id, title, price, description, image) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$user_id, $title, $price, $description, $image_path]);

        $id = $pdo->lastInsertId();
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $new_product = $stmt->fetch();

        echo json_encode(['success' => true, 'product' => $new_product]);
        exit;
    }

    if ($action === 'edit') {
        $id          = (int)($_POST['id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $price       = floatval($_POST['price'] ?? 0.0);
        $description = trim($_POST['description'] ?? '');

        if ($title === '' || $price <= 0 || $description === '' || !$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            exit;
        }

        if (mb_strlen($title) > 100 || mb_strlen($description) > 1000) {
            http_response_code(400);
            echo json_encode(['error' => 'Input too long']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user_id]);
        $product = $stmt->fetch();

        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found or access denied']);
            exit;
        }

        $image_path = $product['image'];

        if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file    = $_FILES['image'];
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo   = new finfo(FILEINFO_MIME_TYPE);
            $mime    = $finfo->file($file['tmp_name']);

            if (!in_array($mime, $allowed) || $file['size'] > 2 * 1024 * 1024) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid image']);
                exit;
            }

            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = $user_id . '_' . time() . '_' . uniqid() . '.' . $ext;
            $dir      = __DIR__ . '/../assets/uploads/marketplace/';

            if (!is_dir($dir)) mkdir($dir, 0755, true);

            if (move_uploaded_file($file['tmp_name'], $dir . $filename)) {
                // Delete old image
                if ($image_path && strpos($image_path, '/unihub/assets/uploads/marketplace/') === 0) {
                    $local_path = __DIR__ . '/..' . str_replace('/unihub', '', $image_path);
                    if (file_exists($local_path)) @unlink($local_path);
                }
                $image_path = '/unihub/assets/uploads/marketplace/' . $filename;
            }
        }

        $stmt = $pdo->prepare('UPDATE products SET title = ?, price = ?, description = ?, image = ? WHERE id = ?');
        $stmt->execute([$title, $price, $description, $image_path, $id]);

        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
        $stmt->execute([$id]);
        $updated = $stmt->fetch();

        echo json_encode(['success' => true, 'product' => $updated]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($body['id'] ?? ($_POST['id'] ?? 0));

        // Get product to check ownership and delete image file
        $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user_id]);
        $product = $stmt->fetch();

        if (!$product) {
            http_response_code(404);
            echo json_encode(['error' => 'Product not found or access denied']);
            exit;
        }

        // Delete image file if exists
        if ($product['image'] && strpos($product['image'], '/unihub/assets/uploads/marketplace/') === 0) {
            $local_path = __DIR__ . '/..' . str_replace('/unihub', '', $product['image']);
            if (file_exists($local_path)) {
                @unlink($local_path);
            }
        }

        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user_id]);

        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
