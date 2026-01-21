<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Record;
use App\Core\DB;
use App\Core\Auth;

class BulkOpsController extends Controller
{
    public function index(): void
    {
        // Only admin
        $user = Auth::user();
        if (!$user || $user['role'] !== 'admin') {
            header('Location: /login');
            exit;
        }

        $this->view('admin/bulk_ops', [
            'user' => $user
        ]);
    }

    public function preview(): void
    {
        // Only admin
        $user = Auth::user();
        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $start = (string)($data['start'] ?? '');
        $end = (string)($data['end'] ?? '');

        if (!$start || !$end) {
            http_response_code(400);
            echo json_encode(['error' => 'Anos inválidos']);
            exit;
        }

        try {
            // Count records before start year (to be deleted)
            $countDeleteOld = Record::countBeforeYear($start);
            
            // Count records after end year (to be deleted)
            $countDeleteNew = Record::countAfterYear($end);

            // Count records in range (to be kept)
            $countKeep = Record::countBetweenYears($start, $end);

            echo json_encode([
                'success' => true,
                'delete_old_count' => $countDeleteOld,
                'delete_new_count' => $countDeleteNew,
                'keep_count' => $countKeep,
                'message' => "Operação: Manter apenas registros entre $start e $end.\nSerão excluídos: $countDeleteOld (Anteriores) e $countDeleteNew (Posteriores).\nSerão mantidos: $countKeep."
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function execute(): void
    {
        // Only admin
        $user = Auth::user();
        if (!$user || $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $start = (string)($data['start'] ?? '');
        $end = (string)($data['end'] ?? '');

        if (!$start || !$end) {
            http_response_code(400);
            echo json_encode(['error' => 'Anos inválidos']);
            exit;
        }

        try {
            DB::pdo()->beginTransaction();

            // 1. Delete before start year
            $deletedOld = Record::deleteBeforeYear($start);

            // 2. Delete after end year
            $deletedNew = Record::deleteAfterYear($end);

            // 3. Log Audit
            $logStmt = DB::pdo()->prepare("INSERT INTO audit_logs (user_id, action, details, created_at) VALUES (:uid, :act, :det, :cat)");
            $logStmt->execute([
                ':uid' => $user['id'],
                ':act' => 'bulk_delete_retention',
                ':det' => json_encode([
                    'start_year' => $start, 
                    'end_year' => $end, 
                    'deleted_old' => $deletedOld,
                    'deleted_new' => $deletedNew
                ]),
                ':cat' => date('c')
            ]);

            DB::pdo()->commit();

            echo json_encode([
                'success' => true,
                'deleted_old' => $deletedOld,
                'deleted_new' => $deletedNew
            ]);

        } catch (\Exception $e) {
            DB::pdo()->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Erro na transação: ' . $e->getMessage()]);
        }
    }
}
