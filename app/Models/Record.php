<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\DB;
use PDO;

class Record
{
    public static function upsert(array $data): void
    {
        // Map JSON aliases to DB columns
        $numeroProcesso = $data['numeroProcesso'] ?? $data['numero'] ?? null;
        $siglaClasse = $data['siglaClasse'] ?? $data['classe'] ?? null;
        $ministroRelator = $data['ministroRelator'] ?? $data['relator'] ?? null;
        
        // Normalize Dates to YYYY-MM-DD
        $normalizeDate = function($d) {
            if (!$d) return null;
            // If already YYYY-MM-DD, return it
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)) return $d;
            // Try to parse DD/MM/YYYY
            if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $d, $m)) {
                return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
            }
            // Try strtotime
            $ts = strtotime(str_replace('/', '-', $d)); // Handle / as separator
            if ($ts) return date('Y-m-d', $ts);
            return $d;
        };

        $dataDecisao = $normalizeDate($data['dataDecisao'] ?? $data['dataJulgamento'] ?? null);
        $dataPublicacao = $normalizeDate($data['dataPublicacao'] ?? null);

        $stmt = DB::pdo()->prepare('INSERT INTO records (
            id, numeroProcesso, numeroRegistro, siglaClasse, descricaoClasse,
            nomeOrgaoJulgador, codOrgaoJulgador, ministroRelator, dataPublicacao, ementa,
            tipoDeDecisao, dataDecisao, decisao, created_at
        ) VALUES (
            :id, :numeroProcesso, :numeroRegistro, :siglaClasse, :descricaoClasse,
            :nomeOrgaoJulgador, :codOrgaoJulgador, :ministroRelator, :dataPublicacao, :ementa,
            :tipoDeDecisao, :dataDecisao, :decisao, :created_at
        ) ON DUPLICATE KEY UPDATE
            numeroProcesso=VALUES(numeroProcesso),
            numeroRegistro=VALUES(numeroRegistro),
            siglaClasse=VALUES(siglaClasse),
            descricaoClasse=VALUES(descricaoClasse),
            nomeOrgaoJulgador=VALUES(nomeOrgaoJulgador),
            codOrgaoJulgador=VALUES(codOrgaoJulgador),
            ministroRelator=VALUES(ministroRelator),
            dataPublicacao=VALUES(dataPublicacao),
            ementa=VALUES(ementa),
            tipoDeDecisao=VALUES(tipoDeDecisao),
            dataDecisao=VALUES(dataDecisao),
            decisao=VALUES(decisao)');
        $stmt->execute([
            ':id' => (string)($data['id'] ?? uniqid()),
            ':numeroProcesso' => self::s($numeroProcesso),
            ':numeroRegistro' => self::s($data['numeroRegistro'] ?? null),
            ':siglaClasse' => self::s($siglaClasse),
            ':descricaoClasse' => self::s($data['descricaoClasse'] ?? null),
            ':nomeOrgaoJulgador' => self::s($data['nomeOrgaoJulgador'] ?? null),
            ':codOrgaoJulgador' => self::s($data['codOrgaoJulgador'] ?? null),
            ':ministroRelator' => self::s($ministroRelator),
            ':dataPublicacao' => self::s($dataPublicacao),
            ':ementa' => self::s($data['ementa'] ?? null),
            ':tipoDeDecisao' => self::s($data['tipoDeDecisao'] ?? null),
            ':dataDecisao' => self::s($dataDecisao),
            ':decisao' => self::s($data['decisao'] ?? null),
            ':created_at' => date('c'),
        ]);
    }

    public static function countBeforeYear(string $year): int
    {
        $stmt = DB::pdo()->prepare("SELECT COUNT(1) as c FROM records WHERE dataDecisao < :date");
        $stmt->execute([':date' => "{$year}-01-01"]);
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['c'];
    }

    public static function deleteBeforeYear(string $year): int
    {
        $stmt = DB::pdo()->prepare("DELETE FROM records WHERE dataDecisao < :date");
        $stmt->execute([':date' => "{$year}-01-01"]);
        return $stmt->rowCount();
    }

    public static function countBetweenYears(string $startYear, string $endYear): int
    {
        $stmt = DB::pdo()->prepare("SELECT COUNT(1) as c FROM records WHERE dataDecisao >= :start AND dataDecisao <= :end");
        $stmt->execute([':start' => "{$startYear}-01-01", ':end' => "{$endYear}-12-31"]);
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['c'];
    }

    public static function countAfterYear(string $year): int
    {
        $stmt = DB::pdo()->prepare("SELECT COUNT(1) as c FROM records WHERE dataDecisao > :date");
        $stmt->execute([':date' => "{$year}-12-31"]);
        return (int)$stmt->fetch(PDO::FETCH_ASSOC)['c'];
    }

    public static function deleteAfterYear(string $year): int
    {
        $stmt = DB::pdo()->prepare("DELETE FROM records WHERE dataDecisao > :date");
        $stmt->execute([':date' => "{$year}-12-31"]);
        return $stmt->rowCount();
    }

    public static function getTabCounts(?string $q, array $filters): array
    {
        unset($filters['tab']);
        [$where, $params] = self::buildAdvancedWhere($q, $filters);
        
        $sql = "SELECT 
            COUNT(1) as all_count,
            COUNT(CASE WHEN (r.siglaClasse = 'ACO' OR r.siglaClasse LIKE '%ACOR%') THEN 1 END) as acor,
            COUNT(CASE WHEN r.siglaClasse = 'DTXT' THEN 1 END) as dtxt,
            COUNT(CASE WHEN r.siglaClasse = 'ACP' THEN 1 END) as acp,
            COUNT(CASE WHEN r.siglaClasse IN ('AP', 'POP') THEN 1 END) as ap,
            COUNT(CASE WHEN (r.siglaClasse = 'MSC' OR r.siglaClasse = 'MS') THEN 1 END) as msc
            FROM records r 
            LEFT JOIN categories c ON c.id = r.category_id 
            $where";
            
        $stmt = DB::pdo()->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function getCategoryCounts(?string $q, array $filters): array
    {
        unset($filters['category']);
        [$where, $params] = self::buildAdvancedWhere($q, $filters);
        
        $sql = "SELECT COALESCE(r.category_id, 'uncategorized') as cat_id, COUNT(1) as count 
                FROM records r 
                $where 
                GROUP BY cat_id";
            
        $stmt = DB::pdo()->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public static function paginate(int $page, int $perPage, ?string $q, array $filters = [], string $sort = 'dataDecisao', string $dir = 'DESC'): array
    {
        $offset = ($page - 1) * $perPage;
        
        // Allowed sort columns
        $allowedSorts = ['numeroProcesso', 'siglaClasse', 'ministroRelator', 'nomeOrgaoJulgador', 'dataDecisao', 'created_at'];
        if (!in_array($sort, $allowedSorts)) $sort = 'dataDecisao';
        $dir = strtoupper($dir) === 'ASC' ? 'ASC' : 'DESC';
        
        // Advanced Search Logic integration
        // If specific advanced filters are present or q uses syntax, we delegate to buildAdvancedWhere
        // For backward compatibility, we keep basic logic if no special syntax/fields used.
        // But simpler to merge logic.
        
        [$where, $params] = self::buildAdvancedWhere($q, $filters);

        $sql = "SELECT r.id, r.numeroProcesso, r.numeroRegistro, r.siglaClasse, r.descricaoClasse, r.ministroRelator, r.nomeOrgaoJulgador, r.codOrgaoJulgador, r.dataDecisao, r.dataPublicacao, r.ementa, r.decisao, r.category_id, c.name AS category
                FROM records r LEFT JOIN categories c ON c.id = r.category_id
                $where ORDER BY r.$sort $dir LIMIT $perPage OFFSET $offset";
        $stmt = DB::pdo()->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v, PDO::PARAM_STR);
        $stmt->execute();
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $countStmt = DB::pdo()->prepare("SELECT COUNT(1) as c FROM records r $where");
        foreach ($params as $k => $v) $countStmt->bindValue($k, $v, PDO::PARAM_STR);
        $countStmt->execute();
        $total = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['c'];
        
        return ['items' => $items, 'total' => $total];
    }

    public static function autocomplete(string $term): array
    {
        $term = trim($term);
        if (strlen($term) < 2) return [];

        $termLike = '%' . $term . '%';
        // Search in Relator and Classe for suggestions
        $sql = "SELECT DISTINCT val FROM (
            SELECT ministroRelator as val FROM records WHERE ministroRelator LIKE :t1
            UNION
            SELECT descricaoClasse as val FROM records WHERE descricaoClasse LIKE :t2
            UNION
            SELECT nomeOrgaoJulgador as val FROM records WHERE nomeOrgaoJulgador LIKE :t3
        ) as u LIMIT 10";
        
        $stmt = DB::pdo()->prepare($sql);
        $stmt->execute([':t1' => $termLike, ':t2' => $termLike, ':t3' => $termLike]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // ... (rest of methods) ...

    private static function buildAdvancedWhere(?string $q, array $filters): array
    {
        $clauses = ['1=1'];
        $params = [];
        $i = 0; // Parameter counter for dynamic binding

        // 1. Structural Filters
        if (!empty($filters['type'])) { $clauses[] = 'r.siglaClasse = :type'; $params[':type'] = $filters['type']; }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'pending') $clauses[] = 'r.category_id IS NULL';
            if ($filters['status'] === 'categorized') $clauses[] = 'r.category_id IS NOT NULL';
        }
        if (!empty($filters['start'])) { $clauses[] = 'r.dataDecisao >= :start'; $params[':start'] = $filters['start']; }
        if (!empty($filters['end'])) { $clauses[] = 'r.dataDecisao <= :end'; $params[':end'] = $filters['end']; }
        
        // Tab Filters
        if (!empty($filters['tab'])) {
            switch ($filters['tab']) {
                case 'acor':
                    // Processo Estrutural ACOR - Assuming ACO or similar
                    $clauses[] = "(r.siglaClasse = 'ACO' OR r.siglaClasse LIKE '%ACOR%')";
                    break;
                case 'dtxt':
                    // Processo Estrutural DTXT - Placeholder or unknown sigla
                    $clauses[] = "r.siglaClasse = 'DTXT'";
                    break;
                case 'acp':
                    // Ação Civil Pública
                    $clauses[] = "r.siglaClasse = 'ACP'";
                    break;
                case 'ap':
                    // Ação Popular
                    $clauses[] = "r.siglaClasse IN ('AP', 'POP')";
                    break;
                case 'msc':
                    // Mandado de Segurança Coletivo
                    $clauses[] = "(r.siglaClasse = 'MSC' OR r.siglaClasse = 'MS')"; // Including MS as fallback for demo if MSC is empty
                    break;
            }
        }
        
        // Advanced Filters
        if (!empty($filters['tribunal'])) { $clauses[] = 'r.nomeOrgaoJulgador LIKE :trib'; $params[':trib'] = '%' . $filters['tribunal'] . '%'; }
        if (!empty($filters['juiz'])) { $clauses[] = 'r.ministroRelator LIKE :juiz'; $params[':juiz'] = '%' . $filters['juiz'] . '%'; }
        if (!empty($filters['numero'])) { $clauses[] = 'r.numeroProcesso LIKE :num'; $params[':num'] = '%' . $filters['numero'] . '%'; }
        if (!empty($filters['ano'])) { $clauses[] = 'r.dataDecisao LIKE :ano'; $params[':ano'] = $filters['ano'] . '%'; }

        // 2. Query String Parsing
        if ($q) {
            // Handle "data:YYYY-YYYY"
            if (preg_match('/data:(\d{4})-(\d{4})/', $q, $matches)) {
                $clauses[] = 'r.dataDecisao BETWEEN :dstart AND :dend';
                $params[':dstart'] = $matches[1] . '-01-01';
                $params[':dend'] = $matches[2] . '-12-31';
                $q = trim(str_replace($matches[0], '', $q));
            }

            // Handle Logical AND (split by ' AND ')
            $andParts = preg_split('/\s+AND\s+/i', $q, -1, PREG_SPLIT_NO_EMPTY);
            $andClauses = [];
            
            foreach ($andParts as $andPart) {
                // Handle Logical OR (split by comma within AND parts)
                $orParts = explode(',', $andPart);
                $orClauses = [];

                foreach ($orParts as $part) {
                    $part = trim($part);
                    if (empty($part)) continue;

                    $isExact = str_starts_with($part, '"') && str_ends_with($part, '"');
                    $isPartial = str_ends_with($part, '*');
                    
                    $term = $part;
                    if ($isExact) $term = trim($part, '"');
                    if ($isPartial) $term = rtrim($part, '*');

                    $p1 = ":q{$i}_em";
                    $p2 = ":q{$i}_de";
                    $p3 = ":q{$i}_mr";
                    $p4 = ":q{$i}_np";
                    $i++;

                    $fieldSearch = "(r.ementa LIKE $p1 OR r.decisao LIKE $p2 OR r.ministroRelator LIKE $p3 OR r.numeroProcesso LIKE $p4)";
                    
                    $val = '%' . $term . '%';

                    $params[$p1] = $val;
                    $params[$p2] = $val;
                    $params[$p3] = $val;
                    $params[$p4] = $val;
                    
                    $orClauses[] = $fieldSearch;
                }

                if (!empty($orClauses)) {
                    $andClauses[] = '(' . implode(' OR ', $orClauses) . ')';
                }
            }
            
            if (!empty($andClauses)) {
                $clauses[] = '(' . implode(' AND ', $andClauses) . ')';
            }
        }
        
        if (!empty($filters['category'])) { $clauses[] = 'r.category_id = :category'; $params[':category'] = (int)$filters['category']; }
        
        $where = 'WHERE ' . implode(' AND ', $clauses);
        return [$where, $params];
    }


    public static function find(string $id): ?array
    {
        $stmt = DB::pdo()->prepare('SELECT r.*, c.name AS category FROM records r LEFT JOIN categories c ON c.id = r.category_id WHERE r.id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function assignCategory(string $recordId, int $categoryId): bool
    {
        $stmt = DB::pdo()->prepare('UPDATE records SET category_id = :cid WHERE id = :id');
        $stmt->execute([':cid' => $categoryId, ':id' => $recordId]);
        return $stmt->rowCount() > 0;
    }

    public static function delete(string $id): bool
    {
        $stmt = DB::pdo()->prepare('DELETE FROM records WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function setAIMetadata(string $id, string $label, float $confidence, $metadata): void
    {
        $stmt = DB::pdo()->prepare('UPDATE records SET ai_label = :label, ai_confidence = :conf, ai_metadata = :meta WHERE id = :id');
        $stmt->execute([
            ':label' => $label,
            ':conf' => $confidence,
            ':meta' => $metadata === null ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE),
            ':id' => $id,
        ]);
    }

    public static function stats(array $filters): array
    {
        [$where, $params] = self::buildWhere($filters);
        $total = DB::pdo()->prepare("SELECT COUNT(1) c FROM records r $where");
        foreach ($params as $k => $v) $total->bindValue($k, $v);
        $total->execute();
        $t = (int)$total->fetch(PDO::FETCH_ASSOC)['c'];
        $cat = DB::pdo()->prepare("SELECT COUNT(1) c FROM records r $where AND r.category_id IS NOT NULL");
        foreach ($params as $k => $v) $cat->bindValue($k, $v);
        $cat->execute();
        $c = (int)$cat->fetch(PDO::FETCH_ASSOC)['c'];
        return ['total' => $t, 'categorized' => $c, 'pending' => $t - $c, 'percentage' => $t ? round($c*100/$t,2) : 0.0];
    }

    public static function byCategory(array $filters): array
    {
        [$where, $params] = self::buildWhere($filters);
        $sql = "SELECT c.name AS category, COUNT(1) AS count FROM records r LEFT JOIN categories c ON c.id = r.category_id $where AND r.category_id IS NOT NULL GROUP BY c.name ORDER BY count DESC";
        $stmt = DB::pdo()->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function timeline(array $filters): array
    {
        [$where, $params] = self::buildWhere($filters);
        $sql = "SELECT SUBSTRING(r.dataDecisao,1,6) AS ym, COUNT(1) AS count FROM records r $where AND r.category_id IS NOT NULL GROUP BY ym ORDER BY ym";
        $stmt = DB::pdo()->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function buildWhere(array $filters): array
    {
        $clauses = ['1=1'];
        $params = [];
        if (!empty($filters['type'])) { $clauses[] = 'r.siglaClasse = :type'; $params[':type'] = $filters['type']; }
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'pending') $clauses[] = 'r.category_id IS NULL';
            if ($filters['status'] === 'categorized') $clauses[] = 'r.category_id IS NOT NULL';
        }
        if (!empty($filters['start'])) { $clauses[] = 'r.dataDecisao >= :start'; $params[':start'] = $filters['start']; }
        if (!empty($filters['end'])) { $clauses[] = 'r.dataDecisao <= :end'; $params[':end'] = $filters['end']; }
        $where = 'WHERE ' . implode(' AND ', $clauses);
        return [$where, $params];
    }

    private static function s(?string $v): ?string
    {
        return $v === null ? null : (string)$v;
    }
}
