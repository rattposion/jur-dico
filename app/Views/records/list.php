<?php
use App\Core\View;

// Helper logic for highlighting (ported from advanced search)
$terms = [];
if (!empty($q)) {
    $qClean = preg_replace('/data:\d{4}-\d{4}/', '', $q);
    // Split by AND or comma (case insensitive AND)
    $parts = preg_split('/(\s+AND\s+|,\s*)/i', $qClean, -1, PREG_SPLIT_NO_EMPTY);
    foreach($parts as $p) {
        $p = trim($p, " \"*");
        if ($p && strlen($p) > 1) $terms[] = $p;
    }
}

$highlight = function($text, $terms) {
    if (empty($text) || empty($terms)) return View::e($text ?? '');
    
    $safeText = View::e($text);
    foreach ($terms as $term) {
        // Highlight term (case insensitive)
        $safeText = preg_replace(
            '/(' . preg_quote($term, '/') . ')/iu', 
            '<mark style="background-color: #fef08a; padding: 0 2px; border-radius: 2px; font-weight: bold;">$1</mark>', 
            $safeText
        );
    }
    return $safeText;
};

$getSnippet = function($text, $terms) {
    if (empty($text) || empty($terms)) return '';
    
    // Find first occurrence of any term
    $firstPos = -1;
    foreach ($terms as $term) {
        $pos = stripos($text, $term);
        if ($pos !== false) {
            if ($firstPos === -1 || $pos < $firstPos) {
                $firstPos = $pos;
            }
        }
    }
    
    if ($firstPos === -1) return ''; // No terms found in text
    
    // Extract context
    $start = max(0, $firstPos - 100);
    $length = 300; // Show 300 chars
    $snippet = substr($text, $start, $length);
    
    if ($start > 0) $snippet = '...' . $snippet;
    if (($start + $length) < strlen($text)) $snippet .= '...';
    
    // Highlight terms in snippet
    $safeSnippet = View::e($snippet);
    foreach ($terms as $term) {
        $safeSnippet = preg_replace(
            '/(' . preg_quote($term, '/') . ')/iu', 
            '<mark style="background-color: #fef08a; padding: 0 2px; border-radius: 2px; font-weight: bold;">$1</mark>', 
            $safeSnippet
        );
    }
    
    return $safeSnippet;
};
?>
<div class="container mt-2">
  <div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--legal-border); padding-bottom:1rem; margin-bottom:1.5rem;">
      <h2 style="margin:0; font-size:1.5rem;">Registros Processuais</h2>
      <span class="text-muted" style="font-size:0.9rem;">Total: <?= View::e($total ?? 0) ?> registros</span>
    </div>

    <form class="filter-form mb-1" method="get" action="/records" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem; background: #f8fafc; padding: 1.5rem; border-radius: 8px; border: 1px solid var(--legal-border);">
      <div class="form-group" style="margin:0">
        <label for="q" style="font-weight: 500; margin-bottom: 0.5rem; display: block;">Palavra-chave</label>
        <div class="input-group">
            <input type="search" id="q" name="q" class="form-control" placeholder="Ex: 'dano moral' AND tribunal" value="<?= View::e($q ?? '') ?>">
        </div>
        <small class="text-muted" style="font-size: 0.75rem;">Suporta: "termo", AND, , (vírgula)</small>
      </div>
      
      <div class="form-group" style="margin:0">
        <label for="type" style="font-weight: 500; margin-bottom: 0.5rem; display: block;">Classe</label>
        <input type="text" id="type" name="type" class="form-control" placeholder="Ex: HC, RE..." value="<?= View::e($_GET['type'] ?? '') ?>">
      </div>

      <div class="form-group" style="margin:0">
        <label for="relator" style="font-weight: 500; margin-bottom: 0.5rem; display: block;">Relator</label>
        <input type="text" id="relator" name="relator" class="form-control" placeholder="Nome do Ministro" value="<?= View::e($_GET['relator'] ?? '') ?>">
      </div>

      <div class="form-group" style="margin:0">
        <label for="start" style="font-weight: 500; margin-bottom: 0.5rem; display: block;">Data Início</label>
        <input type="date" id="start" name="start" class="form-control" value="<?= View::e($_GET['start'] ?? '') ?>">
      </div>

      <div class="form-group" style="margin:0">
        <label for="end" style="font-weight: 500; margin-bottom: 0.5rem; display: block;">Data Fim</label>
        <input type="date" id="end" name="end" class="form-control" value="<?= View::e($_GET['end'] ?? '') ?>">
      </div>

      <div class="form-group" style="margin:0">
        <label for="category" style="font-weight: 500; margin-bottom: 0.5rem; display: block;">Categoria</label>
        <select id="category" name="category" class="form-select">
          <option value="">Todas as categorias</option>
          <?php foreach (($categories ?? []) as $c): ?>
            <option value="<?= View::e($c['id']) ?>" <?= (isset($_GET['category']) && $_GET['category'] == $c['id']) ? 'selected' : '' ?>>
              <?= View::e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group" style="margin:0; display:flex; align-items:flex-end;">
        <button class="btn btn-primary" type="submit" style="width:100%">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
          Filtrar Resultados
        </button>
      </div>
    </form>

    <div id="recordsRoot" class="table-responsive" data-csrf="<?= View::e($csrf ?? '') ?>" data-admin="<?= !empty($user) && (($user['role'] ?? '')==='admin') ? '1' : '0' ?>">
      <table class="table table-hover">
        <thead>
          <tr>
            <th style="width: 80px;">ID</th>
            <th>Processo</th>
            <th>Classe</th>
            <th>Órgão Julgador</th>
            <th>Relator</th>
            <th>Data Decisão</th>
            <th>Categoria</th>
            <th class="text-right">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($items)): ?>
            <tr>
              <td colspan="8" class="text-center text-muted" style="padding: 2rem;">
                Nenhum registro encontrado com os filtros selecionados.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($items as $row): ?>
              <tr data-id="<?= View::e($row['id']) ?>">
                <td>
                  <a href="/records/view/<?= View::e($row['id']) ?>" style="font-weight:600; font-family:monospace;">
                    #<?= View::e($row['id']) ?>
                  </a>
                </td>
                <td>
                  <span style="font-weight:500; color:var(--legal-navy)"><?= $highlight($row['numeroProcesso'], $terms) ?></span>
                </td>
                <td>
                  <span class="badge" style="background:#eef2ff; color:var(--legal-royal); padding:2px 8px; border-radius:4px; font-size:0.85rem; font-weight:500;">
                    <?= View::e(($row['siglaClasse'] ?? '') . ' ' . ($row['descricaoClasse'] ?? '')) ?>
                  </span>
                </td>
                <td><?= $highlight($row['nomeOrgaoJulgador'] ?? '-', $terms) ?></td>
                <td><?= $highlight($row['ministroRelator'], $terms) ?></td>
                <td>
                  <?php 
                    $date = $row['dataDecisao']; 
                    echo $date ? date('d/m/Y', strtotime($date)) : '-';
                  ?>
                </td>
                <td>
                  <?php if (!empty($categories) && !empty($user) && (($user['role'] ?? '')==='admin')): ?>
                    <select name="category" aria-label="Categoria" style="padding: 4px 8px; font-size: 0.9rem; border-color: #cbd5e1; border-radius: 4px;">
                      <option value="">Sem categoria</option>
                      <?php foreach ($categories as $c): ?>
                        <option value="<?= View::e($c['id']) ?>" <?= (int)($row['category_id'] ?? 0) === (int)$c['id'] ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  <?php else: ?>
                    <?php if($row['category']): ?>
                      <span style="display:inline-flex; align-items:center; gap:4px;">
                        <span style="width:6px; height:6px; background:var(--legal-success); border-radius:50%;"></span>
                        <?= View::e($row['category']) ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
                <td class="text-right">
                  <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
                    <a href="/records/view/<?= View::e($row['id']) ?>" class="btn btn-secondary btn-sm" title="Visualizar Detalhes">
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    </a>
                    <?php if (!empty($user) && (($user['role'] ?? '')==='admin')): ?>
                      <button class="btn btn-danger btn-sm btn-del" type="button" title="Excluir">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                      </button>
                    <?php endif; ?>
                  </div>
                  <span class="msg" aria-live="polite" style="display:block; font-size:0.8rem; margin-top:4px;"></span>
                </td>
              </tr>
              <!-- Snippet Row if match in ementa/decisao -->
              <?php if(!empty($terms)): ?>
                <?php 
                   $snippet = $getSnippet($row['ementa'] ?? '', $terms);
                   if (!$snippet) $snippet = $getSnippet($row['decisao'] ?? '', $terms);
                ?>
                <?php if ($snippet): ?>
                <tr>
                    <td colspan="8" class="border-0 pt-0 pb-3" style="background-color: #fcfcfc;">
                        <div style="background-color: #f1f5f9; padding: 0.75rem; border-radius: 4px; border-left: 4px solid var(--legal-royal); font-size: 0.9rem; color: #475569; margin: 0 1rem;">
                            <span style="font-weight: 600; color: var(--legal-navy); display: block; margin-bottom: 2px;">Trecho Relevante:</span>
                            <?= $snippet ?>
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php 
      $totalPages = (int)ceil(($total ?? 0)/20); 
      if ($totalPages > 1): 
        // Helper to build pagination links keeping all params
        $makePageLink = function($p) {
            $params = $_GET;
            $params['page'] = $p;
            return '?' . http_build_query($params);
        };
    ?>
      <div class="pagination" style="display:flex; justify-content:center; gap:0.5rem; margin-top:2rem;">
        <?php if ($page > 1): ?>
             <a class="btn btn-secondary" href="<?= $makePageLink($page - 1) ?>">Anterior</a>
        <?php endif; ?>
        
        <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        if ($start > 1) echo '<span style="align-self:center">...</span>';
        ?>

        <?php for ($i=$start; $i<= $end; $i++): ?>
          <a class="btn <?= $i === ($page ?? 1) ? 'btn-primary' : 'btn-secondary' ?>" 
             href="<?= $makePageLink($i) ?>"
             style="min-width: 40px;">
            <?= $i ?>
          </a>
        <?php endfor; ?>
        
        <?php if ($end < $totalPages) echo '<span style="align-self:center">...</span>'; ?>

        <?php if ($page < $totalPages): ?>
             <a class="btn btn-secondary" href="<?= $makePageLink($page + 1) ?>">Próxima</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
