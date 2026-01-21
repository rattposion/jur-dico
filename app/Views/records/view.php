<?php
use App\Core\View;
?>
<div class="container mt-2">
  <div class="card">
    <div class="card-header" style="border-bottom:1px solid var(--legal-border); padding-bottom:1rem; margin-bottom:1.5rem;">
      <div style="display:flex; justify-content:space-between; align-items:flex-start;">
        <div>
          <span class="badge" style="background:var(--legal-navy); color:white; padding:4px 10px; border-radius:4px; font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em;"><?= View::e($r['siglaClasse'] ?? '') ?></span>
          <h2 style="margin:0.5rem 0 0; font-size:1.5rem;"><?= View::e($r['descricaoClasse'] ?? 'Processo Sem Classe') ?></h2>
          <div class="text-muted" style="font-size:0.9rem; margin-top:0.2rem;">Processo nº <?= View::e($r['numeroProcesso'] ?? '') ?></div>
        </div>
        <div style="text-align:right;">
           <span class="text-muted" style="font-size:0.85rem; display:block;">ID do Registro</span>
           <span style="font-family:monospace; font-weight:600; font-size:1.1rem;">#<?= View::e($r['id'] ?? '') ?></span>
        </div>
      </div>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
      <div class="info-group">
        <label style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--legal-muted); margin-bottom:0.2rem;">Número de Registro</label>
        <div style="font-weight:500;"><?= View::e($r['numeroRegistro'] ?? '-') ?></div>
      </div>
      <div class="info-group">
        <label style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--legal-muted); margin-bottom:0.2rem;">Órgão Julgador</label>
        <div style="font-weight:500;"><?= View::e($r['nomeOrgaoJulgador'] ?? '-') ?></div>
      </div>
      <div class="info-group">
        <label style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--legal-muted); margin-bottom:0.2rem;">Ministro Relator</label>
        <div style="font-weight:500; color:var(--legal-navy);"><?= View::e($r['ministroRelator'] ?? '-') ?></div>
      </div>
      <div class="info-group">
        <label style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--legal-muted); margin-bottom:0.2rem;">Data Publicação</label>
        <div style="font-weight:500;"><?= View::e($r['dataPublicacao'] ?? '-') ?></div>
      </div>
      <div class="info-group">
        <label style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--legal-muted); margin-bottom:0.2rem;">Tipo de Decisão</label>
        <div style="font-weight:500;"><?= View::e($r['tipoDeDecisao'] ?? '-') ?></div>
      </div>
      <div class="info-group">
        <label style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--legal-muted); margin-bottom:0.2rem;">Data da Decisão</label>
        <div style="font-weight:500;"><?= View::e($r['dataDecisao'] ?? '-') ?></div>
      </div>
      <div class="info-group">
        <label style="font-size:0.8rem; text-transform:uppercase; letter-spacing:0.05em; color:var(--legal-muted); margin-bottom:0.2rem;">Categoria</label>
        <div style="font-weight:500;">
          <?php if($r['category']): ?>
            <span style="display:inline-flex; align-items:center; gap:4px; color:var(--legal-success); font-weight:600;">
              <span style="width:8px; height:8px; background:var(--legal-success); border-radius:50%;"></span>
              <?= View::e($r['category']) ?>
            </span>
          <?php else: ?>
            <span class="text-muted">Não categorizado</span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div style="margin-top:2rem;">
      <h3 style="font-size:1.1rem; border-bottom:1px solid var(--legal-border); padding-bottom:0.5rem; margin-bottom:1rem; color:var(--legal-navy);">Ementa</h3>
      <div class="textblock" style="background:#f8fafc; padding:1.5rem; border-radius:8px; border:1px solid var(--legal-border); white-space: pre-wrap; font-family:inherit; line-height:1.7; color:var(--legal-graphite);">
        <?= View::e($r['ementa'] ?? 'Não disponível') ?>
      </div>
    </div>

    <div style="margin-top:2rem;">
      <h3 style="font-size:1.1rem; border-bottom:1px solid var(--legal-border); padding-bottom:0.5rem; margin-bottom:1rem; color:var(--legal-navy);">Decisão Completa</h3>
      <div class="textblock" style="background:#f8fafc; padding:1.5rem; border-radius:8px; border:1px solid var(--legal-border); white-space: pre-wrap; font-family:inherit; line-height:1.7; color:var(--legal-graphite);">
        <?= View::e($r['decisao'] ?? 'Não disponível') ?>
      </div>
    </div>
    
    <div style="margin-top:2rem; padding-top:1rem; border-top:1px solid var(--legal-border);">
      <a href="/records" class="btn btn-secondary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px;"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        Voltar para Lista
      </a>
    </div>
  </div>
</div>
