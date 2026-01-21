<?php
use App\Core\View;
?>
<div class="container mt-2">
  <section class="hero-card">
    <h1 class="hero-title">Plataforma Jurídica</h1>
    <p class="hero-text">Gerencie seus registros processuais, analise dados e obtenha insights estratégicos a partir de documentos estruturados.</p>
    <div class="hero-actions">
      <a class="btn btn-hero-primary" href="/records">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
        Explorar Registros
      </a>
      <a class="btn btn-hero-secondary" href="/admin">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
        Painel Administrativo
      </a>
    </div>
  </section>

  <section id="systemStatus" class="card" aria-live="polite">
    <div class="card-header">
      <h2 style="font-size: 1.25rem; margin: 0; display: flex; align-items: center; gap: 0.5rem;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" color="var(--legal-royal)"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
        Status das Conexões
      </h2>
    </div>
    <div class="status-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; padding: 1rem;">
      <!-- Populated by JS -->
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

  <section id="dashboardRoot" class="card">
    <div class="card-header">
      <h2 style="font-size: 1.5rem; margin: 0;">Dashboard Analítico</h2>
    </div>

    <form id="dashFilters" class="filter" action="#" onsubmit="return false" style="background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--legal-border); margin-bottom: 2rem;">
      <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <div class="form-group" style="margin:0">
          <label class="text-muted" style="font-size:0.85rem;">Início</label>
          <input type="text" name="start" placeholder="YYYYMMDD">
        </div>
        <div class="form-group" style="margin:0">
          <label class="text-muted" style="font-size:0.85rem;">Fim</label>
          <input type="text" name="end" placeholder="YYYYMMDD">
        </div>
        <div class="form-group" style="margin:0">
          <label class="text-muted" style="font-size:0.85rem;">Tipo de Classe</label>
          <input type="text" name="type" placeholder="Ex: RE, HC">
        </div>
        <div class="form-group" style="margin:0">
          <label class="text-muted" style="font-size:0.85rem;">Status</label>
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
        <a class="btn btn-secondary" id="exportCsv" href="#" style="font-size: 0.85rem;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
          Exportar CSV
        </a>
        <a class="btn btn-secondary" id="exportPdf" href="#" style="font-size: 0.85rem;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:5px"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="12" y1="18" x2="12" y2="12"></line><line x1="9" y1="15" x2="15" y2="15"></line></svg>
          Exportar PDF
        </a>
      </div>
    </form>

    <div class="indicators grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
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

    <div class="charts grid" style="grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 2rem;">
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
