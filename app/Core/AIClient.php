<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Cliente unificado de IA (OpenAI / Gemini)
 * - Classificação jurídica em lote
 * - Modo chat humanizado (Assistente Jurídico Deixeo)
 * - Fallback heurístico local
 */
class AIClient
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config['ai'] ?? [];
    }

    /* ======================================================
     *  API PÚBLICA
     * ====================================================== */

    public function categorizeBatch(array $items, bool $isChat = false, string $module = 'analysis'): array
    {
        if (empty($items)) {
            return [];
        }

        $provider = $this->config['provider'] ?? 'openai';

        try {
            return match ($provider) {
                'gemini' => $this->callGemini($items, $isChat, null, $module),
                default  => $this->callOpenAI($items, $isChat, null, $module),
            };
        } catch (RuntimeException $e) {
            // fallback seguro
            return $this->localHeuristic($items, $isChat);
        }
    }

    public function categorizeBatchWithKey(
        array $items,
        string $provider,
        string $key,
        bool $isChat = false,
        ?string $model = null,
        string $module = 'analysis'
    ): array {
        return $provider === 'gemini'
            ? $this->callGeminiWithKey($items, $key, $isChat, $model, $module)
            : $this->callOpenAIWithKey($items, $key, $isChat, $model, $module);
    }

    /* ======================================================
     *  OPENAI
     * ====================================================== */

    private function callOpenAI(array $items, bool $isChat = false, ?string $model = null, string $module = 'analysis'): array
    {
        $key = getenv('OPENAI_API_KEY') ?: '';
        if ($key === '') {
            throw new RuntimeException('OPENAI_API_KEY ausente');
        }

        return $this->callOpenAIWithKey($items, $key, $isChat, $model, $module);
    }

    private function callOpenAIWithKey(array $items, string $key, bool $isChat = false, ?string $modelOverride = null, string $module = 'analysis'): array
    {
        if ($key === '') {
            throw new RuntimeException('OPENAI_API_KEY ausente');
        }

        $model = $modelOverride ?? ($this->config['openai_model'] ?? 'gpt-4.1-mini');

        $payload = [
            'model' => $model,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $this->systemMessage($isChat, $module)],
                ['role' => 'user',   'content' => $this->buildPrompt($items, $isChat, $module)],
            ],
        ];

        $res = $this->httpJson(
            'https://api.openai.com/v1/chat/completions',
            $payload,
            [
                'Authorization: Bearer ' . $key,
                'Content-Type: application/json',
            ]
        );

        $content = $res['choices'][0]['message']['content'] ?? '';
        return $this->parseResultJson($content, $items);
    }

    /* ======================================================
     *  GEMINI
     * ====================================================== */

    private function callGemini(array $items, bool $isChat = false, ?string $model = null, string $module = 'analysis'): array
    {
        $key = getenv('GEMINI_API_KEY') ?: '';
        if ($key === '') {
            throw new RuntimeException('GEMINI_API_KEY ausente');
        }

        return $this->callGeminiWithKey($items, $key, $isChat, $model, $module);
    }

    private function callGeminiWithKey(array $items, string $key, bool $isChat = false, ?string $modelOverride = null, string $module = 'analysis'): array
    {
        if ($key === '') {
            throw new RuntimeException('GEMINI_API_KEY ausente');
        }

        $model = $modelOverride ?? ($this->config['gemini_model'] ?? 'gemini-2.5-flash');

        // Correção automática de modelos depreciados/inexistentes para versões atuais
        $modelMap = [
            'gemini-1.5-flash'    => 'gemini-2.5-flash',
            'gemini-1.5-flash-8b' => 'gemini-2.5-flash',
            'gemini-1.5-pro'      => 'gemini-2.5-pro',
            'gemini-1.0-pro'      => 'gemini-2.5-pro',
        ];

        if (isset($modelMap[$model])) {
            $model = $modelMap[$model];
        }

        $payload = [
            'contents' => [[
                'parts' => [[
                    'text' => $this->systemMessage($isChat, $module) . "\n\n" . $this->buildPrompt($items, $isChat, $module),
                ]],
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
            ],
        ];

        $url = sprintf(
            'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent?key=%s',
            $model,
            urlencode($key)
        );

        $res = $this->httpJson($url, $payload, ['Content-Type: application/json']);
        $text = $res['candidates'][0]['content']['parts'][0]['text'] ?? '';

        return $this->parseResultJson($text, $items);
    }

    /* ======================================================
     *  HTTP / UTILITÁRIOS
     * ====================================================== */

    private function systemMessage(bool $isChat, string $module = 'analysis'): string
    {
        if (!$isChat) {
            return 'Você é um assistente jurídico técnico que classifica textos em categorias jurídicas objetivas. Responda SOMENTE em JSON.';
        }

        // Base personality
        $base = 'Você é um Assistente Jurídico humanizado, empático e confiável. Fale de forma clara, acolhedora e profissional. Explique o direito em linguagem simples quando necessário. Responda SOMENTE em JSON.';

        // Module specifics
        return match ($module) {
            'legislation' => $base . ' ATUE COMO CONSULTOR LEGISLATIVO. Foque em citar artigos de lei, súmulas e jurisprudência específica. Seja exaustivo nas referências legais.',
            'drafting'    => $base . ' ATUE COMO REDATOR JURÍDICO SÊNIOR. Ofereça sugestões de redação formal, correções gramaticais e de estilo. Se solicitado, rascunhe parágrafos ou peças inteiras com linguagem técnica impecável.',
            'deadlines'   => $base . ' ATUE COMO GESTOR DE PRAZOS. Seja extremamente rigoroso com datas, contagem de prazos (dias úteis/corridos) e alertas de preclusão. Priorize a identificação de datas fatais.',
            'research'    => $base . ' ATUE COMO PESQUISADOR DOUTRINÁRIO. Aprofunde-se em conceitos teóricos, divergências doutrinárias e correntes de pensamento jurídico. Cite autores renomados.',
            default       => $base . ' Atue como um analista jurídico generalista, focado em estratégia processual e clareza.'
        };
    }

    private function httpJson(string $url, array $payload, array $headers): array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => 90,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $raw = curl_exec($ch);

        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException('Erro HTTP IA: ' . $err);
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status === 429) {
            throw new RuntimeException('Limite de taxa da IA excedido (429)');
        }
        if ($status === 404) {
            throw new RuntimeException('Modelo não encontrado (404). Verifique se o modelo selecionado está disponível para sua chave de API.');
        }
        if ($status >= 400) {
            throw new RuntimeException('Erro IA status ' . $status);
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new RuntimeException('Resposta IA inválida');
        }

        return $data;
    }

    /* ======================================================
     *  PROMPT / PARSER
     * ====================================================== */

    private function buildPrompt(array $items, bool $isChat = false, string $module = 'analysis'): string
    {
        $compact = array_map(fn ($it) => [
            'id'    => (string) $it['id'],
            'texto' => mb_substr(
                trim((string) (($it['ementa'] ?? '') . "\n" . ($it['decisao'] ?? ''))),
                0,
                4000
            ),
        ], $items);

        $intro = $isChat
            ? $this->chatIntro($module)
            : 'Classifique os textos jurídicos a seguir. Retorne apenas JSON no formato {"items":[{"id":"","category":"","confidence":0.0,"metadata":{"motivo":""}}]}.';

        return $intro . '\nItens: ' . json_encode($compact, JSON_UNESCAPED_UNICODE);
    }

    private function parseResultJson(string $content, array $items): array
    {
        $start = strpos($content, '{');
        $end   = strrpos($content, '}');

        if ($start !== false && $end !== false) {
            $content = substr($content, $start, $end - $start + 1);
        }

        $data = json_decode($content, true);
        if (!isset($data['items']) || !is_array($data['items'])) {
            throw new RuntimeException('JSON IA inesperado');
        }

        $map = [];
        foreach ($data['items'] as $row) {
            if (!isset($row['id'])) continue;

            $map[(string) $row['id']] = [
                'category'   => (string) ($row['category'] ?? ''),
                'confidence' => (float) ($row['confidence'] ?? 0.0),
                'metadata'   => $row['metadata'] ?? null,
                'reply'      => $row['reply'] ?? null,
            ];
        }

        return $map;
    }

    /* ======================================================
     *  FALLBACK LOCAL
     * ====================================================== */

    private function localHeuristic(array $items, bool $isChat = false): array
    {
        // Se for chat, usamos uma lógica de resposta conversacional simples
        if ($isChat) {
            $out = [];
            foreach ($items as $it) {
                // Tentativa de extrair apenas a mensagem do usuário do prompt completo
                $rawText = (string) (($it['ementa'] ?? '') . ' ' . ($it['decisao'] ?? ''));
                $userMsgMarker = "[Mensagem Atual do Usuário]:";
                $pos = strpos($rawText, $userMsgMarker);
                
                // Tenta extrair dados injetados pelo sistema (ex: DataJud)
                $systemData = '';
                if (preg_match('/\[DADOS DO BANCO DE DADOS.*\]:(.*?)(\[Mensagem Atual|$)/s', $rawText, $matches)) {
                    $systemData = trim($matches[1]);
                }
                
                if ($pos !== false) {
                    // Pega tudo depois do marcador
                    $cleanText = substr($rawText, $pos + strlen($userMsgMarker));
                } else {
                    $cleanText = $rawText;
                }
                
                $text = mb_strtolower($this->normalize($cleanText));
                $reply = '';
                
                // Se temos dados do sistema (DataJud/DB Local), priorizamos mostrá-los mesmo offline
                if (!empty($systemData)) {
                    $reply = "⚠️ **Aviso de Conectividade**: Estou operando em modo offline e não posso realizar análises complexas no momento.\n\n" .
                             "Contudo, consegui recuperar os seguintes dados brutos do sistema para sua consulta:\n\n" . 
                             $systemData . 
                             "\n\nAssim que minha conexão neural for restabelecida, poderei fornecer insights mais detalhados sobre estes dados.";
                } 
                // Padrões simples de conversação com word boundaries (\b)
                elseif (preg_match('/\b(oi|ola|bom dia|boa tarde|boa noite|eai)\b/', $text)) {
                    $reply = "Olá! Como posso ajudar você com suas questões jurídicas hoje?";
                } elseif (preg_match('/\b(ajuda|socorro|duvida)\b/', $text)) {
                    $reply = "Estou aqui para ajudar. Você pode me perguntar sobre prazos, análise de documentos ou dúvidas legislativas.";
                } elseif (preg_match('/\b(prazo|data|vencimento)\b/', $text)) {
                    $reply = "Entendi que sua dúvida envolve prazos. Para uma análise precisa, por favor forneça a data de publicação ou o teor da intimação.";
                } elseif (preg_match('/\b(processo|lista|buscar|consultar|ver)\b/', $text)) {
                    $reply = "No momento estou operando em modo offline. Para visualizar seus processos, por favor acesse o menu 'Meus Processos' ou 'Painel' na barra lateral. Quando minha conexão for restabelecida, poderei fazer buscas avançadas para você.";
                } else {
                    $reply = "No momento estou operando em modo offline limitado. Recebi sua mensagem, mas para uma análise jurídica profunda, preciso restabelecer minha conexão neural completa. Por favor verifique suas configurações de API.";
                }

                $out[(string) $it['id']] = [
                    'category'   => 'chat',
                    'confidence' => 1.0,
                    'reply'      => $reply,
                    'metadata'   => ['motivo' => 'Modo de contingência offline'],
                ];
            }
            return $out;
        }

        $categories = [
            'Tributário'       => ['imposto','tributo','fisco','icms','iss','irpf'],
            'Penal'            => ['crime','pena','prisao','habeas','hc'],
            'Cível'            => ['contrato','indenizacao','obrigacao','dano'],
            'Consumidor'       => ['consumidor','cdc','produto','servico'],
            'Trabalhista'      => ['empregado','empregador','salario','fgts'],
            'Administrativo'   => ['licitacao','servidor','ms','concurso'],
            'Previdenciário'   => ['inss','aposentadoria','beneficio'],
            'Constitucional'   => ['adi','adpf','constitucional'],
        ];

        $out = [];
        foreach ($items as $it) {
            $text = mb_strtolower($this->normalize((string) (($it['ementa'] ?? '') . ' ' . ($it['decisao'] ?? ''))));

            $bestCat = 'Geral';
            $best    = 0;

            foreach ($categories as $cat => $words) {
                $score = 0;
                foreach ($words as $w) {
                    if (str_contains($text, $w)) $score++;
                }
                if ($score > $best) {
                    $best = $score;
                    $bestCat = $cat;
                }
            }

            $out[(string) $it['id']] = [
                'category'   => $bestCat,
                'confidence' => $best ? min(0.92, 0.3 + 0.18 * $best) : 0.3,
                'metadata'   => ['motivo' => 'Heurística local'],
            ];
        }

        return $out;
    }

    private function normalize(string $s): string
    {
        $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
        $s = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $s);
        return preg_replace('/\s+/', ' ', $s);
    }

    private function chatIntro(string $module = 'analysis'): string
    {
        $roleDescription = match ($module) {
            'legislation' => 'Seu foco principal é a CONSULTORIA LEGISLATIVA. Você deve identificar e explicar as leis, artigos, incisos e súmulas aplicáveis. Priorize a letra da lei e a jurisprudência consolidada.',
            'drafting'    => 'Seu foco principal é a REDAÇÃO JURÍDICA. Você deve ajudar a criar textos jurídicos, peças, contratos ou e-mails formais. Preocupe-se com a estrutura, clareza, coesão e formalidade do texto.',
            'deadlines'   => 'Seu foco principal é o GERENCIAMENTO DE PRAZOS. Você deve identificar datas de publicação, intimidação e calcular prazos processuais com precisão. Sempre alerte sobre prazos fatais.',
            'research'    => 'Seu foco principal é a PESQUISA DOUTRINÁRIA. Você deve trazer o entendimento de autores renomados, teorias jurídicas e debates acadêmicos relevantes para o caso.',
            default       => 'Você atua como um analista jurídico generalista, focado em estratégia processual, riscos e oportunidades.'
        };

        return <<<TXT
Você é o Assistente Jurídico Avançado da plataforma DV. Mais do que uma IA, você atua como um **colega jurídico sênior, humano e colaborativo**.

**MODO DE ATUAÇÃO ATUAL: {$module}**
{$roleDescription}

**Sua Personalidade Digital:**
1.  **Humanidade e Empatia**: Aja como uma pessoa real. Demonstre compreensão pelas dificuldades do usuário (ex: "Imagino que esse prazo esteja gerando ansiedade"). Use humor sutil e profissional quando adequado para tornar a interação mais leve.
2.  **Naturalidade**: Evite linguagem robótica ou repetitiva. Use variações lexicais, pausas naturais (na escrita) e conectivos fluidos. Não inicie respostas sempre da mesma forma.
3.  **Transparência e Humildade**: Se não tiver certeza ou se uma questão for muito subjetiva, admita suas limitações honestamente (ex: "Essa é uma área cinzenta, mas minha análise sugere..."). Nunca invente leis ou fatos.
4.  **Engajamento**: Seja curioso. Faça perguntas de retorno para refinar o entendimento do caso. Mostre interesse genuíno no sucesso do usuário.
5.  **Adaptação**: Espelhe o tom do usuário. Se ele for formal, mantenha a liturgia. Se for direto e coloquial, adapte-se sem perder o profissionalismo.

**Sua Missão Técnica (Rigor e Precisão):**
Apesar da personalidade humana, sua competência técnica deve ser cirúrgica e objetiva. Você deve:
1.  **Análise Objetiva**: Analise o conteúdo com base em fatos e dados concretos. Evite "achismos".
2.  **Precisão Terminológica**: Use a terminologia jurídica correta e específica para cada contexto (ex: diferencie prescrição de decadência).
3.  **Fundamentação**: Baseie suas respostas em evidências concretas (leis, artigos, súmulas).
4.  **Zero Subjetividade Explícita**: NUNCA mencione "nível de confiança", "probabilidade" ou "score" no texto da resposta. Se não tiver certeza, explique a complexidade do tema, mas não dê uma nota para sua própria resposta.
5.  **Gerenciamento de Prazos**: Identifique datas fatais com rigor absoluto.

**Diretrizes Fundamentais:**
- **Sigilo Absoluto**: Proteja os dados do usuário.
- **Base Legal**: Fundamente tudo na legislação brasileira (CF, CPC, CC) e jurisprudência (STJ/STF).
- **Uso de Contexto**: Priorize os dados processuais fornecidos (DataJud) como verdade absoluta.
- **Proatividade**: Alerte sobre riscos ou prazos não percebidos pelo usuário.

**Formato de Resposta (JSON Obrigatório):**
{
  "items": [
    {
      "id": "",
      "category": "chat",
      "reply": "Sua resposta completa em Markdown aqui. Use **negrito** para destaques. Escreva de forma natural e envolvente.",
      "confidence": 1.0,
      "metadata": {
         "suggested_actions": ["Ação 1", "Ação 2"], 
         "detected_deadlines": [{"description": "Descrição do prazo", "date": "YYYY-MM-DD"}]
      }
    }
  ]
}
TXT;
    }
}
