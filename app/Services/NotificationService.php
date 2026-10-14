<?php
namespace App\Services;

use App\Database;

class NotificationService {
    public static function create(int $userId, string $title, string $message, string $type = 'info', ?string $linkUrl = null): int {
        try {
            return Database::insert('notifications', [
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'is_read' => 0,
                'link_url' => $linkUrl,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            error_log("Notification creation failed: " . $e->getMessage());
            return 0;
        }
    }

    public static function getUnread(int $userId, int $limit = 10): array {
        return Database::fetchAll(
            "SELECT * FROM notifications WHERE user_id = :uid AND is_read = 0 ORDER BY created_at DESC LIMIT $limit",
            ['uid' => $userId]
        );
    }

    public static function markAllRead(int $userId): void {
        Database::query("UPDATE notifications SET is_read = 1 WHERE user_id = :uid", ['uid' => $userId]);
    }
}
