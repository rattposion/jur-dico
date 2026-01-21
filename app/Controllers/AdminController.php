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
        $count = 0;
        foreach (JSONStream::iterateObjects($tmp) as $obj) { Record::upsert($obj); $count++; }

        // Auto-correction for Órgão Julgador
        $updater = new RecordUpdater($this->config);
        $fixStats = $updater->fixOrgaoJulgador('PRIMEIRA SEÇÃO');
        $remaining = $updater->validateCompleteness('PRIMEIRA SEÇÃO');
        
        $msg = "Uploaded $count records. Fixed Organs: {$fixStats['updated']}. Remaining invalid: $remaining.";

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
