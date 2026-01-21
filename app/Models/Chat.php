<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDO;

class Chat
{
    public static function getAllForUser(int $userId): array
    {
        $stmt = DB::pdo()->prepare('SELECT c.id, c.title, c.created_at FROM conversations c JOIN conversation_users cu ON cu.conversation_id=c.id WHERE cu.user_id=:uid ORDER BY c.id DESC');
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function create(int $userId, ?string $title = null): int
    {
        DB::pdo()->prepare('INSERT INTO conversations (title, created_by, created_at) VALUES (:t, :by, :at)')->execute([':t' => $title, ':by' => $userId, ':at' => date('c')]);
        $id = (int)DB::pdo()->lastInsertId();
        DB::pdo()->prepare('INSERT INTO conversation_users (conversation_id, user_id, role) VALUES (:cid, :uid, :role)')->execute([':cid' => $id, ':uid' => $userId, ':role' => 'client']);
        return $id;
    }

    public static function get(int $id, int $userId): ?array
    {
        $stmt = DB::pdo()->prepare('SELECT c.* FROM conversations c JOIN conversation_users cu ON cu.conversation_id=c.id WHERE c.id=:id AND cu.user_id=:uid');
        $stmt->execute([':id' => $id, ':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function getOrCreateForUser(int $userId): array
    {
        $stmt = DB::pdo()->prepare('SELECT c.* FROM conversations c JOIN conversation_users cu ON cu.conversation_id=c.id WHERE cu.user_id=:uid ORDER BY c.id DESC LIMIT 1');
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) return $row;
        DB::pdo()->prepare('INSERT INTO conversations (title, created_by, created_at) VALUES (:t, :by, :at)')->execute([':t' => null, ':by' => $userId, ':at' => date('c')]);
        $id = (int)DB::pdo()->lastInsertId();
        DB::pdo()->prepare('INSERT INTO conversation_users (conversation_id, user_id, role) VALUES (:cid, :uid, :role)')->execute([':cid' => $id, ':uid' => $userId, ':role' => 'client']);
        return ['id' => $id, 'title' => null, 'created_by' => $userId, 'created_at' => date('c')];
    }

    public static function messages(int $conversationId, int $limit = 100): array
    {
        $stmt = DB::pdo()->prepare('SELECT m.id, m.user_id, m.kind, m.text, m.created_at FROM messages m WHERE m.conversation_id = :cid ORDER BY m.id DESC LIMIT :lim');
        $stmt->bindValue(':cid', $conversationId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return array_reverse($rows);
    }

    public static function sendMessage(int $conversationId, int $userId, string $kind, ?string $text): int
    {
        DB::pdo()->prepare('INSERT INTO messages (conversation_id, user_id, kind, text, created_at) VALUES (:cid, :uid, :k, :t, :at)')->execute([':cid' => $conversationId, ':uid' => $userId, ':k' => $kind, ':t' => $text, ':at' => date('c')]);
        return (int)DB::pdo()->lastInsertId();
    }

    public static function addAttachment(int $messageId, string $filename, string $mime, int $size, string $path): void
    {
        DB::pdo()->prepare('INSERT INTO attachments (message_id, filename, mime, size, path, created_at) VALUES (:mid, :fn, :mime, :sz, :p, :at)')->execute([':mid' => $messageId, ':fn' => $filename, ':mime' => $mime, ':sz' => $size, ':p' => $path, ':at' => date('c')]);
    }

    public static function attachmentsOfMessage(int $messageId): array
    {
        $stmt = DB::pdo()->prepare('SELECT id, filename, mime, size, path FROM attachments WHERE message_id = :mid');
        $stmt->execute([':mid' => $messageId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function setTyping(int $conversationId, int $userId): void
    {
        DB::pdo()->prepare('REPLACE INTO typing (conversation_id, user_id, updated_at) VALUES (:cid, :uid, :at)')->execute([':cid' => $conversationId, ':uid' => $userId, ':at' => date('c')]);
    }

    public static function typingUsers(int $conversationId, int $excludeUserId): array
    {
        $stmt = DB::pdo()->prepare('SELECT user_id FROM typing WHERE conversation_id = :cid AND updated_at >= :th AND user_id <> :ex');
        $stmt->execute([':cid' => $conversationId, ':th' => date('c', time()-10), ':ex' => $excludeUserId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public static function clearMessages(int $conversationId): void
    {
        // First delete attachments
        $stmt = DB::pdo()->prepare('DELETE FROM attachments WHERE message_id IN (SELECT id FROM messages WHERE conversation_id = :cid)');
        $stmt->execute([':cid' => $conversationId]);
        
        // Then delete messages
        $stmt = DB::pdo()->prepare('DELETE FROM messages WHERE conversation_id = :cid');
        $stmt->execute([':cid' => $conversationId]);
    }

    public static function delete(int $conversationId): void
    {
        self::clearMessages($conversationId);
        
        DB::pdo()->prepare('DELETE FROM conversation_users WHERE conversation_id = :cid')->execute([':cid' => $conversationId]);
        DB::pdo()->prepare('DELETE FROM typing WHERE conversation_id = :cid')->execute([':cid' => $conversationId]);
        DB::pdo()->prepare('DELETE FROM conversations WHERE id = :cid')->execute([':cid' => $conversationId]);
    }
}

