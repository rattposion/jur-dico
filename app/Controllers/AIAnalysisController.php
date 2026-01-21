<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\View;
use App\Core\Auth;
use App\Core\CSRF;
use App\Models\AIAnalysis;

class AIAnalysisController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $this->view('ai/analysis', [
            'csrf' => CSRF::generate($this->config),
            'user' => Auth::user()
        ]);
    }

    public function report(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');
        
        try {
            $stats = AIAnalysis::getGlobalStats();
            $problems = AIAnalysis::getProblematicProcesses();
            
            echo json_encode([
                'ok' => true,
                'stats' => $stats,
                'problems' => $problems
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function details(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');
        
        $id = $_GET['id'] ?? '';
        if (!$id) {
            echo json_encode(['ok' => false, 'error' => 'ID required']);
            return;
        }

        $data = AIAnalysis::getProcessDetails($id);
        if ($data) {
            echo json_encode(['ok' => true, 'data' => $data]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Not found']);
        }
    }
}
