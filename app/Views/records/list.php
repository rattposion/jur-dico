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
<!-- Full Width Layout Container -->
<div style="display: flex; flex-direction: column; min-height: 100%; width: 100%;">
    
    <!-- Header Section -->
    <div style="padding: 1.5rem 2rem; border-bottom: 1px solid var(--legal-border);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem;">
            <h2 style="margin:0; font-size:1.8rem; color: var(--legal-navy);">Registros Processuais</h2>
            <span class="text-muted" style="font-size:0.9rem;">Total: <?= View::e($total ?? 0) ?> registros</span>
        </div>

        <!-- Navigation Tabs -->
        <div class="tabs-scroll-container" style="overflow-x: auto; -webkit-overflow-scrolling: touch; padding-bottom: 4px;">
          <div class="tabs-nav" style="display: inline-flex; gap: 0.5rem; min-width: 100%;">
            <?php
              $currentTab = $_GET['tab'] ?? '';
              $tabs = [
                  '' => 'Todos',
                  'acor' => 'Processo Estrutural ACOR',
                  'dtxt' => 'Processo Estrutural DTXT',
                  'acp' => 'Ação Civil Pública',
                  'ap' => 'Ação Popular',
                  'msc' => 'Mandado De Segurança Coletivo'
              ];
              
              foreach ($tabs as $key => $label):
                  $active = ($currentTab === $key);
                  $countKey = $key === '' ? 'all_count' : $key;
                  $count = $tabCounts[$countKey] ?? 0;
                  
                  $params = $_GET;
                  if ($key === '') {
                      unset($params['tab']);
                  } else {
                      $params['tab'] = $key;
                  }
                  $params['page'] = 1; // Reset page
                  
                  $url = '?' . http_build_query($params);
            ?>
              <a href="<?= $url ?>" 
                 class="tab-link <?= $active ? 'active' : '' ?>"
                 style="white-space: nowrap; padding: 0.75rem 1.25rem; text-decoration: none; color: <?= $active ? 'var(--legal-royal)' : 'var(--legal-muted)' ?>; font-weight: <?= $active ? '600' : '500' ?>; border-bottom: 2px solid <?= $active ? 'var(--legal-royal)' : 'transparent' ?>; transition: all 0.2s ease;">
                  <?= View::e($label) ?> <span style="font-size: 0.8em; opacity: 0.8; margin-left: 4px; background: <?= $active ? 'rgba(79, 70, 229, 0.1)' : 'rgba(0,0,0,0.05)' ?>; padding: 2px 6px; border-radius: 99px;"><?= $count ?></span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
    </div>

    <!-- Filter Section (Gray Band) -->
    <div style="background: #f8fafc; padding: 1.5rem 2rem; border-bottom: 1px solid var(--legal-border);">
        <form method="get" action="/records" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin: 0;">
          <?php if(!empty($_GET['tab'])): ?>
            <input type="hidden" name="tab" value="<?= View::e($_GET['tab']) ?>">
          <?php endif; ?>
          <div class="form-group" style="margin:0">
            <label for="q" style="font-weight: 500; margin-bottom: 0.5rem; display: block; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--legal-muted);">Pesquisa</label>
            <div class="input-group">
                <input type="search" id="q" name="q" class="form-control" placeholder="Ex: 'dano moral' AND tribunal" value="<?= View::e($q ?? '') ?>" style="border-radius: 4px;">
            </div>
          </div>
          
          <div class="form-group" style="margin:0">
            <label for="numero" style="font-weight: 500; margin-bottom: 0.5rem; display: block; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--legal-muted);">Número</label>
            <input type="text" id="numero" name="numero" class="form-control" placeholder="Número do processo..." value="<?= View::e($_GET['numero'] ?? '') ?>" style="border-radius: 4px;">
          </div>

          <div class="form-group" style="margin:0">
            <label for="relator" style="font-weight: 500; margin-bottom: 0.5rem; display: block; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--legal-muted);">Relator</label>
            <input type="text" id="relator" name="relator" class="form-control" placeholder="Nome do Ministro" value="<?= View::e($_GET['relator'] ?? '') ?>" style="border-radius: 4px;">
          </div>

          <div class="form-group" style="margin:0">
            <label for="start" style="font-weight: 500; margin-bottom: 0.5rem; display: block; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--legal-muted);">Data Início</label>
            <input type="date" id="start" name="start" class="form-control" value="<?= View::e($_GET['start'] ?? '') ?>" style="border-radius: 4px;">
          </div>

          <div class="form-group" style="margin:0">
            <label for="end" style="font-weight: 500; margin-bottom: 0.5rem; display: block; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--legal-muted);">Data Fim</label>
            <input type="date" id="end" name="end" class="form-control" value="<?= View::e($_GET['end'] ?? '') ?>" style="border-radius: 4px;">
          </div>

          <div class="form-group" style="margin:0">
            <label for="category" style="font-weight: 500; margin-bottom: 0.5rem; display: block; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--legal-muted);">Categoria</label>
            <select id="category" name="category" class="form-select" style="border-radius: 4px;">
              <option value="">Todas as categorias</option>
              <option value="uncategorized" <?= (isset($_GET['category']) && $_GET['category'] === 'uncategorized') ? 'selected' : '' ?>>
                Sem Categoria (<?= $categoryCounts['uncategorized'] ?? 0 ?>)
              </option>
              <?php foreach (($categories ?? []) as $c): ?>
                <?php $count = $categoryCounts[$c['id']] ?? 0; ?>
                <option value="<?= View::e($c['id']) ?>" <?= (isset($_GET['category']) && $_GET['category'] == $c['id']) ? 'selected' : '' ?>>
                  <?= View::e($c['name']) ?> (<?= $count ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group" style="margin:0; display:flex; align-items:flex-end;">
            <button class="btn btn-primary" type="submit" style="width:100%; border-radius: 4px;">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:8px"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
              Filtrar
            </button>
          </div>
        </form>
    </div>

    <!-- Data Table -->
    <div id="recordsRoot" class="table-responsive" data-csrf="<?= View::e($csrf ?? '') ?>" data-admin="<?= !empty($user) && (($user['role'] ?? '')==='admin') ? '1' : '0' ?>" style="flex: 1;">
      <table class="table table-hover" style="margin: 0; border-collapse: collapse; width: 100%;">
        <thead style="background: white; position: sticky; top: 0; z-index: 10; border-bottom: 2px solid var(--legal-border);">
          <tr>
            <th style="padding: 1rem; text-align: center; white-space: nowrap; font-size: 0.85rem; text-transform: uppercase; color: var(--legal-muted);">CÓD. ÓRGÃO</th>
            <th style="padding: 1rem; text-align: center; white-space: nowrap; font-size: 0.85rem; text-transform: uppercase; color: var(--legal-muted);">CLASSE</th>
            <th style="padding: 1rem; text-align: center; white-space: nowrap; font-size: 0.85rem; text-transform: uppercase; color: var(--legal-muted);">NÚMERO</th>
            <th style="padding: 1rem; text-align: center; white-space: nowrap; font-size: 0.85rem; text-transform: uppercase; color: var(--legal-muted);">RELATOR</th>
            <th style="padding: 1rem; text-align: center; white-space: nowrap; font-size: 0.85rem; text-transform: uppercase; color: var(--legal-muted);">DATA JULGAMENTO</th>
            <th style="padding: 1rem; text-align: center; white-space: nowrap; font-size: 0.85rem; text-transform: uppercase; color: var(--legal-muted);">DATA PUBLICAÇÃO</th>
            <th style="padding: 1rem; text-align: center; min-width: 250px; font-size: 0.85rem; text-transform: uppercase; color: var(--legal-muted);">EMENTA</th>
            <th style="padding: 1rem; text-align: center; white-space: nowrap; font-size: 0.85rem; text-transform: uppercase; color: var(--legal-muted);">CATEGORIA</th>
            <th style="padding: 1rem; text-align: center; white-space: nowrap; font-size: 0.85rem; text-transform: uppercase; color: var(--legal-muted);">AÇÃO</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($items)): ?>
            <tr>
              <td colspan="8" class="text-center text-muted" style="padding: 3rem;">
                Nenhum registro encontrado com os filtros selecionados.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($items as $row): ?>
              <tr data-id="<?= View::e($row['id']) ?>" style="border-bottom: 1px solid var(--legal-border);">
                <!-- codOrgaoJulgador -->
                <td class="text-muted" style="padding: 1rem; text-align: center; vertical-align: middle;">
                  <?= View::e($row['codOrgaoJulgador'] ?? '-') ?>
                </td>

                <!-- Classe -->
                <td style="padding: 1rem; text-align: center; vertical-align: middle; white-space: nowrap;">
                  <span class="badge" style="background:#eef2ff; color:var(--legal-royal); padding:4px 8px; border-radius:4px; font-size:0.85rem; font-weight:500;">
                    <?= View::e($row['siglaClasse'] ?? '-') ?>
                  </span>
                </td>
                
                <!-- número (Link) -->
                <td style="padding: 1rem; text-align: center; vertical-align: middle; white-space: nowrap;">
                   <a href="/records/view/<?= View::e($row['id']) ?>" style="font-weight:600; color:var(--legal-navy); text-decoration:none;">
                     <?= $highlight($row['numeroProcesso'], $terms) ?>
                   </a>
                </td>

                <!-- relator -->
                <td style="padding: 1rem; text-align: center; vertical-align: middle; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= View::e($row['ministroRelator'] ?? '') ?>">
                    <?= $highlight($row['ministroRelator'], $terms) ?>
                </td>

                <!-- dataJulgamento -->
                <td style="padding: 1rem; text-align: center; vertical-align: middle; white-space: nowrap;">
                  <?php 
                    $date = $row['dataDecisao']; 
                    echo $date ? date('d/m/Y', strtotime($date)) : '-';
                  ?>
                </td>

                <!-- dataPublicacao -->
                <td style="padding: 1rem; text-align: center; vertical-align: middle; white-space: nowrap;">
                  <?php 
                    $datePub = $row['dataPublicacao'] ?? null; 
                    echo $datePub ? date('d/m/Y', strtotime($datePub)) : '-';
                  ?>
                </td>

                <!-- Ementa -->
                <td style="padding: 1rem; text-align: center; vertical-align: middle; max-width: 300px; font-size: 0.9rem;">
                    <div style="color: #475569; display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; text-align: center;">
                        <?= $highlight(mb_substr($row['ementa'] ?? '', 0, 150) . (mb_strlen($row['ementa'] ?? '') > 150 ? '...' : ''), $terms) ?>
                    </div>
                </td>

                <!-- Categoria -->
                <td style="padding: 1rem; text-align: center; vertical-align: middle; white-space: nowrap;">
                   <?php if (!empty($categories) && !empty($user) && (($user['role'] ?? '')==='admin')): ?>
                        <select onchange="updateRecordCategory('<?= View::e($row['id']) ?>', this.value)" 
                                onclick="event.stopPropagation();"
                                style="font-size:0.8rem; font-weight:600; color:var(--legal-royal); border: 1px solid var(--legal-border); padding: 2px 4px; border-radius: 4px; max-width: 150px;">
                          <option value="">Sem Categoria</option>
                          <?php foreach ($categories as $cat): ?>
                            <option value="<?= View::e($cat['id']) ?>" <?= ($row['category_id'] == $cat['id']) ? 'selected' : '' ?>>
                              <?= View::e($cat['name']) ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <?php if($row['category']): ?>
                          <span style="font-size:0.8rem; font-weight:600; color:var(--legal-royal);">
                            <?= View::e($row['category']) ?>
                          </span>
                        <?php else: ?>
                          <span class="text-muted" style="font-size:0.8rem;">-</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>

                <!-- açao -->
                <td style="padding: 1rem; text-align: center; vertical-align: middle; white-space: nowrap;">
                  <div style="display: flex; gap: 4px; justify-content: center;">
                    <a href="/records/view/<?= View::e($row['id']) ?>" class="btn btn-sm btn-outline-primary" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                      Ver
                    </a>
                    <?php if (!empty($user) && (($user['role'] ?? '') === 'admin')): ?>
                      <button onclick="deleteRecord('<?= View::e($row['id']) ?>')" class="btn btn-sm btn-outline-danger" style="font-size: 0.8rem; padding: 0.25rem 0.5rem;">
                        Excluir
                      </button>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              
              <!-- Snippet Row (only if relevant terms matched outside visible columns) -->
              <?php if(!empty($terms)): ?>
                <?php 
                   // Since we show ementa and decisao now, we might not need this snippet row as much, 
                   // but let's keep it for very long matches or other fields.
                   // However, the user request layout is very dense.
                   // I'll keep it but span all columns.
                   $snippet = $getSnippet($row['ementa'] ?? '', $terms); // We show snippet in cell, but maybe context is needed?
                ?>
              <?php endif; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php 
      $totalPages = (int)ceil(($total ?? 0)/20); 
      if ($totalPages > 1): 
        $makePageLink = function($p) {
            $params = $_GET;
            $params['page'] = $p;
            return '?' . http_build_query($params);
        };
    ?>
      <div class="pagination" style="display:flex; justify-content:center; gap:0.5rem; padding: 2rem; background: #fff; border-top: 1px solid var(--legal-border);">
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

<script>
  function updateRecordCategory(recordId, categoryId) {
    const csrfToken = document.getElementById('recordsRoot').dataset.csrf || '';
    
    fetch('/records/update-category', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `record_id=${encodeURIComponent(recordId)}&category_id=${encodeURIComponent(categoryId)}&csrf=${encodeURIComponent(csrfToken)}`
    })
    .then(response => {
      if (response.ok) {
        // Optional: show toast success
        console.log('Category updated');
      } else {
        alert('Erro ao atualizar categoria');
      }
    })
    .catch(err => console.error(err));
  }

  function deleteRecord(recordId) {
    if (!confirm('Tem certeza que deseja excluir este registro? Esta ação não pode ser desfeita.')) {
      return;
    }

    const csrfToken = document.getElementById('recordsRoot').dataset.csrf || '';

    fetch('/records/delete', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: `id=${encodeURIComponent(recordId)}&csrf=${encodeURIComponent(csrfToken)}`
    })
    .then(response => {
      if (response.ok) {
        // Remove row from table
        const row = document.querySelector(`tr[data-id="${recordId}"]`);
        if (row) row.remove();
      } else {
        alert('Erro ao excluir registro');
      }
    })
    .catch(err => console.error(err));
  }
</script>
