<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Models\UserApiKey;
use App\Models\Audit;
use App\Core\AIClient;

class ApiKeysController extends Controller
{
    private const MODELS = [
        'openai' => [
            'gpt-4o' => ['label' => 'GPT-4o (Recomendado)', 'type' => 'paid'],
            'gpt-4o-mini' => ['label' => 'GPT-4o Mini (Mais Econômico)', 'type' => 'paid'],
            'gpt-4-turbo' => ['label' => 'GPT-4 Turbo', 'type' => 'paid'],
            'gpt-3.5-turbo' => ['label' => 'GPT-3.5 Turbo', 'type' => 'paid'],
        ],
        'gemini' => [
            'gemini-2.5-flash' => ['label' => 'Gemini 2.5 Flash (Recomendado)', 'type' => 'free_tier'],
            'gemini-2.5-pro' => ['label' => 'Gemini 2.5 Pro', 'type' => 'free_tier'],
            'gemini-2.0-flash' => ['label' => 'Gemini 2.0 Flash', 'type' => 'free_tier'],
            'gemini-3-pro-preview' => ['label' => 'Gemini 3 Pro Preview (Experimental)', 'type' => 'free_tier'],
        ]
    ];

    private function validate(string $provider, string $key): bool
    {
        $key = trim($key);
        if (strlen($key) < 8) return false; // Minimum length check
        
        if ($provider === 'openai') {
            // OpenAI keys usually start with sk- but project keys might differ.
            // Just check if it looks like a key (no spaces, basic chars).
            // Allowing a broader set of characters for future compatibility.
            return (bool)preg_match('/^sk-[A-Za-z0-9_\-\.]+/', $key) || strpos($key, 'sk-') === 0;
        }
        
        if ($provider === 'gemini') {
            // Gemini keys are usually alphanumeric.
            return (bool)preg_match('/^[A-Za-z0-9_\-\.]+$/', $key);
        }
        
        return false;
    }

    public function index(): void
    {
        Auth::requireAuth();
        $u = Auth::user();
        $rows = UserApiKey::allByUser((int)$u['id']);
        $list = [];
        foreach ($rows as $r) {
            $plain = UserApiKey::decrypt($r['enc_key']);
            $list[] = [
                'provider' => $r['provider'],
                'masked' => UserApiKey::mask($plain),
                'model' => $r['model'] ?? null,
                'active' => (int)$r['active'] === 1,
                'created_at' => $r['created_at'],
                'updated_at' => $r['updated_at'],
            ];
        }
        Audit::log((int)$u['id'], 'api_keys_view', null, null);
        $this->view('settings/api_keys', ['keys' => $list, 'csrf' => CSRF::generate($this->config)]);
    }

    public function save(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) { http_response_code(400); echo 'csrf'; return; }
        $provider = ($_POST['provider'] ?? '') === 'gemini' ? 'gemini' : 'openai';
        $key = trim((string)($_POST['key'] ?? ''));
        $model = trim((string)($_POST['model'] ?? ''));
        
        $validModels = array_keys(self::MODELS[$provider] ?? []);
        if (!in_array($model, $validModels)) {
            // Default to first model if invalid or empty
            $model = $validModels[0] ?? '';
        }

        if (!$this->validate($provider, $key)) { http_response_code(400); echo 'invalid'; return; }
        $u = Auth::user();
        $enc = UserApiKey::encrypt($key);
        UserApiKey::save((int)$u['id'], $provider, $enc, $model);
        UserApiKey::setActive((int)$u['id'], $provider);
        Audit::log((int)$u['id'], 'api_keys_save', null, "$provider:$model");
        header('Location: /settings/api-keys');
    }

    public function test(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) { http_response_code(400); echo json_encode(['ok'=>false, 'msg'=>'Erro de segurança (CSRF)']); return; }
        $provider = ($_POST['provider'] ?? '') === 'gemini' ? 'gemini' : 'openai';
        $key = trim((string)($_POST['key'] ?? ''));
        $model = trim((string)($_POST['model'] ?? ''));

        if (!$this->validate($provider, $key)) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Formato de chave inválido']); return; }
        
        $config = ['ai' => ['provider' => $provider]];
        if ($model) {
            if ($provider === 'gemini') $config['ai']['gemini_model'] = $model;
            if ($provider === 'openai') $config['ai']['openai_model'] = $model;
        }

        $client = new AIClient($config);
        try {
            $items = [['id'=>'t','ementa'=>'Teste jurídico de classificação','decisao'=>'']];
            $res = $client->categorizeBatchWithKey($items, $provider, $key);
            echo json_encode(['ok'=>true,'category'=>current($res)['category'] ?? '']);
        } catch (\Throwable $e) {
            echo json_encode(['ok'=>false, 'msg' => 'Erro na API: ' . $e->getMessage()]);
        }
    }

    public function activate(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) { http_response_code(400); echo 'csrf'; return; }
        $provider = ($_POST['provider'] ?? '') === 'gemini' ? 'gemini' : 'openai';
        $u = Auth::user();
        UserApiKey::setActive((int)$u['id'], $provider);
        Audit::log((int)$u['id'], 'api_keys_activate', null, $provider);
        header('Location: /settings/api-keys');
    }

    public function delete(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) { http_response_code(400); echo 'csrf'; return; }
        $provider = ($_POST['provider'] ?? '') === 'gemini' ? 'gemini' : 'openai';
        $u = Auth::user();
        UserApiKey::delete((int)$u['id'], $provider);
        Audit::log((int)$u['id'], 'api_keys_delete', null, $provider);
        header('Location: /settings/api-keys');
    }
}

