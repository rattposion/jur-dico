<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Record;
use App\Core\View;
use App\Models\Category;
use App\Models\SavedSearch;
use App\Models\SearchHistory;
use App\Core\CSRF;
use App\Core\Auth;

class RecordsController extends Controller
{
    private function getParams(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : null;
        $filters = [
            'type' => isset($_GET['type']) ? trim((string)$_GET['type']) : null,
            'relator' => isset($_GET['relator']) ? trim((string)$_GET['relator']) : null,
            'start' => isset($_GET['start']) ? trim((string)$_GET['start']) : null,
            'end' => isset($_GET['end']) ? trim((string)$_GET['end']) : null,
            'category' => isset($_GET['category']) ? (string)$_GET['category'] : null,
            // Advanced fields
            'tribunal' => isset($_GET['tribunal']) ? trim((string)$_GET['tribunal']) : null,
            'juiz' => isset($_GET['juiz']) ? trim((string)$_GET['juiz']) : null,
            'numero' => isset($_GET['numero']) ? trim((string)$_GET['numero']) : null,
            'ano' => isset($_GET['ano']) ? trim((string)$_GET['ano']) : null,
        ];
        $sort = isset($_GET['sort']) ? trim((string)$_GET['sort']) : 'dataDecisao';
        $dir = isset($_GET['dir']) ? trim((string)$_GET['dir']) : 'DESC';

        return [$page, $q, $filters, $sort, $dir];
    }

    public function index(): void
    {
        [$page, $q, $filters, $sort, $dir] = $this->getParams();
        
        // Log history if user is logged in and search is performed
        $user = Auth::user();
        if ($user && ($q || !empty(array_filter($filters)))) {
            SearchHistory::log((int)$user['id'], $q, $filters);
        }

        $res = Record::paginate($page, 20, $q, $filters, $sort, $dir);
        $cats = Category::all();
        $this->view('records/list', [
            'items' => $res['items'], 
            'total' => $res['total'], 
            'page' => $page, 
            'q' => $q, 
            'filters' => $filters, 
            'categories' => $cats, 
            'csrf' => CSRF::generate($this->config), 
            'user' => $user,
            'sort' => $sort,
            'dir' => $dir
        ]);
    }

    public function advanced(): void
    {
        $user = Auth::user();
        [$page, $q, $filters, $sort, $dir] = $this->getParams();

        // Perform search if any param is set (even empty search to show all, or just show form)
        // Usually advanced search page starts empty.
        // If GET params are present, we search.
        $hasSearch = $q || !empty(array_filter($filters));
        
        $items = null;
        $total = 0;

        if ($hasSearch) {
            if ($user) {
                SearchHistory::log((int)$user['id'], $q, $filters);
            }
            $res = Record::paginate($page, 20, $q, $filters, $sort, $dir);
            $items = $res['items'];
            $total = $res['total'];
        }

        $cats = Category::all();
        $this->view('records/advanced', [
            'categories' => $cats, 
            'csrf' => CSRF::generate($this->config),
            'user' => $user,
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'q' => $q,
            'filters' => $filters,
            'sort' => $sort,
            'dir' => $dir
        ]);
    }

    public function autocomplete(): void
    {
        $term = $_GET['term'] ?? '';
        $results = Record::autocomplete($term);
        header('Content-Type: application/json');
        echo json_encode($results);
    }

