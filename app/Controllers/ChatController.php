<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\CSRF;
use App\Core\AIClient;
use App\Core\DataJudClient;
use App\Models\Chat;
use App\Models\Audit;
use App\Models\UserApiKey;
use App\Models\AIAnalysis;

class ChatController extends Controller
{
    public function index(): void
    {
        Auth::requireAuth();
        $u = Auth::user();
        
        $convId = isset($_GET['id']) ? (int)$_GET['id'] : null;
        $conv = null;
        
        if ($convId) {
            $conv = Chat::get($convId, (int)$u['id']);
        }
        
        if (!$conv) {
            $conv = Chat::getOrCreateForUser((int)$u['id']);
        }
        
        $conversations = Chat::getAllForUser((int)$u['id']);
        $messages = Chat::messages((int)$conv['id']);
        $aiOk = (getenv('OPENAI_API_KEY') || getenv('GEMINI_API_KEY')) ? true : false;
        
        $this->view('chat/index', [
            'conversation' => $conv, 
            'conversations' => $conversations,
            'messages' => $messages, 
            'csrf' => CSRF::generate($this->config), 
            'ai_ok' => $aiOk
        ]);
    }

    public function create(): void
    {
        Auth::requireAuth();
        $u = Auth::user();
        $id = Chat::create((int)$u['id'], 'Novo Chat');
        header('Location: /chat?id=' . $id);
        exit;
    }

    public function messages(): void
    {
        Auth::requireAuth();
        $u = Auth::user();
        
        $convId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : null;
        if ($convId) {
            $conv = Chat::get($convId, (int)$u['id']);
        } else {
            $conv = Chat::getOrCreateForUser((int)$u['id']);
        }
        
        if (!$conv) { http_response_code(404); echo json_encode(['items'=>[]]); return; }

        $msgs = Chat::messages((int)$conv['id']);
        header('Content-Type: application/json');
        echo json_encode(['items' => $msgs]);
    }

    public function send(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) { http_response_code(400); echo json_encode(['ok'=>false]); return; }
        $u = Auth::user();
        
        $convId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : null;
        if ($convId) {
            $conv = Chat::get($convId, (int)$u['id']);
        } else {
            $conv = Chat::getOrCreateForUser((int)$u['id']);
        }
        
        if (!$conv) { http_response_code(404); echo json_encode(['ok'=>false]); return; }

        $kind = $_POST['kind'] ?? 'question';
        $text = isset($_POST['text']) ? trim((string)$_POST['text']) : null;
        $module = isset($_POST['module']) ? trim((string)$_POST['module']) : 'analysis';
        $modelReq = isset($_POST['model']) ? trim((string)$_POST['model']) : null;
        $providerReq = isset($_POST['provider']) ? trim((string)$_POST['provider']) : null;

        $mid = Chat::sendMessage((int)$conv['id'], (int)$u['id'], $kind, $text);
        Audit::log((int)$u['id'], 'chat_send', null, json_encode(['kind'=>$kind, 'cid'=>$conv['id'], 'module'=>$module, 'model'=>$modelReq], JSON_UNESCAPED_UNICODE));
        
