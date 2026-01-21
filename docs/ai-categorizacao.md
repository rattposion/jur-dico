# Categorização via IA

- Módulo AIClient integra com OpenAI (padrão) ou Gemini.
- Chaves de API são lidas de variáveis de ambiente (OPENAI_API_KEY, GEMINI_API_KEY).
- Fluxo:
  - Admin aciona "/admin/ai/classify" no painel.
  - Backend lê o JSON original via JSONStream em lotes.
  - Para cada lote envia resumo (ementa + decisão) para a IA.
  - A IA retorna JSON com id, category, confidence e metadata.
  - O sistema encontra/cria a categoria, associa ao registro e salva ai_label, ai_confidence, ai_metadata.
  - Auditoria registrada em audit_logs com ação "ai_classify".