    public function saveSearch(): void
    {
        Auth::requireAuth();
        $user = Auth::user();
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['name']) || empty($data['criteria'])) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Dados inválidos']);
            return;
        }

        $ok = SavedSearch::create((int)$user['id'], $data['name'], $data['criteria']);
        echo json_encode(['ok' => $ok]);
    }

    public function getSavedSearches(): void
    {
        Auth::requireAuth();
        $user = Auth::user();
        $list = SavedSearch::listByUser((int)$user['id']);
        header('Content-Type: application/json');
        echo json_encode($list);
    }
    
    public function deleteSavedSearch(): void
    {
        Auth::requireAuth();
        $user = Auth::user();
        $id = $_POST['id'] ?? '';
        $ok = SavedSearch::delete($id, (int)$user['id']);
        echo json_encode(['ok' => $ok]);
    }

    public function getHistory(): void
    {
        Auth::requireAuth();
        $user = Auth::user();
        $list = SearchHistory::getRecent((int)$user['id']);
        header('Content-Type: application/json');
        echo json_encode($list);
    }

    public function exportCsv(): void
    {
        $this->export('csv');
    }

    public function exportPdf(): void
    {
        $this->export('pdf');
    }

    private function export(string $format): void
    {
        [$page, $q, $filters, $sort, $dir] = $this->getParams();
        
        // Get all results (limit 1000 for safety)
        $res = Record::paginate(1, 1000, $q, $filters, $sort, $dir);
        $items = $res['items'];

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=registros.csv');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Processo', 'Classe', 'Relator', 'Órgão', 'Data Decisão', 'Categoria']);
            foreach ($items as $r) {
                fputcsv($out, [
                    $r['id'],
                    $r['numeroProcesso'],
                    $r['siglaClasse'],
                    $r['ministroRelator'],
                    $r['nomeOrgaoJulgador'],
                    $r['dataDecisao'],
                    $r['category'] ?? 'N/A'
                ]);
            }
            fclose($out);
        } elseif ($format === 'pdf') {
            // Simple HTML for Print
            $html = '<html><head><style>
                table { width: 100%; border-collapse: collapse; font-family: sans-serif; font-size: 12px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                h1 { font-family: sans-serif; }
            </style></head><body onload="window.print()">
            <h1>Relatório de Processos</h1>
            <table>
                <thead>
                    <tr>
                        <th>Processo</th>
                        <th>Classe</th>
                        <th>Relator</th>
                        <th>Órgão</th>
                        <th>Data</th>
                        <th>Categoria</th>
                    </tr>
                </thead>
                <tbody>';
            foreach ($items as $r) {
                $html .= "<tr>
                    <td>{$r['numeroProcesso']}</td>
                    <td>{$r['siglaClasse']}</td>
                    <td>{$r['ministroRelator']}</td>
                    <td>{$r['nomeOrgaoJulgador']}</td>
                    <td>{$r['dataDecisao']}</td>
                    <td>" . ($r['category'] ?? 'N/A') . "</td>
                </tr>";
            }
            $html .= '</tbody></table></body></html>';
            echo $html;
        }
    }

    public function show(): void
    {
        $id = (string)($_GET['id'] ?? '');
        $record = $id ? Record::find($id) : null;
        if (!$record) {
            http_response_code(404);
            echo '404';
            return;
        }
        $this->view('records/view', ['r' => $record, 'e' => [View::e('ementa'), View::e('decisao')]]);
    }

    public function updateCategory(): void
    {
        Auth::requireAuth();
        Auth::requireRole('admin');
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'csrf']); return; }
        $recordId = (string)($_POST['record_id'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        if ($recordId === '' || $categoryId <= 0 || !Category::find($categoryId)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'dados']); return; }
        $ok = Record::assignCategory($recordId, $categoryId);
        if ($ok) { 
            $u = Auth::user();
            \App\Models\Audit::log((int)$u['id'], 'assign_category', $recordId, (string)$categoryId);
            header('Content-Type: application/json');
            echo json_encode(['ok'=>true]);
        } else { http_response_code(500); echo json_encode(['ok'=>false]); }
    }

    public function delete(): void
    {
        Auth::requireAuth();
        Auth::requireRole('admin');
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'csrf']); return; }
        $recordId = (string)($_POST['record_id'] ?? '');
        if ($recordId === '') { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'dados']); return; }
        $ok = Record::delete($recordId);
        if ($ok) {
            $u = Auth::user();
            \App\Models\Audit::log((int)$u['id'], 'delete_record', $recordId, null);
            header('Content-Type: application/json');
            echo json_encode(['ok'=>true]);
        } else { http_response_code(404); echo json_encode(['ok'=>false]); }
    }
}
