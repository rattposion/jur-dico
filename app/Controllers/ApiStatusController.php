<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\DB;
use App\Core\AIClient;
use App\Core\DataJudClient;

class ApiStatusController extends Controller
{
    public function check(): void
    {
        Auth::requireAuth();
        header('Content-Type: application/json');

        $status = [
            'db' => false,
            'ai' => false,
            'datajud' => false,
            'details' => []
        ];

        // 1. Check DB
        try {
            DB::pdo()->query("SELECT 1");
            $status['db'] = true;
        } catch (\Exception $e) {
            $status['details']['db'] = $e->getMessage();
        }

        // 2. Check AI (Gemini/OpenAI)
        // Simple heuristic: Check if Key is present. 
        // Real check would involve a minimal API call, but let's avoid cost/latency for status ping unless cached.
        // We will just check if ENV keys are set or UserApiKey exists.
        $hasEnvKey = getenv('OPENAI_API_KEY') || getenv('GEMINI_API_KEY');
        if ($hasEnvKey) {
            $status['ai'] = true;
        } else {
            // Check user key
            // Ideally we should check if *current* user has key, but for system status we might want global availability
            // For now, let's assume if env is missing, it's false unless user provides one. 
            // We will mark as 'partial' or 'user-dependent' in real app, here boolean.
            $status['ai'] = false; // Pending user key check in frontend or deep check
        }

        // 3. Check DataJud (Public API)
        // Simple HEAD request or similar to DataJud endpoint
        $djUrl = 'https://api-publica.datajud.cnj.jus.br/api_publica_stj/_search';
        if ($this->ping($djUrl)) {
            $status['datajud'] = true;
        }

        echo json_encode($status);
    }

    private function ping(string $url): bool
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code >= 200 && $code < 500;
    }
}
