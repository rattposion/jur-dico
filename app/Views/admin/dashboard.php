<?php
use App\Core\View;
?>
<div class="container mt-2">
  <section id="systemStatus" class="card" aria-live="polite" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h2 style="font-size: 1.25rem; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="var(--legal-royal)"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
            Status das Conexões
        </h2>
    </div>
    <div class="status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; padding: 1rem;">
        <div class="status-item" id="status-db">
            <span class="badge badge-success">Banco de Dados: Verificando...</span>
        </div>
        <div class="status-item" id="status-ai">
            <span class="badge badge-secondary">IA (Gemini/OpenAI): Verificando...</span>
        </div>
        <div class="status-item" id="status-datajud">
            <span class="badge badge-secondary">DataJud API: Verificando...</span>
        </div>
    </div>
  </section>

  <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Actions Card -->
    <div class="card">
      <div class="card-header" style="border-bottom:1px solid var(--legal-border); padding-bottom:1rem; margin-bottom:1rem;">
        <h2 style="font-size:1.25rem; margin:0;">Ações Rápidas</h2>
      </div>
      
      <?php if (!empty($message)): ?>
        <div class="alert alert-success" style="margin-bottom:1rem;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px"><polyline points="20 6 9 17 4 12"></polyline></svg>
          Importados: <?= View::e($message) ?>
        </div>
      <?php endif; ?>
      <?php if (!empty($error)): ?>
        <div class="alert alert-error" style="margin-bottom:1rem;">Erro ao processar ação</div>
      <?php endif; ?>

      <div style="display:flex; flex-direction:column; gap:1rem;">
        <form method="post" action="/admin/import">
          <input type="hidden" name="csrf" value="<?= View::e($csrf) ?>">
          <button class="btn btn-secondary" type="submit" style="width:100%; justify-content:center;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Importar JSON (Padrão)
          </button>
        </form>
        
        <form method="post" action="/admin/ai/classify">
          <input type="hidden" name="csrf" value="<?= View::e($csrf) ?>">
          <button class="btn btn-primary" type="submit" style="width:100%; justify-content:center;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px"><path d="M12 2a10 10 0 1 0 10 10H12V2z"></path><path d="M12 2a10 10 0 0 1 10 10h-10V2z" transform="rotate(45 12 12)"></path></svg>
            Classificar via IA
          </button>
        </form>
      </div>
    </div>

    <!-- Upload Card -->
    <div class="card">
      <div class="card-header" style="border-bottom:1px solid var(--legal-border); padding-bottom:1rem; margin-bottom:1rem;">
        <h2 style="font-size:1.25rem; margin:0;">Upload de Arquivo</h2>
      </div>
      <form method="post" action="/admin/upload" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= View::e($csrf) ?>">
        <div class="form-group">
          <label for="jsonFile">Arquivo JSON</label>
          <input type="file" id="jsonFile" name="json" accept="application/json,.json" required style="padding:0.5rem; border:1px solid var(--legal-border); border-radius:4px; width:100%;">
        </div>
        <div class="form-group">
          <label for="tabSelect">Aba / Tipo (Opcional)</label>
          <select id="tabSelect" name="tab" style="padding:0.5rem; border:1px solid var(--legal-border); border-radius:4px; width:100%;">
            <option value="">Detectar do JSON</option>
            <option value="acor">Processo Estrutural ACOR</option>
            <option value="dtxt">Processo Estrutural DTXT</option>
            <option value="acp">Ação Civil Pública</option>
            <option value="ap">Ação Popular</option>
            <option value="msc">Mandado De Segurança Coletivo</option>
          </select>
          <small class="text-muted" style="display:block; margin-top:4px;">Se selecionado, forçará a Classe do processo.</small>
        </div>
        <button class="btn btn-secondary" type="submit" style="width:100%; justify-content:center;">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
          Enviar e Importar
        </button>
      </form>
    </div>

    <!-- Bulk Operations Card -->
    <div class="card">
      <div class="card-header" style="border-bottom:1px solid var(--legal-border); padding-bottom:1rem; margin-bottom:1rem;">
        <h2 style="font-size:1.25rem; margin:0; color: #dc2626;">Operações em Massa</h2>
      </div>
      <p style="margin-bottom:1rem; font-size: 0.9rem; color: #666;">
        Gerencie exclusões e pagamentos em lote por período.
      </p>
      <a href="/admin/bulk-ops" class="btn btn-secondary" style="width:100%; justify-content:center; display: flex; align-items: center; border-color: #dc2626; color: #dc2626;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2-2v2"></path></svg>
        Acessar Ferramenta
      </a>
    </div>
  </div>

  <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    <!-- Categories Card -->
    <div class="card">
      <div class="card-header" style="border-bottom:1px solid var(--legal-border); padding-bottom:1rem; margin-bottom:1rem;">
        <h2 style="font-size:1.25rem; margin:0;">Gerenciar Categorias</h2>
      </div>
      <form method="post" action="/admin/category/create" style="margin-bottom:1.5rem; display:flex; gap:0.5rem;">
        <input type="hidden" name="csrf" value="<?= View::e($csrf) ?>">
        <input type="text" name="name" required placeholder="Nova categoria..." style="flex:1;">
        <button class="btn btn-secondary" type="submit">Criar</button>
      </form>
      
      <?php if (!empty($categories)): ?>
        <div class="table-responsive" style="max-height: 200px; overflow-y: auto;">
          <table class="table table-sm">
            <thead>
              <tr>
                <th style="width: 50px;">ID</th>
                <th>Nome</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($categories as $c): ?>
                <tr>
                  <td><?= View::e($c['id']) ?></td>
                  <td><?= View::e($c['name']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Assign Category Card -->
    <div class="card">
      <div class="card-header" style="border-bottom:1px solid var(--legal-border); padding-bottom:1rem; margin-bottom:1rem;">
        <h2 style="font-size:1.25rem; margin:0;">Atribuição Manual</h2>
      </div>
      <form method="post" action="/admin/category/assign">
        <input type="hidden" name="csrf" value="<?= View::e($csrf) ?>">
        <div class="form-group">
          <label for="record_id">ID do Registro</label>
          <input type="text" id="record_id" name="record_id" required placeholder="#ID">
        </div>
        <div class="form-group">
          <label for="category_id">Categoria</label>
          <select id="category_id" name="category_id" required>
            <option value="">Selecione...</option>
            <?php foreach (($categories ?? []) as $c): ?>
              <option value="<?= View::e($c['id']) ?>"><?= View::e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <button class="btn btn-secondary" type="submit" style="width:100%; justify-content:center;">Atribuir Categoria</button>
      </form>
    </div>
  </div>

  <section id="dashboardRoot" class="card">
    <div class="card-header" style="border-bottom: 1px solid var(--legal-border); padding-bottom: 1rem; margin-bottom: 1.5rem;">
      <h2 style="font-size: 1.5rem; margin: 0;">Dashboard Analítico</h2>
    </div>

    <form id="dashFilters" class="filter" action="#" onsubmit="return false" style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--legal-border); margin-bottom: 2rem;">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <div class="form-group" style="margin:0">
          <label style="font-size:0.85rem; color:var(--legal-muted);">Início</label>
          <input type="text" name="start" placeholder="YYYYMMDD">
        </div>
        <div class="form-group" style="margin:0">
          <label style="font-size:0.85rem; color:var(--legal-muted);">Fim</label>
          <input type="text" name="end" placeholder="YYYYMMDD">
        </div>
        <div class="form-group" style="margin:0">
          <label style="font-size:0.85rem; color:var(--legal-muted);">Tipo de Classe</label>
          <input type="text" name="type" placeholder="Ex: RE, HC">
        </div>
        <div class="form-group" style="margin:0">
          <label style="font-size:0.85rem; color:var(--legal-muted);">Status</label>
          <select name="status">
            <option value="">Todos</option>
            <option value="categorized">Categorizados</option>
            <option value="pending">Pendentes</option>
          </select>
        </div>
        <div class="form-group" style="margin:0; display:flex; gap:0.5rem; align-items:flex-end;">
          <button class="btn btn-primary" id="applyFilters" type="button" style="flex:1">Aplicar</button>
        </div>
      </div>
      <div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; display: flex; gap: 1rem;">
        <a class="btn btn-secondary btn-sm" id="exportCsv" href="#" style="font-size: 0.85rem;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
          Exportar CSV
        </a>
        <a class="btn btn-secondary btn-sm" id="exportPdf" href="#" style="font-size: 0.85rem;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
          Exportar PDF
        </a>
      </div>
    </form>

    <div class="indicators" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
      <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--legal-border); box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <div class="stat-label">Total de Registros</div>
        <div class="stat-value" id="indTotal">0</div>
      </div>
      <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--legal-border); box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <div class="stat-label" style="color: var(--legal-success);">Categorizados</div>
        <div class="stat-value" id="indCat" style="color: var(--legal-success);">0</div>
        <div class="sub" id="indPct" style="font-size: 0.9rem; color: var(--legal-muted);">0%</div>
      </div>
      <div class="stat-card" style="background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--legal-border); box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
        <div class="stat-label" style="color: var(--legal-gold);">Pendentes</div>
        <div class="stat-value" id="indPend" style="color: var(--legal-gold);">0</div>
      </div>
    </div>

    <div class="charts" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
      <div class="chart" style="background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--legal-border);">
        <h3 style="text-align: center; margin-bottom: 1.5rem; font-size: 1.1rem;">Distribuição por categorias</h3>
        <svg id="pieChart" viewBox="0 0 200 200" role="img" aria-label="Distribuição por categorias" style="max-height: 300px; width: 100%;"></svg>
      </div>
      <div class="chart" style="background: white; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--legal-border);">
        <h3 style="text-align: center; margin-bottom: 1.5rem; font-size: 1.1rem;">Evolução temporal</h3>
        <svg id="lineChart" viewBox="0 0 400 200" role="img" aria-label="Evolução temporal" style="max-height: 300px; width: 100%;"></svg>
      </div>
    </div>
  </section>
</div>
