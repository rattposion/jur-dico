<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDO;

class AIAnalysis
{
    // Read-only access enforcement: Only SELECT queries are written here.
    // In a stricter environment, we would use a separate DB user with SELECT-only privileges.
    
    public static function getGlobalStats(): array
    {
        // Audit log for security
        Audit::log(0, 'ai_db_scan', null, 'Global stats requested');

        $pdo = DB::pdo();
        
        $total = $pdo->query('SELECT COUNT(1) FROM records')->fetchColumn();
        $categorized = $pdo->query('SELECT COUNT(1) FROM records WHERE category_id IS NOT NULL')->fetchColumn();
        $ai_labeled = $pdo->query('SELECT COUNT(1) FROM records WHERE ai_label IS NOT NULL')->fetchColumn();
        
        // Check for potential issues
        $missing_meta = $pdo->query('SELECT COUNT(1) FROM records WHERE ementa IS NULL OR decisao IS NULL')->fetchColumn();
        $low_conf = $pdo->query('SELECT COUNT(1) FROM records WHERE ai_confidence IS NOT NULL AND ai_confidence < 0.6')->fetchColumn();
        
        return [
            'total_processes' => (int)$total,
            'categorized' => (int)$categorized,
            'ai_analyzed' => (int)$ai_labeled,
            'integrity_issues' => (int)$missing_meta,
            'low_confidence' => (int)$low_conf,
            'scan_time' => date('c')
        ];
    }

    public static function getProblematicProcesses(int $limit = 50): array
    {
        Audit::log(0, 'ai_db_scan', null, 'Problematic processes listing');

        $sql = "SELECT id, numeroProcesso, ai_confidence, ai_label, created_at 
                FROM records 
                WHERE (ai_confidence < 0.6 AND ai_confidence IS NOT NULL) 
                   OR (ementa IS NULL OR decisao IS NULL)
                ORDER BY created_at DESC 
                LIMIT :limit";
                
        $stmt = DB::pdo()->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function getProcessDetails(string $id): ?array
    {
        Audit::log(0, 'ai_db_read', $id, 'Full process details requested');

        $stmt = DB::pdo()->prepare('SELECT r.*, c.name as category_name 
                                    FROM records r 
                                    LEFT JOIN categories c ON r.category_id = c.id 
                                    WHERE r.id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ?: null;
    }

    public static function searchProcesses(string $query): array
    {
        // Sanitize input for LIKE is handled by PDO binding, but we validate strictly string
        if (empty($query)) return [];
        
        Audit::log(0, 'ai_db_search', null, "Search: $query");

        $sql = "SELECT id, numeroProcesso, ementa, ai_label, ai_confidence 
                FROM records 
                WHERE numeroProcesso LIKE :q OR ementa LIKE :q 
                LIMIT 20";
        $stmt = DB::pdo()->prepare($sql);
        $stmt->bindValue(':q', "%$query%", PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