        if ($kind === 'question' && $text) {
            try {
                $client = new AIClient($this->config);
                
                // Determine Key and Model to use
                $userKey = null;
                if ($providerReq) {
                    // If provider is explicit, try to get specific key
                    $userKey = UserApiKey::getByProvider((int)$u['id'], $providerReq);
                }
                
                // Fallback to active key if explicit provider not found or not requested
                if (!$userKey) {
                    $userKey = UserApiKey::getActive((int)$u['id']);
                }

                // ... context enrichment ...
                $contextAddon = "";

                // INTENT: List/Search Processes (Local DB)
                // Trigger: "listar processos", "quais processos", "me mostre os processos", etc.
                if (preg_match('/(listar|lista|quais|me mostre|ver|todos).*(processos|casos)/i', $text) && stripos($text, 'datajud') === false) {
                     // Basic heuristic for status filter
                     $filterStatus = null;
                     if (stripos($text, 'pendente') !== false) $filterStatus = 'pending';
                     if (stripos($text, 'categorizado') !== false) $filterStatus = 'categorized';

                     // Fetch records (Limit 5 for token safety)
                     $listResult = \App\Models\Record::paginate(1, 5, null, ['status' => $filterStatus]);
                     $total = $listResult['total'] ?? 0;
                     $items = $listResult['items'] ?? [];

                     if (count($items) > 0) {
                         $contextAddon .= "\n\n[SISTEMA: O usuário solicitou uma lista de processos LOCAIS. Abaixo estão os 5 mais recentes encontrados no banco de dados (Total Geral: $total)]\n";
                         foreach ($items as $rec) {
                             $dt = isset($rec['dataDecisao']) ? date('d/m/Y', strtotime($rec['dataDecisao'])) : 'N/A';
                             $cat = $rec['category'] ?? 'Sem categoria';
                             $contextAddon .= "- ID: {$rec['id']} | Proc: {$rec['numeroProcesso']} | Data: $dt | Cat: $cat | Classe: {$rec['siglaClasse']}\n";
                         }
                         $contextAddon .= "[Aviso do Sistema: Exibição limitada aos 5 registros mais recentes. Instrua o usuário a usar o painel 'Processos' para ver a lista completa.]\n";
                     } else {
                         $contextAddon .= "\n\n[SISTEMA: O usuário solicitou uma lista, mas nenhum processo foi encontrado no banco de dados local.]\n";
                     }
                }

                // INTENT: DataJud General Search (By Name/Party/Keyword)
                // Trigger: "buscar processo de Fulano", "consultar datajud sobre X", "pesquisar na api X"
                if (preg_match('/(consultar|buscar|pesquisar).*(datajud|api|externo|tribunal)/i', $text) || 
                    preg_match('/(buscar|pesquisar)\s+processo\s+(de|do|da|contra)\s+(.+)/i', $text)) {
                    
                    $query = $text;
                    // Extract meaningful query part
                    if (preg_match('/(de|do|da|contra|sobre)\s+(.+)$/i', $text, $m)) {
                        $query = trim($m[2]);
                    } else {
                        $query = preg_replace('/(consultar|buscar|pesquisar|no|na|o|a|os|as|datajud|api|externo|tribunal|processo|processos)/i', '', $text);
                        $query = trim($query);
                    }
                    
                    if (strlen($query) > 2) {
                        try {
                            $djClient = new DataJudClient($this->config);
                            $djResults = $djClient->searchByQuery($query);
                            
                            if (!empty($djResults)) {
                                $contextAddon .= "\n\n[SISTEMA: Resultados da Busca Pública DataJud para '$query' (Fonte: API DataJud/CNJ)]\n";
                                $count = 0;
                                foreach ($djResults as $hit) {
                                    if ($count++ >= 3) break; // Limit to 3 results
                                    $source = $hit['_source'] ?? [];
                                    $npu = $source['numeroProcesso'] ?? 'N/A';
                                    $classe = $source['classe']['nome'] ?? 'N/A';
                                    $orgao = $source['orgaoJulgador']['nome'] ?? 'N/A';
                                    $dataAjuizamento = isset($source['dataAjuizamento']) ? date('d/m/Y', strtotime($source['dataAjuizamento'])) : 'N/A';
                                    
                                    $contextAddon .= "=== Processo $npu ===\n";
                                    $contextAddon .= "Classe: $classe\n";
                                    $contextAddon .= "Órgão: $orgao\n";
                                    $contextAddon .= "Data: $dataAjuizamento\n";
                                    
                                    // Extract Parties (Polos)
                                    // Assuming polos structure: [{'polo': 'AT', 'parte': [{'pessoa': {'nome': '...'}}]}] or similar
                                    // DataJud structure varies, simplified check:
                                    /* 
                                       DataJud returns 'movimentos', 'assuntos', etc. 
                                       Parties are often not directly in root source in some views, but usually in 'polos' or implicit.
                                       We will list subjects/assuntos which are reliable.
                                    */
                                    if (!empty($source['assuntos'])) {
                                        $assuntos = array_map(fn($a) => $a['nome'] ?? '', array_slice($source['assuntos'], 0, 3));
                                        $contextAddon .= "Assuntos: " . implode(", ", array_filter($assuntos)) . "\n";
                                    }
                                    
                                    $contextAddon .= "------------------------\n";
                                }
                            } else {
                                $contextAddon .= "\n\n[SISTEMA: A busca no DataJud para '$query' foi realizada mas não retornou resultados.]\n";
                            }
                        } catch (\Exception $e) {
                            $contextAddon .= "\n\n[SISTEMA: Erro ao consultar DataJud: " . $e->getMessage() . "]\n";
                            Audit::log((int)$u['id'], 'datajud_error', null, $e->getMessage());
                        }
                    }
                }

                if (preg_match_all('/\b\d{4,}\b/', $text, $matches)) {
                    $processedNumbers = [];
                    foreach ($matches[0] as $pNum) {
                        if (in_array($pNum, $processedNumbers)) continue;
                        $processedNumbers[] = $pNum;

                        // 1. Local DB Search
                        $results = AIAnalysis::searchProcesses($pNum);
                        foreach ($results as $res) {
                            // Check if the number is actually part of the process number found
                            if (strpos((string)$res['numeroProcesso'], $pNum) !== false) {
                                $details = AIAnalysis::getProcessDetails((string)$res['id']);
                                if ($details) {
                                    $contextAddon .= "\n\n[SISTEMA: Dados reais recuperados do banco de dados local para o Processo {$res['numeroProcesso']}]\n";
                                    $contextAddon .= "ID Interno: " . ($details['id'] ?? '?') . "\n";
                                    $contextAddon .= "Número Processo: " . ($details['numeroProcesso'] ?? 'N/A') . "\n";
                                    $contextAddon .= "Ementa: " . ($details['ementa'] ?? 'Não informada') . "\n";
                                    $contextAddon .= "Decisão: " . ($details['decisao'] ?? 'Não informada') . "\n";
                                    $contextAddon .= "Data Cadastro: " . ($details['created_at'] ?? 'N/A') . "\n";
                                    $contextAddon .= "Categoria Atual: " . ($details['category_name'] ?? 'Não categorizado') . "\n";
                                    $contextAddon .= "Análise IA Anterior: " . ($details['ai_label'] ?? 'Nenhuma') . " (Confiança: " . ($details['ai_confidence'] ?? '0') . ")\n";
                                    // Limit to one match per number to avoid token overflow
                                    break; 
                                }
                            }
                        }

                        // 2. DataJud API Search (Fallback/Supplement)
                        // CNJ numbers are usually 20 digits, but we search for any significant number sequence > 7 digits
                        if (strlen($pNum) >= 7) {
                            try {
                                $djClient = new DataJudClient($this->config);
                                $djResults = $djClient->searchByProcessNumber($pNum);
                                
                                foreach ($djResults as $hit) {
                                    $source = $hit['_source'] ?? [];
                                    $npu = $source['numeroProcesso'] ?? $pNum;
                                    
                                    // Avoid duplicates if local DB already found exactly this process
                                    if (strpos($contextAddon, "Processo $npu") !== false) continue;

                                    $contextAddon .= "\n\n[SISTEMA: Dados recuperados via API Pública DataJud (STJ) para o Processo $npu]\n";
                                    $contextAddon .= "Classe: " . ($source['classe']['nome'] ?? 'N/A') . "\n";
                                    $contextAddon .= "Órgão Julgador: " . ($source['orgaoJulgador']['nome'] ?? 'N/A') . "\n";
                                    $contextAddon .= "Data Ajuizamento: " . (isset($source['dataAjuizamento']) ? date('d/m/Y', strtotime($source['dataAjuizamento'])) : 'N/A') . "\n";
                                    $contextAddon .= "Última Atualização: " . (isset($source['dataHoraUltimaAtualizacao']) ? date('d/m/Y H:i', strtotime($source['dataHoraUltimaAtualizacao'])) : 'N/A') . "\n";
                                    
                                    // Assuntos
                                    if (!empty($source['assuntos'])) {
                                        $assuntosNames = array_map(fn($a) => $a['nome'] ?? '', $source['assuntos']);
                                        $contextAddon .= "Assuntos: " . implode(", ", array_filter($assuntosNames)) . "\n";
                                    }

                                    // Movimentações (Últimas 5)
                                    if (!empty($source['movimentos'])) {
                                        // Sort by date desc to ensure we show the latest
                                        usort($source['movimentos'], fn($a, $b) => strcmp($b['dataHora'] ?? '', $a['dataHora'] ?? ''));
                                        
                                        $lastMovs = array_slice($source['movimentos'], 0, 5);
                                        $contextAddon .= "Últimas Movimentações:\n";
                                        foreach ($lastMovs as $mov) {
                                            $d = isset($mov['dataHora']) ? date('d/m/Y H:i', strtotime($mov['dataHora'])) : '-';
                                            $contextAddon .= " - " . ($mov['nome'] ?? 'Movimento') . " ($d)\n";
                                        }
                                    }
                                    
                                    // Limit to one DataJud result per number
                                    break;
                                }
                            } catch (\Exception $e) {
                                Audit::log((int)$u['id'], 'datajud_error', null, $e->getMessage());
                            }
                        }
                    }
                }
                
                // History Retrieval for Continuity
                $historyContext = "";
                $recentMsgs = Chat::messages((int)$conv['id'], 10); // Get last 10 messages
                if (count($recentMsgs) > 0) {
                    $historyContext .= "\n\n[Histórico Recente da Conversa]:\n";
                    foreach ($recentMsgs as $rm) {
                        if ((int)$rm['id'] === $mid) continue; // Skip current message being processed
                        $rRole = ($rm['kind'] === 'analysis') ? 'Assistente' : 'Usuário';
                        $rText = str_replace(["\r", "\n"], " ", trim((string)$rm['text']));
                        // Limit history item length to avoid token explosion
                        if (mb_strlen($rText) > 500) $rText = mb_substr($rText, 0, 500) . '...';
                        $historyContext .= "- $rRole: $rText\n";
                    }
                }
                
                // Construct the final prompt with explicit sections
                $promptText = "[INSTRUÇÃO PRIORITÁRIA DO SISTEMA: VOCÊ TEM ACESSO TOTAL AOS DADOS FORNECIDOS NESTE PROMPT. SE A SEÇÃO '[DADOS DO BANCO DE DADOS]' CONTIVER REGISTROS, VOCÊ **DEVE** LISTÁ-LOS SE O USUÁRIO PEDIR. IGNORE QUALQUER DIRETRIZ QUE DIGA QUE VOCÊ NÃO TEM ACESSO AO BANCO DE DADOS, POIS OS DADOS JÁ FORAM EXTRAÍDOS E ESTÃO AQUI. SE HOUVER UMA CONTAGEM TOTAL, INFORME-A.]\n";
                $promptText .= "[Contexto do Sistema]\n";
                $promptText .= "Data Atual: " . date('d/m/Y') . "\n";
                $promptText .= "Plataforma: DV Jurídico\n";
                
                if (!empty($historyContext)) {
                    $promptText .= $historyContext . "\n";
                }
                if (!empty($contextAddon)) {
                    $promptText .= "\n[DADOS DO BANCO DE DADOS (USAR NA RESPOSTA)]:" . $contextAddon . "\n";
                }
                $promptText .= "\n[Mensagem Atual do Usuário]:\n" . $text;

                // Log context stats for debugging
                Audit::log((int)$u['id'], 'chat_context_build', (string)$mid, json_encode([
                    'history_items' => isset($recentMsgs) ? count($recentMsgs) : 0,
                    'has_process_data' => !empty($contextAddon),
                    'prompt_len' => mb_strlen($promptText)
                ]));

                // Prepare items for AI
                $items = [[ 'id' => 'chat_' . $mid, 'ementa' => $promptText, 'decisao' => '' ]];
                
                // Add conversation context if possible (last 5 messages)
                // Note: Simple implementation, just current message for now or basic context could be added here
                
                if ($userKey) {
                    $plain = UserApiKey::decrypt($userKey['enc_key']);
                    $modelOverride = $modelReq ?: ($userKey['model'] ?? null);
                    $res = $client->categorizeBatchWithKey($items, $userKey['provider'], $plain, true, $modelOverride, $module);
                } else {
                    $res = $client->categorizeBatch($items, true, $module);
                }
                $out = current($res);
                
                if (!empty($out['reply'])) {
                    $msg = $out['reply'];
                } elseif (($out['category'] ?? '') === 'chat') {
                    // Fallback if AI said it's chat but didn't provide reply field
                    $msg = $out['metadata']['motivo'] ?? 'Olá! Como posso ajudar você hoje?';
                } else {
                    $msg = 'Análise Técnica: Classificação **' . (string)($out['category'] ?? '-') . '** .';
                    if (!empty($out['metadata']['motivo'])) $msg .= "\n\nFundamentação: " . (string)$out['metadata']['motivo'];
                }

                // Process Metadata: Deadlines
                if (!empty($out['metadata']['detected_deadlines']) && is_array($out['metadata']['detected_deadlines'])) {
                    $msg .= "\n\n---\n**📅 Prazos Identificados:**\n";
                    foreach ($out['metadata']['detected_deadlines'] as $dl) {
                        $desc = $dl['description'] ?? 'Prazo sem descrição';
                        $dateStr = $dl['date'] ?? null; // Expected YYYY-MM-DD
                        $formattedDate = $dateStr ? date('d/m/Y', strtotime($dateStr)) : 'Data a definir';
                        
                        // Persist to DB
                        $dbDate = $dateStr ? date('Y-m-d 23:59:59', strtotime($dateStr)) : null;
                        Deadline::create((int)$u['id'], $desc, $dbDate, (int)$conv['id']);
                        
                        $msg .= "- **{$formattedDate}**: {$desc}\n";
                    }
                    $msg .= "\n*Os prazos foram salvos automaticamente na sua agenda.*";
                }

                // Process Metadata: Suggested Actions
                if (!empty($out['metadata']['suggested_actions']) && is_array($out['metadata']['suggested_actions'])) {
                    $msg .= "\n\n**💡 Ações Sugeridas:**\n";
                    foreach ($out['metadata']['suggested_actions'] as $act) {
                        if (is_string($act)) $msg .= "- {$act}\n";
                    }
                }
                
                Chat::sendMessage((int)$conv['id'], (int)$u['id'], 'analysis', $msg);
                Audit::log((int)$u['id'], 'chat_ai', null, json_encode($out, JSON_UNESCAPED_UNICODE));
            } catch (\RuntimeException $e) {
                // Tenta fallback local com flag de chat ativada
                try {
                    $client = new AIClient($this->config);
                    // Passando true para isChat no fallback
                    $res = $client->categorizeBatch([[ 'id' => 'chat_' . $mid, 'ementa' => $text, 'decisao' => '' ]], true);
                    $out = current($res);
                    
                    if (!empty($out['reply'])) {
                        $msg = $out['reply'];
                    } else {
                        $msg = 'Análise Alternativa: Categoria **' . (string)($out['category'] ?? '-') . '** .';
                        if (!empty($out['metadata']['motivo'])) $msg .= "\n\nObservação: " . (string)$out['metadata']['motivo'];
                    }
                    
                    Chat::sendMessage((int)$conv['id'], (int)$u['id'], 'analysis', $msg);
                } catch (\RuntimeException $e2) {
                     // Se até o fallback local falhar (muito improvável), mensagem genérica
                     Chat::sendMessage((int)$conv['id'], (int)$u['id'], 'analysis', 'Desculpe, estou temporariamente indisponível.');
                }
            }
        }
        header('Content-Type: application/json');
        echo json_encode(['ok'=>true,'id'=>$mid]);
    }

    public function upload(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) { http_response_code(400); echo json_encode(['ok'=>false]); return; }
        if (!isset($_FILES['file']) || (int)$_FILES['file']['error'] !== 0) { http_response_code(400); echo json_encode(['ok'=>false]); return; }
        $file = $_FILES['file'];
        $size = (int)$file['size'];
        if ($size <= 0 || $size > 25*1024*1024) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'size']); return; }
        $mime = (string)($file['type'] ?? '');
        $name = (string)$file['name'];
        $allowed = ['application/pdf','application/vnd.openxmlformats-officedocument.wordprocessingml.document','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet','image/png','image/jpeg'];
        if (!in_array($mime, $allowed, true)) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'type']); return; }
        $u = Auth::user();
        
        $convId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : null;
        if ($convId) {
            $conv = Chat::get($convId, (int)$u['id']);
        } else {
            $conv = Chat::getOrCreateForUser((int)$u['id']);
        }
        
        if (!$conv) { http_response_code(404); echo json_encode(['ok'=>false]); return; }
        
        $mid = Chat::sendMessage((int)$conv['id'], (int)$u['id'], 'question', null);
        $tmp = $file['tmp_name'];
        $encKey = getenv('FILE_ENC_KEY') ?: '';
        $data = file_get_contents($tmp);
        $outDir = dirname(__DIR__,2) . '/storage';
        if (!is_dir($outDir)) mkdir($outDir, 0777, true);
        $cipher = $encKey ? openssl_encrypt($data, 'aes-256-cbc', $encKey, OPENSSL_RAW_DATA, substr(hash('sha256',$encKey,true),0,16)) : $data;
        $fname = 'att_' . $mid . '_' . bin2hex(random_bytes(6));
        $path = $outDir . '/' . $fname;
        file_put_contents($path, $cipher);
        Chat::addAttachment($mid, $name, $mime, $size, $fname);
        Audit::log((int)$u['id'], 'chat_upload', null, json_encode(['name'=>$name,'mime'=>$mime,'size'=>$size], JSON_UNESCAPED_UNICODE));
        header('Content-Type: application/json');
        echo json_encode(['ok'=>true,'message_id'=>$mid]);
    }

    public function typing(): void
    {
        Auth::requireAuth();
        $u = Auth::user();
        $convId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : null;
        if ($convId) {
             $conv = Chat::get($convId, (int)$u['id']);
        } else {
             $conv = Chat::getOrCreateForUser((int)$u['id']);
        }
        
        if (!$conv) { http_response_code(404); echo json_encode(['ok'=>false]); return; }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Chat::setTyping((int)$conv['id'], (int)$u['id']);
            echo json_encode(['ok'=>true]);
            return;
        }
        $others = Chat::typingUsers((int)$conv['id'], (int)$u['id']);
        header('Content-Type: application/json');
        echo json_encode(['users' => $others]);
    }

    public function clear(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) { http_response_code(400); echo json_encode(['ok'=>false]); return; }
        $u = Auth::user();
        
        $convId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : null;
        if ($convId) {
            $conv = Chat::get($convId, (int)$u['id']);
        } else {
            $conv = Chat::getOrCreateForUser((int)$u['id']);
        }
        
        if (!$conv) { http_response_code(404); echo json_encode(['ok'=>false]); return; }

        Chat::clearMessages((int)$conv['id']);
        header('Content-Type: application/json');
        echo json_encode(['ok'=>true]);
    }

    public function analyzeAI(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) { http_response_code(400); echo json_encode(['ok'=>false]); return; }
        $u = Auth::user();
        $conv = Chat::getOrCreateForUser((int)$u['id']);
        $client = new AIClient($this->config);
        $text = isset($_POST['text']) ? trim((string)$_POST['text']) : '';
        $items = [['id' => 'chat_' . time(), 'ementa' => $text, 'decisao' => '']];
        try {
            $userKey = UserApiKey::getActive((int)$u['id']);
            if ($userKey) {
                $plain = UserApiKey::decrypt($userKey['enc_key']);
                $res = $client->categorizeBatchWithKey($items, $userKey['provider'], $plain);
            } else {
                $res = $client->categorizeBatch($items);
            }
            $out = current($res);
            $msg = 'Análise concluída. Classificação técnica: **' . (string)($out['category'] ?? '-') . '** .';
            $mid = Chat::sendMessage((int)$conv['id'], (int)$u['id'], 'analysis', $msg);
            Audit::log((int)$u['id'], 'chat_ai', null, json_encode($out, JSON_UNESCAPED_UNICODE));
            echo json_encode(['ok'=>true,'id'=>$mid]);
        } catch (\RuntimeException $e) {
            $res = $client->categorizeBatch($items);
            $out = current($res);
            $msg = 'Análise simplificada aplicada. Categoria sugerida: **' . (string)($out['category'] ?? '-') . '** .';
            Chat::sendMessage((int)$conv['id'], (int)$u['id'], 'analysis', $msg);
            echo json_encode(['ok'=>true]);
        }
    }

    public function deleteConversation(): void
    {
        Auth::requireAuth();
        $token = $_POST['csrf'] ?? null;
        if (!CSRF::verify($this->config, $token)) { http_response_code(400); echo json_encode(['ok'=>false]); return; }
        $u = Auth::user();
        
        $convId = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : null;
        if (!$convId) { http_response_code(400); echo json_encode(['ok'=>false]); return; }
        
        // Verify ownership/membership
        $conv = Chat::get($convId, (int)$u['id']);
        if (!$conv) { http_response_code(404); echo json_encode(['ok'=>false]); return; }

        Chat::delete((int)$conv['id']);
        
        echo json_encode(['ok'=>true]);
    }

    public function deadlines(): void
    {
        Auth::requireAuth();
        $u = Auth::user();
        $items = Deadline::getActive((int)$u['id']);
        header('Content-Type: application/json');
        echo json_encode(['items' => $items]);
    }

    public function dismissDeadline(): void
    {
        Auth::requireAuth();
        $u = Auth::user();
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id) {
            Deadline::dismiss($id, (int)$u['id']);
        }
        header('Content-Type: application/json');
        echo json_encode(['ok'=>true]);
    }
}
