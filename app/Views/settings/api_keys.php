<?php
use App\Core\View;
?>
<div class="container mt-2">
  <section class="card">
    <div class="card-header">
      <h2>Chaves de API</h2>
      <p class="text-muted" style="margin-bottom:0">Escolha OpenAI ou Gemini, insira sua chave e teste antes de salvar.</p>
    </div>
    
    <form id="apiKeyForm" method="post" action="/settings/api-keys/save" novalidate style="margin-bottom: 2rem;">
      <input type="hidden" name="csrf" value="<?= View::e($csrf) ?>">
      
      <div class="grid" style="grid-template-columns: 1fr 1fr 2fr; gap: 1.5rem; align-items: start;">
        <div class="form-group">
          <label>Fornecedor</label>
          <select name="provider" id="providerSelect">
            <option value="openai">OpenAI</option>
            <option value="gemini">Gemini</option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Modelo</label>
          <select name="model" id="modelSelect">
            <!-- Options populated by JS -->
          </select>
        </div>
        
        <div class="form-group">
          <label>Chave</label>
          <input type="password" name="key" required placeholder="sk-..." autocomplete="off">
          <div class="field-error" data-error-for="key"></div>
        </div>
      </div>

      <div class="flex" style="margin-top: 1rem;">
        <button class="btn btn-secondary" type="button" id="testKey">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
          Testar Conexão
        </button>
        <button class="btn btn-primary" type="submit">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
          Salvar Chave
        </button>
        <span class="loader" style="display:none; align-self: center;">Verificando...</span>
      </div>
    </form>

    <?php if (!empty($keys)): ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Fornecedor</th>
              <th>Modelo</th>
              <th>Chave (Mascarada)</th>
              <th>Estado</th>
              <th class="text-right">Ações</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($keys as $k): ?>
              <tr>
                <td>
                  <span style="font-weight: 600; text-transform: capitalize;"><?= View::e($k['provider']) ?></span>
                </td>
                <td>
                  <span class="text-muted"><?= View::e($k['model'] ?? '-') ?></span>
                </td>
                <td>
                  <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px; color: var(--legal-navy);"><?= View::e($k['masked']) ?></code>
                </td>
                <td>
                  <?php if ($k['active']): ?>
                    <span class="badge" style="background: #ecfdf5; color: #047857; padding: 2px 8px; border-radius: 12px; font-size: 0.85rem;">Ativa</span>
                  <?php else: ?>
                    <span class="badge" style="background: #f3f4f6; color: #6b7280; padding: 2px 8px; border-radius: 12px; font-size: 0.85rem;">Inativa</span>
                  <?php endif; ?>
                </td>
                <td class="text-right">
                  <div class="flex" style="justify-content: flex-end; gap: 0.5rem;">
                    <?php if (!$k['active']): ?>
                      <form method="post" action="/settings/api-keys/activate" style="margin:0;">
                        <input type="hidden" name="csrf" value="<?= View::e($csrf) ?>">
                        <input type="hidden" name="provider" value="<?= View::e($k['provider']) ?>">
                        <button class="btn btn-secondary btn-sm" type="submit" style="padding: 0.4rem 0.8rem; font-size: 0.85rem;">Ativar</button>
                      </form>
                    <?php endif; ?>
                    <form method="post" action="/settings/api-keys/delete" style="margin:0;" onsubmit="return confirm('Tem certeza que deseja remover esta chave?');">
                      <input type="hidden" name="csrf" value="<?= View::e($csrf) ?>">
                      <input type="hidden" name="provider" value="<?= View::e($k['provider']) ?>">
                      <button class="btn btn-danger btn-sm" type="submit" style="padding: 0.4rem 0.8rem; font-size: 0.85rem; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;">Remover</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="alert alert-info">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
        Nenhuma chave de API configurada. Adicione uma para usar os recursos de IA.
      </div>
    <?php endif; ?>
  </section>
</div>

<section class="card mt-4">
    <div class="card-header">
      <h3>Informações sobre Planos e Limites</h3>
    </div>
    <div style="padding: 1rem;">
      <div class="alert alert-info" style="margin-bottom: 1.5rem;">
        <strong>Gemini (Google):</strong> Oferece um nível gratuito generoso para modelos como 1.5 Flash e 1.5 Pro.
        <ul>
            <li>Gratuito: Até 15 RPM (requisições por minuto) e 1 milhão de tokens por dia (verificar site oficial).</li>
            <li>Ideal para testes e uso moderado.</li>
        </ul>
      </div>
      <div class="alert" style="background: #fffbeb; color: #92400e; border: 1px solid #fcd34d;">
        <strong>OpenAI (ChatGPT):</strong> Não possui plano gratuito via API (diferente do site chatgpt.com).
        <ul>
            <li>GPT-4o Mini: Extremamente barato e capaz, recomendado para economizar.</li>
            <li>GPT-4o: Maior inteligência, custo mais elevado.</li>
            <li>Cobrança por uso (pré-pago ou pós-pago).</li>
        </ul>
      </div>
    </div>
  </section>

  <script>
  document.addEventListener('DOMContentLoaded', () => {
  const models = {
    'openai': [
      { value: 'gpt-4o', label: 'GPT-4o (Recomendado)', type: 'paid', badge: 'Pago' },
      { value: 'gpt-4o-mini', label: 'GPT-4o Mini', type: 'paid', badge: 'Econômico' },
      { value: 'gpt-4-turbo', label: 'GPT-4 Turbo', type: 'paid', badge: 'Pago' },
      { value: 'gpt-3.5-turbo', label: 'GPT-3.5 Turbo', type: 'paid', badge: 'Pago' }
    ],
    'gemini': [
      { value: 'gemini-1.5-pro', label: 'Gemini 1.5 Pro', type: 'free_tier', badge: 'Gratuito' },
      { value: 'gemini-1.5-flash', label: 'Gemini 1.5 Flash', type: 'free_tier', badge: 'Gratuito' },
      { value: 'gemini-pro', label: 'Gemini Pro', type: 'free_tier', badge: 'Gratuito' }
    ]
  };

  const providerSelect = document.getElementById('providerSelect');
  const modelSelect = document.getElementById('modelSelect');

  function updateModels() {
    const provider = providerSelect.value;
    const options = models[provider] || [];
    
    modelSelect.innerHTML = '';
    options.forEach(opt => {
      const el = document.createElement('option');
      el.value = opt.value;
      
      let badgeHtml = '';
      if (opt.badge) {
          if (opt.badge === 'Gratuito') {
              el.textContent = `${opt.label} [GRATUITO]`;
          } else if (opt.badge === 'Econômico') {
              el.textContent = `${opt.label} [$ ECONÔMICO]`;
          } else {
              el.textContent = opt.label;
          }
      } else {
          el.textContent = opt.label;
      }
      
      modelSelect.appendChild(el);
    });
  }

  providerSelect.addEventListener('change', updateModels);
  
  // Initialize
  updateModels();
});
</script>
