<?php
/**
 * Helper function to create user notifications in the database.
 */
if (!function_exists('add_notification')) {
    function add_notification($pdo, $user_id, $type, $title, $message, $link = '') {
        try {
            $stmt = $pdo->prepare('
                INSERT INTO notifications (user_id, type, title, message, link) 
                VALUES (?, ?, ?, ?, ?)
            ');
            return $stmt->execute([
                $user_id,
                $type,
                $title,
                $message,
                $link
            ]);
        } catch (Exception $e) {
            // Silently fail or log to error log to avoid breaking the core user experience
            error_log('Failed to create notification: ' . $e->getMessage());
            return false;
        }
    }
}
