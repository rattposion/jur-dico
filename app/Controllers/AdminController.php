<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\JSONStream;
use App\Core\AIClient;
use App\Models\Record;
use App\Models\Category;
use App\Models\Audit;
use App\Models\UserApiKey;
use App\Services\RecordUpdater;

class AdminController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $cats = Category::all();
        $this->view('admin/dashboard', ['csrf' => CSRF::generate($this->config), 'categories' => $cats]);
    }

    public function stats(): void
    {
        Auth::requireAuth();
        $this->view('admin/stats', ['csrf' => CSRF::generate($this->config)]);
    }

    public function getAnalyticsData(): void
    {
        Auth::requireAuth();
        
        $period = $_GET['period'] ?? 'month';
        $start = $_GET['start'] ?? null;
        $end = $_GET['end'] ?? null;
        $classFilter = $_GET['class'] ?? null;
        $catFilter = $_GET['category'] ?? null;
        $techClassFilter = $_GET['classification'] ?? null;
        
        $db = \App\Core\DB::pdo();
        
        // Calculate Date Range
        $now = new \DateTime();
        $startDate = clone $now;
        $endDate = clone $now;
        $prevStartDate = clone $now;
        $prevEndDate = clone $now;
        
        switch ($period) {
            case 'today':
                $startDate->setTime(0, 0, 0);
                $prevStartDate->modify('-1 day')->setTime(0, 0, 0);
                $prevEndDate->modify('-1 day')->setTime(23, 59, 59);
                break;
            case 'week':
                $startDate->modify('-7 days')->setTime(0, 0, 0);
                $prevStartDate->modify('-14 days')->setTime(0, 0, 0);
                $prevEndDate->modify('-8 days')->setTime(23, 59, 59);
                break;
            case 'month':
                $startDate->modify('-30 days')->setTime(0, 0, 0);
                $prevStartDate->modify('-60 days')->setTime(0, 0, 0);
                $prevEndDate->modify('-31 days')->setTime(23, 59, 59);
                break;
            case 'year':
                $startDate->modify('-1 year')->setTime(0, 0, 0);
                $prevStartDate->modify('-2 years')->setTime(0, 0, 0);
                $prevEndDate->modify('-1 year')->setTime(23, 59, 59);
                break;
            case 'custom':
                if ($start) $startDate = new \DateTime($start);
                if ($end) $endDate = new \DateTime($end . ' 23:59:59');
                $diff = $startDate->diff($endDate);
                $prevEndDate = clone $startDate;
                $prevEndDate->modify('-1 second');
                $prevStartDate = clone $prevEndDate;
                $prevStartDate->sub($diff);
                break;
        }
        
        $fmtStart = $startDate->format('Y-m-d');
        $fmtEnd = $endDate->format('Y-m-d');
        $fmtPrevStart = $prevStartDate->format('Y-m-d');
        $fmtPrevEnd = $prevEndDate->format('Y-m-d');
        
        // Filter Helpers
        $buildWhere = function($alias = '') use ($classFilter, $catFilter, $techClassFilter) {
            $prefix = $alias ? $alias . '.' : '';
            // Use dataDecisao for timeline analysis as requested ("real evolution")
            $sql = " {$prefix}dataDecisao BETWEEN :s AND :e";
            
            if ($classFilter) $sql .= " AND {$prefix}siglaClasse = :class";
            if ($catFilter) $sql .= " AND {$prefix}category_id = :cat";
            
            if ($techClassFilter) {
                switch ($techClassFilter) {
                    case 'estrutural':
                        // Assuming ACOR and DTXT and related classes imply Structural
                        $sql .= " AND ({$prefix}siglaClasse IN ('ACO', 'ACOR', 'DTXT') OR {$prefix}category_id IN (SELECT id FROM categories WHERE name LIKE '%Estrutural%'))";
                        break;
                    case 'nao_estrutural':
                        $sql .= " AND ({$prefix}siglaClasse NOT IN ('ACO', 'ACOR', 'DTXT') AND ({$prefix}category_id IS NULL OR {$prefix}category_id NOT IN (SELECT id FROM categories WHERE name LIKE '%Estrutural%')))";
                        break;
                    case 'nao_estrutural_tecnicas':
                        // Complex case: Unstructured but with techniques. 
                        // Implementation: Search in ementa/decisao for keywords if no explicit field exists
                        $sql .= " AND ({$prefix}siglaClasse NOT IN ('ACO', 'ACOR', 'DTXT') AND ({$prefix}ementa LIKE '%técnicas estruturantes%' OR {$prefix}decisao LIKE '%medidas estruturantes%'))";
                        break;
                }
            }
            
            return $sql;
        };
        
        $bindParams = function($s, $e) use ($classFilter, $catFilter) {
            $p = [':s' => $s, ':e' => $e];
            if ($classFilter) $p[':class'] = $classFilter;
            if ($catFilter) $p[':cat'] = $catFilter;
            return $p;
        };
        
        // Helper for counts
        $getCount = function($s, $e) use ($db, $buildWhere, $bindParams) {
            $stmt = $db->prepare("SELECT COUNT(1) as c FROM records WHERE " . $buildWhere());
            $stmt->execute($bindParams($s, $e));
            return (int)$stmt->fetch(\PDO::FETCH_ASSOC)['c'];
        };
        
        $getCategorizedCount = function($s, $e) use ($db, $buildWhere, $bindParams) {
            $where = $buildWhere();
            $stmt = $db->prepare("SELECT COUNT(1) as c FROM records WHERE category_id IS NOT NULL AND $where");
            $stmt->execute($bindParams($s, $e));
            return (int)$stmt->fetch(\PDO::FETCH_ASSOC)['c'];
        };

        // KPI 1: Total Records Imported (Volume)
        $currentVolume = $getCount($fmtStart, $fmtEnd);
        $prevVolume = $getCount($fmtPrevStart, $fmtPrevEnd);
        $volumeChange = $prevVolume > 0 ? (($currentVolume - $prevVolume) / $prevVolume) * 100 : 0;
        
        // KPI 2: Categorization Rate (Conversion)
        $currentCat = $getCategorizedCount($fmtStart, $fmtEnd);
        $currentRate = $currentVolume > 0 ? ($currentCat / $currentVolume) * 100 : 0;
        $prevCat = $getCategorizedCount($fmtPrevStart, $fmtPrevEnd);
        $prevRate = $prevVolume > 0 ? ($prevCat / $prevVolume) * 100 : 0;
        $rateChange = $currentRate - $prevRate;
        
        // KPI 3: Active Categories (Diversity)
        $where = $buildWhere();
        $params = $bindParams($fmtStart, $fmtEnd);
        $sqlCatDiv = "SELECT COUNT(DISTINCT category_id) as c FROM records WHERE category_id IS NOT NULL AND $where";
        $stmt = $db->prepare($sqlCatDiv);
        $stmt->execute($params);
        $currentDiv = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['c'];
        
        // KPI 4: Decisions (Judgments)
        $stmt = $db->prepare("SELECT COUNT(1) as c FROM records WHERE dataDecisao IS NOT NULL AND $where");
        $stmt->execute($params);
        $currentDecisions = (int)$stmt->fetch(\PDO::FETCH_ASSOC)['c'];

        // Chart 1: Trend (Line) - Daily Volume
        // Use dataDecisao for "Real Evolution"
        $sqlTrend = "SELECT dataDecisao as date, COUNT(1) as count FROM records WHERE $where GROUP BY date ORDER BY date";
        $stmt = $db->prepare($sqlTrend);
        $stmt->execute($params);
        $trendData = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Fill gaps for trend
        $labels = [];
        $values = [];
        $interval = new \DateInterval('P1D');
        // Ensure start/end are valid DateTime objects
        $pStart = new \DateTime($fmtStart);
        $pEnd = new \DateTime($fmtEnd);
        $periodRange = new \DatePeriod($pStart, $interval, $pEnd);
        $trendMap = array_column($trendData, 'count', 'date');
        
        foreach ($periodRange as $dt) {
            $d = $dt->format('Y-m-d');
            $labels[] = $d;
            $values[] = $trendMap[$d] ?? 0;
        }

        // Chart 2: Top Categories (Bar)
        // Note: If filtering by specific category, this chart will show only that category (1 bar)
        $whereR = $buildWhere('r');
        $sqlTopCats = "SELECT c.name, COUNT(r.id) as count FROM records r JOIN categories c ON r.category_id = c.id WHERE $whereR GROUP BY c.name ORDER BY count DESC LIMIT 5";
        $stmt = $db->prepare($sqlTopCats);
        $stmt->execute($params);
        $topCats = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Chart 3: Status Distribution (Doughnut)
        $pending = $currentVolume - $currentCat;
        $distribution = [
            ['label' => 'Categorizado', 'value' => $currentCat],
            ['label' => 'Pendente', 'value' => $pending]
        ];

        // Table: Recent Records
        $sqlRecent = "SELECT r.numeroProcesso, r.siglaClasse, r.dataDecisao, c.name as category FROM records r LEFT JOIN categories c ON r.category_id = c.id WHERE $whereR ORDER BY r.created_at DESC LIMIT 10";
        $stmt = $db->prepare($sqlRecent);
        $stmt->execute($params);
        $recentRecords = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $response = [
            'kpi' => [
                'volume' => ['value' => $currentVolume, 'change' => round($volumeChange, 1)],
                'rate' => ['value' => round($currentRate, 1), 'change' => round($rateChange, 1)],
                'diversity' => ['value' => $currentDiv, 'change' => 0],
                'decisions' => ['value' => $currentDecisions, 'change' => 0]
            ],
            'charts' => [
                'trend' => ['labels' => $labels, 'data' => $values],
                'categories' => $topCats,
                'distribution' => $distribution
            ],
            'table' => $recentRecords,
            'debug' => ['start' => $fmtStart, 'end' => $fmtEnd]
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
    }

    public function import(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) {
            http_response_code(400);
            echo 'csrf';
            return;
        }
        $path = $this->config['import']['json_path'];
        $count = 0;
        foreach (JSONStream::iterateObjects($path) as $obj) {
            Record::upsert($obj);
            $count++;
        }

        // Auto-correction for Órgão Julgador
        $updater = new RecordUpdater($this->config);
        $fixStats = $updater->fixOrgaoJulgador('PRIMEIRA SEÇÃO');
        $remaining = $updater->validateCompleteness('PRIMEIRA SEÇÃO');
        
        $msg = "Imported $count records. Fixed Organs: {$fixStats['updated']}. Remaining invalid: $remaining.";

        $cats = Category::all();
        $this->view('admin/dashboard', ['message' => $msg, 'csrf' => CSRF::generate($this->config), 'categories' => $cats]);
    }

    public function upload(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) { http_response_code(400); echo 'csrf'; return; }
        if (!isset($_FILES['json']) || (int)$_FILES['json']['error'] !== 0) {
            $cats = Category::all();
            $this->view('admin/dashboard', ['error' => 'upload', 'csrf' => CSRF::generate($this->config), 'categories' => $cats]);
            return;
        }
        $tmp = $_FILES['json']['tmp_name'];
        $tab = $_POST['tab'] ?? '';
        $tabMap = [
            'acor' => 'ACO',
            'dtxt' => 'DTXT',
            'acp' => 'ACP',
            'ap' => 'AP', // or POP, but AP is standard sigla
            'msc' => 'MSC'
        ];
        $forcedClass = $tabMap[$tab] ?? null;

        $count = 0;
        foreach (JSONStream::iterateObjects($tmp) as $obj) { 
            if ($forcedClass) {
                $obj['siglaClasse'] = $forcedClass;
                // Also update verbose class name if needed, but sigla is the key
                if (!isset($obj['classe'])) $obj['classe'] = $forcedClass;
            }
            Record::upsert($obj); 
            $count++; 
        }

        // Auto-correction for Órgão Julgador
        $updater = new RecordUpdater($this->config);
        $fixStats = $updater->fixOrgaoJulgador('PRIMEIRA SEÇÃO');
        $remaining = $updater->validateCompleteness('PRIMEIRA SEÇÃO');
        
        $msg = "Uploaded $count records";
        if ($forcedClass) $msg .= " (Tab: $tab -> Class: $forcedClass)";
        $msg .= ". Fixed Organs: {$fixStats['updated']}. Remaining invalid: $remaining.";

        $cats = Category::all();
        $this->view('admin/dashboard', ['message' => $msg, 'csrf' => CSRF::generate($this->config), 'categories' => $cats]);
    }

    public function createCategory(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) { http_response_code(400); echo 'csrf'; return; }
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name !== '') { Category::create($name); }
        $cats = Category::all();
        $this->view('admin/dashboard', ['csrf' => CSRF::generate($this->config), 'categories' => $cats]);
    }

    public function assignCategory(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) { http_response_code(400); echo 'csrf'; return; }
        $recordId = (string)($_POST['record_id'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        if ($recordId !== '' && $categoryId > 0) { Record::assignCategory($recordId, $categoryId); }
        $cats = Category::all();
        $this->view('admin/dashboard', ['csrf' => CSRF::generate($this->config), 'categories' => $cats]);
    }

    public function dashboardData(): void
    {
        Auth::requireAuth();
        $filters = [
            'type' => isset($_GET['type']) ? (string)$_GET['type'] : null,
            'status' => isset($_GET['status']) ? (string)$_GET['status'] : null,
            'start' => isset($_GET['start']) ? (string)$_GET['start'] : null,
            'end' => isset($_GET['end']) ? (string)$_GET['end'] : null,
        ];
        $stats = Record::stats($filters);
        $byCat = Record::byCategory($filters);
        $timeline = Record::timeline($filters);
        header('Content-Type: application/json');
        echo json_encode(['stats' => $stats, 'categories' => $byCat, 'timeline' => $timeline]);
    }

    public function exportCsv(): void
    {
        Auth::requireAuth();
        $filters = [
            'type' => isset($_GET['type']) ? (string)$_GET['type'] : null,
            'status' => isset($_GET['status']) ? (string)$_GET['status'] : null,
            'start' => isset($_GET['start']) ? (string)$_GET['start'] : null,
            'end' => isset($_GET['end']) ? (string)$_GET['end'] : null,
        ];
        $stats = Record::stats($filters);
        $byCat = Record::byCategory($filters);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="dashboard.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Total', 'Categorizados', 'Pendentes', 'Percentual']);
        fputcsv($out, [$stats['total'], $stats['categorized'], $stats['pending'], $stats['percentage']]);
        fputcsv($out, []);
        fputcsv($out, ['Categoria', 'Quantidade']);
        foreach ($byCat as $row) fputcsv($out, [$row['category'], $row['count']]);
        fclose($out);
    }

    public function exportPdf(): void
    {
        Auth::requireAuth();
        $filters = [
            'type' => isset($_GET['type']) ? (string)$_GET['type'] : null,
            'status' => isset($_GET['status']) ? (string)$_GET['status'] : null,
            'start' => isset($_GET['start']) ? (string)$_GET['start'] : null,
            'end' => isset($_GET['end']) ? (string)$_GET['end'] : null,
        ];
        $stats = Record::stats($filters);
        $byCat = Record::byCategory($filters);
        $content = "Dashboard\nTotal: {$stats['total']}\nCategorizados: {$stats['categorized']} ({$stats['percentage']}%)\nPendentes: {$stats['pending']}\n\nCategorias:\n";
        foreach ($byCat as $row) { $content .= ($row['category'] ?: '-') . ': ' . $row['count'] . "\n"; }
        $pdf = self::simplePdf($content);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="dashboard.pdf"');
        echo $pdf;
    }

    public function classifyAI(): void
    {
        Auth::requireAuth();
        Auth::requireRole('admin');
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) {
            http_response_code(400);
            echo 'csrf';
            return;
        }
        $path = $this->config['import']['json_path'];
        $batchSize = (int)($this->config['import']['batch_size'] ?? 20);
        if ($batchSize < 1) $batchSize = 20;
        $client = new AIClient($this->config);
        $total = 0;
        $classified = 0;
        $errors = [];
        $batch = [];
        $user = Auth::user();
        try {
            foreach (JSONStream::iterateObjects($path) as $obj) {
                if (!isset($obj['id'])) continue;
                $batch[] = $obj;
                $total++;
                if (count($batch) >= $batchSize) {
                    $classified += $this->handleAIBatch($client, $batch, (int)$user['id']);
                    $batch = [];
                }
            }
            if ($batch) {
                $classified += $this->handleAIBatch($client, $batch, (int)$user['id']);
            }
        } catch (\RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
        $cats = Category::all();
        $msg = 'Analisados: ' . $total . ' | Classificados: ' . $classified;
        if ($errors) $msg .= ' | Erros: ' . implode('; ', $errors);
        $this->view('admin/dashboard', ['message' => $msg, 'csrf' => CSRF::generate($this->config), 'categories' => $cats]);
    }

    private function handleAIBatch(AIClient $client, array $batch, int $userId): int
    {
        $userKey = UserApiKey::getActive($userId);
        if ($userKey) {
            $plain = UserApiKey::decrypt($userKey['enc_key']);
            $res = $client->categorizeBatchWithKey($batch, $userKey['provider'], $plain);
        } else {
            $res = $client->categorizeBatch($batch);
        }
        $count = 0;
        foreach ($batch as $obj) {
            $id = (string)$obj['id'];
            if (!isset($res[$id])) continue;
            $r = $res[$id];
            $label = (string)($r['category'] ?? '');
            if ($label === '') continue;
            $catId = Category::findOrCreateByName($label);
            Record::assignCategory($id, $catId);
            Record::setAIMetadata($id, $label, (float)($r['confidence'] ?? 0), $r['metadata'] ?? null);
            Audit::log($userId, 'ai_classify', $id, json_encode($r, JSON_UNESCAPED_UNICODE));
            $count++;
        }
        return $count;
    }

    private static function simplePdf(string $text): string
    {
        $lines = explode("\n", $text);
        $content = "";
        $y = 800;
        foreach ($lines as $line) {
            $safe = str_replace(['(',')','\\'], ['\\(','\\)','\\\\'], $line);
            $content .= "BT /F1 12 Tf 50 $y Td ($safe) Tj ET\n";
            $y -= 18;
        }
        $objects = [];
        $objects[] = [1, "<< /Type /Catalog /Pages 2 0 R >>\n"]; 
        $objects[] = [2, "<< /Type /Pages /Kids [3 0 R] /Count 1 >>\n"]; 
        $objects[] = [3, "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\n"]; 
        $objects[] = [4, "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\n"]; 
        $stream = "q\n$content Q\n";
        $objects[] = [5, "<< /Length " . strlen($stream) . " >>\nstream\n$stream\nendstream\n"]; 
        $pdf = "%PDF-1.4\n";
        $xref = [];
        $offsets = [];
        foreach ($objects as [$num, $obj]) {
            $offsets[$num] = strlen($pdf);
            $pdf .= "$num 0 obj\n$obj" . "endobj\n";
        }
        $startxref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects)+1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($objects as [$num, $obj]) {
            $pdf .= str_pad((string)$offsets[$num], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        $pdf .= "trailer << /Size " . (count($objects)+1) . " /Root 1 0 R >>\nstartxref\n$startxref\n%%EOF";
        return $pdf;
    }
}
