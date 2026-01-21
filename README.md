# Documentação Técnica: Integração DataJud

Esta documentação descreve a implementação técnica da integração entre o Chat IA do sistema DV Jurídico e a API Pública do DataJud (CNJ).

## 1. Visão Geral
A integração permite que o usuário consulte processos judiciais diretamente pelo chat, utilizando linguagem natural. O sistema detecta intenções de busca, consulta a API do DataJud, processa os resultados JSON e os apresenta de forma formatada.

### Funcionalidades Principais
- **Busca por Número do Processo (NPU):** Detecta sequências numéricas (CNJ 20 dígitos ou parciais).
- **Busca por Termos/Partes:** Permite buscas por nome de partes, advogados ou palavras-chave.
- **Cache Inteligente:** Armazena resultados em arquivo por 1 hora para otimizar performance e reduzir chamadas à API.
- **Fallback Seguro:** Utiliza uma chave de API padrão caso nenhuma seja configurada.

## 2. Arquitetura e Componentes

### 2.1. Cliente DataJud (`app/Core/DataJudClient.php`)
Responsável pela comunicação direta com a API ElasticSearch do DataJud.

- **Autenticação:** Header `Authorization: APIKey <chave>`.
- **Métodos Principais:**
  - `searchByProcessNumber(string $npu)`: Busca exata pelo campo `numeroProcesso`.
  - `searchByQuery(string $query)`: Busca textual (`query_string`) em todos os campos indexados.
  - `fetchMulti(array $npus)`: Executa múltiplas buscas em paralelo usando `curl_multi` (para enriquecimento em lote).
- **Cache:**
  - Diretório: `storage/cache/datajud/`
  - TTL: 3600 segundos (1 hora).
  - Mecanismo: Hash MD5 da query ou número do processo serve como nome do arquivo JSON.

### 2.2. Controlador de Chat (`app/Controllers/ChatController.php`)
Gerencia a intenção do usuário e a formatação da resposta.

- **Detecção de Intenção:**
  - *Busca Explícita:* Regex `/consultar|buscar|pesquisar.*datajud/i`.
  - *Busca Implícita:* Detecção de números de processo no texto.
- **Fluxo de Dados:**
  1. Usuário envia mensagem (ex: "buscar processo de João da Silva").
  2. Controller identifica intenção e extrai a query ("João da Silva").
  3. `DataJudClient` é instanciado e consulta a API.
  4. Resultados são formatados em texto (Seção `[SISTEMA: Resultados...]`).
  5. Prompt enriquecido é enviado à IA (OpenAI/Gemini) para gerar a resposta final ao usuário.

## 3. Estrutura de Dados Retornada

O sistema extrai e exibe os seguintes campos do JSON do DataJud:

| Campo | Descrição | Origem no JSON |
|-------|-----------|----------------|
| **NPU** | Número Único do Processo | `_source.numeroProcesso` |
| **Classe** | Classe Judicial | `_source.classe.nome` |
| **Órgão** | Órgão Julgador | `_source.orgaoJulgador.nome` |
| **Data** | Data de Ajuizamento | `_source.dataAjuizamento` |
| **Assuntos** | Assuntos do Processo | `_source.assuntos[].nome` |
| **Movimentos** | Últimas 5 movimentações | `_source.movimentos[]` (ordenado por data) |

## 4. Configuração

A configuração é carregada de `config/config.php`.

```php
'datajud' => [
    'url' => 'https://api-publica.datajud.cnj.jus.br/api_publica_stj/_search',
    'api_key' => 'SUA_CHAVE_AQUI' // Fallback implementado no código
]
```

## 5. Tratamento de Erros e Segurança

- **Falhas na API:** Exceções são capturadas, logadas na tabela `audit` e uma mensagem amigável é retornada ao contexto do chat.
- **Dados Sensíveis:** A integração respeita a visibilidade pública da API. Dados em segredo de justiça não retornados pela API pública não serão exibidos.
- **Validação SSL:** Em ambiente de desenvolvimento local, a verificação SSL pode ser ignorada (configurável), mas deve ser ativa em produção.

## 6. Como Usar

No Chat IA, o usuário pode digitar:
- *"Consultar DataJud sobre o processo 1234567..."*
- *"Buscar processo de Empresa X LTDA"*
- *"Pesquisar na API do tribunal sobre danos morais"*

O sistema priorizará dados locais (banco de dados do DV Jurídico) e complementará com dados do DataJud quando necessário.
