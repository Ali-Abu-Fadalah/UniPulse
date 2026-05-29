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
    $stmt = $pdo->prepare('SELECT id, offered, needed, created_at FROM skills WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user_id]);
    $my_skills = $stmt->fetchAll();

    $matches = [];
    if (!empty($my_skills)) {
        $offered_list = array_map('mb_strtolower', array_column($my_skills, 'offered'));
        $needed_list  = array_map('mb_strtolower', array_column($my_skills, 'needed'));

        $placeholders_o = implode(',', array_fill(0, count($offered_list), '?'));
        $placeholders_n = implode(',', array_fill(0, count($needed_list),  '?'));

        /*
         * SQL placeholder count breakdown:
         *   CASE WHEN offered IN ($placeholders_n)  → n
         *     AND needed  IN ($placeholders_o)       → o
         *   CASE WHEN offered IN ($placeholders_n)  → n
         *   CASE WHEN needed  IN ($placeholders_o)  → o
         *   WHERE user_id != ?                       → 1
         *   WHERE offered IN ($placeholders_n)       → n
         *      OR needed  IN ($placeholders_o)       → o
         *   Total: 3n + 3o + 1
         */
        $sql = "
            SELECT s.id, s.offered, s.needed, u.full_name, u.id as match_user_id,
                CASE
                    WHEN LOWER(s.offered) IN ($placeholders_n) AND LOWER(s.needed) IN ($placeholders_o) THEN 'both'
                    WHEN LOWER(s.offered) IN ($placeholders_n) THEN 'they_offer'
                    WHEN LOWER(s.needed)  IN ($placeholders_o) THEN 'they_need'
                END as match_type
            FROM skills s
            JOIN users u ON s.user_id = u.id
            WHERE s.user_id != ?
            AND (
                LOWER(s.offered) IN ($placeholders_n)
                OR LOWER(s.needed) IN ($placeholders_o)
            )
            ORDER BY match_type DESC, s.created_at DESC
        ";

        // Correct param order matches the placeholder order above (3n + 3o + 1 total)
        $full_params = array_merge(
            $needed_list, $offered_list,   // CASE WHEN 'both': offered IN n, needed IN o
            $needed_list,                  // CASE WHEN 'they_offer': offered IN n
            $offered_list,                 // CASE WHEN 'they_need': needed IN o
            [$user_id],                    // WHERE user_id != ?
            $needed_list, $offered_list    // WHERE offered IN n OR needed IN o
        );

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($full_params);
            $matches = $stmt->fetchAll();
        } catch (Exception $e) {
            // Log error but continue — matches will just be empty
            $matches = [];
        }
    }

    $q = trim($_GET['q'] ?? '');

    if ($q !== '') {
        $stmt = $pdo->prepare('
            SELECT s.id, s.offered, s.needed, u.full_name, u.id as match_user_id
            FROM skills s
            JOIN users u ON s.user_id = u.id
            WHERE s.user_id != ? AND (s.offered LIKE ? OR s.needed LIKE ?)
            ORDER BY s.created_at DESC
            LIMIT 50
        ');
        $stmt->execute([$user_id, "%$q%", "%$q%"]);
        $others = $stmt->fetchAll();
    } else {
        $stmt = $pdo->prepare('
            SELECT s.id, s.offered, s.needed, u.full_name, u.id as match_user_id
            FROM skills s
            JOIN users u ON s.user_id = u.id
            WHERE s.user_id != ?
            ORDER BY s.created_at DESC
            LIMIT 20
        ');
        $stmt->execute([$user_id]);
        $others = $stmt->fetchAll();
    }

    echo json_encode([
        'my_skills' => $my_skills,
        'matches'   => $matches,
        'others'    => $others,
    ]);
    exit;
}

if ($method === 'POST') {
    $body   = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';

    if ($action === 'add') {
        $offered = trim($body['offered'] ?? '');
        $needed  = trim($body['needed']  ?? '');

        if ($offered === '' || $needed === '') {
            http_response_code(400);
            echo json_encode(['error' => 'Both fields are required']);
            exit;
        }

        if (mb_strlen($offered) > 60 || mb_strlen($needed) > 60) {
            http_response_code(400);
            echo json_encode(['error' => 'Skill name too long (max 60 characters)']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM skills WHERE user_id = ?');
        $stmt->execute([$user_id]);
        if ($stmt->fetchColumn() >= 10) {
            http_response_code(400);
            echo json_encode(['error' => 'Maximum 10 skills allowed']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO skills (user_id, offered, needed) VALUES (?, ?, ?)');
        $stmt->execute([$user_id, $offered, $needed]);

        $id   = $pdo->lastInsertId();

        // Check for mutual skill matches
        try {
            $match_stmt = $pdo->prepare('
                SELECT s.user_id, u.full_name
                FROM skills s
                JOIN users u ON s.user_id = u.id
                WHERE s.user_id != ? 
                  AND LOWER(s.offered) = LOWER(?) 
                  AND LOWER(s.needed) = LOWER(?)
            ');
            $match_stmt->execute([$user_id, $needed, $offered]);
            $mutual_matches = $match_stmt->fetchAll();
            
            if (!empty($mutual_matches)) {
                require_once '../includes/notif_helper.php';
                $usr_stmt = $pdo->prepare('SELECT full_name FROM users WHERE id = ?');
                $usr_stmt->execute([$user_id]);
                $my_name = $usr_stmt->fetchColumn() ?: 'Someone';
                
                foreach ($mutual_matches as $m) {
                    // Notify other user
                    add_notification($pdo, $m['user_id'], 'skill_match', 'Mutual Skill Match!', "$my_name has a mutual skill match with you! (Offered: $offered ⇄ Needed: $needed)", '#skill-exchange');
                    // Notify current user
                    add_notification($pdo, $user_id, 'skill_match', 'Mutual Skill Match!', "You have a mutual skill match with {$m['full_name']}! (Offered: $offered ⇄ Needed: $needed)", '#skill-exchange');
                }
            }
        } catch (Exception $e) {
            // Fail silently
        }
        $stmt = $pdo->prepare('SELECT id, offered, needed, created_at FROM skills WHERE id = ?');
        $stmt->execute([$id]);
        $skill = $stmt->fetch();

        echo json_encode(['success' => true, 'skill' => $skill]);
        exit;
    }

    if ($action === 'edit') {
        $id      = (int)($body['id'] ?? 0);
        $offered = trim($body['offered'] ?? '');
        $needed  = trim($body['needed']  ?? '');

        if ($offered === '' || $needed === '' || !$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data']);
            exit;
        }

        if (mb_strlen($offered) > 60 || mb_strlen($needed) > 60) {
            http_response_code(400);
            echo json_encode(['error' => 'Skill name too long (max 60 characters)']);
            exit;
        }

        $stmt = $pdo->prepare('UPDATE skills SET offered = ?, needed = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$offered, $needed, $id, $user_id]);

        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare('SELECT id, offered, needed, created_at FROM skills WHERE id = ?');
            $stmt->execute([$id]);
            $skill = $stmt->fetch();
            echo json_encode(['success' => true, 'skill' => $skill]);
        } else {
            http_response_code(403);
            echo json_encode(['error' => 'Skill not found or access denied']);
        }
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($body['id'] ?? 0);

        $stmt = $pdo->prepare('DELETE FROM skills WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user_id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Skill not found']);
            exit;
        }

        echo json_encode(['success' => true]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
