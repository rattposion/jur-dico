<?php use App\Core\View; ?>
<div class="container mt-2">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2 class="m-0">Pesquisa Avançada</h2>
        <div>
            <a href="/records/export/csv?<?= http_build_query($_GET) ?>" class="btn btn-sm btn-outline-secondary">Exportar CSV</a>
            <a href="/records/export/pdf?<?= http_build_query($_GET) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">Exportar PDF</a>
        </div>
    </div>
    
    <div class="row g-0">
        <!-- Sidebar: History & Saved -->
        <div class="col-md-3 border-end bg-light p-3">
            <h5 class="mt-2 mb-3">Favoritos</h5>
            <div id="saved-searches-list" class="list-group list-group-flush small mb-4 bg-white rounded shadow-sm">
                <!-- Loaded via JS -->
                <span class="text-muted p-2">Carregando...</span>
            </div>
            
            <h5 class="mb-3">Histórico Recente</h5>
            <div id="history-list" class="list-group list-group-flush small bg-white rounded shadow-sm">
                 <!-- Loaded via JS -->
                 <span class="text-muted p-2">Carregando...</span>
            </div>
        </div>

        <!-- Main: Search Form & Results -->
        <div class="col-md-9 p-3">
            <form id="advanced-form" method="get" action="/records/advanced" class="p-4 bg-white rounded border mb-4 shadow-sm">
                
                <!-- Main Search with Autocomplete -->
                <div class="mb-3 position-relative">
                    <label class="form-label fw-bold">Palavras-chave</label>
                    <div class="input-group">
                        <span class="input-group-text"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg></span>
                        <input type="text" class="form-control" name="q" id="q-input" autocomplete="off" value="<?= View::e($q ?? '') ?>" placeholder='Ex: "dano moral" AND tribunal'>
                    </div>
                    <small class="text-muted">Suporta: "termo exato", parcial*, lógica AND, data:2023-2024</small>
                    <div id="autocomplete-suggestions" class="list-group position-absolute w-100 shadow" style="z-index: 1000; display:none; top: 100%;"></div>
                </div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small text-uppercase fw-bold text-muted">Tribunal / Órgão</label>
                        <input type="text" class="form-control form-control-sm" name="tribunal" value="<?= View::e($filters['tribunal'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-uppercase fw-bold text-muted">Juiz / Relator</label>
                        <input type="text" class="form-control form-control-sm" name="juiz" value="<?= View::e($filters['juiz'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small text-uppercase fw-bold text-muted">Número Processo</label>
                        <input type="text" class="form-control form-control-sm" name="numero" value="<?= View::e($filters['numero'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-uppercase fw-bold text-muted">Ano</label>
                        <input type="text" class="form-control form-control-sm" name="ano" placeholder="Ex: 2023" value="<?= View::e($filters['ano'] ?? '') ?>">
                    </div>
                    <!-- Date Range -->
                    <div class="col-md-3">
                        <label class="form-label small text-uppercase fw-bold text-muted">Data Início</label>
                        <input type="date" class="form-control form-control-sm" name="start" value="<?= View::e($filters['start'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-uppercase fw-bold text-muted">Data Fim</label>
                        <input type="date" class="form-control form-control-sm" name="end" value="<?= View::e($filters['end'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                         <label class="form-label small text-uppercase fw-bold text-muted">Categoria</label>
                         <select name="category" class="form-select form-select-sm">
                            <option value="">Todas</option>
                            <?php foreach($categories as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= (isset($filters['category']) && $filters['category'] == $c['id']) ? 'selected' : '' ?>><?= View::e($c['name']) ?></option>
                            <?php endforeach; ?>
                         </select>
                    </div>
                </div>
                
                <div class="mt-4 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-link btn-sm text-decoration-none" onclick="saveCurrentSearch()">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-1"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                        Salvar Filtros
                    </button>
                    <button type="submit" class="btn btn-primary px-4">
                        Pesquisar
                    </button>
                </div>
                
                <!-- Maintain sort params hidden if needed, or let them reset on new search? usually new search resets sort -->
            </form>

            <!-- Results Table -->
            <?php if (isset($items) && count($items) > 0): ?>
                <?php
                if (!empty($q)) {
                    $qClean = preg_replace('/data:\d{4}-\d{4}/', '', $q);
                    // Split by AND or comma (case insensitive AND)
                    $parts = preg_split('/(\s+AND\s+|,\s*)/i', $qClean, -1, PREG_SPLIT_NO_EMPTY);
                    foreach($parts as $p) {
                        $p = trim($p, " \"*");
                        if ($p && strlen($p) > 1) $terms[] = $p;
                    }
                }

                $makeSortLink = function($col) use ($sort, $dir) {
                    $newDir = ($sort === $col && $dir === 'ASC') ? 'DESC' : 'ASC';
                    $icon = '';
                    if ($sort === $col) {
                        $icon = $dir === 'ASC' ? ' ↑' : ' ↓';
                    }
                    $qs = array_merge($_GET, ['sort' => $col, 'dir' => $newDir]);
                    // Reset page to 1 when sorting
                    $qs['page'] = 1;
                    return '<a href="?' . http_build_query($qs) . '" class="text-dark text-decoration-none fw-bold">' . $col . $icon . '</a>'; // Simple label logic
                };
                // Custom labels
                $headers = [
                    'numeroProcesso' => 'Processo',
                    'nomeOrgaoJulgador' => 'Tribunal',
                    'ministroRelator' => 'Juiz',
                    'dataDecisao' => 'Data',
                    'category' => 'Status' // Not sortable by category name easily in simple SQL without join sort, but we allow it? paginate uses records table columns. category name is joined. 
                    // Record::paginate sorts by r.$sort. r.category is not valid. r.category_id is.
                    // Let's stick to allowedSorts: 'numeroProcesso', 'siglaClasse', 'ministroRelator', 'nomeOrgaoJulgador', 'dataDecisao'
                ];
                ?>
                <div class="table-responsive bg-white border rounded shadow-sm">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th><?= $makeSortLink('numeroProcesso') ?></th>
                                <th><?= $makeSortLink('nomeOrgaoJulgador') ?></th>
                                <th><?= $makeSortLink('ministroRelator') ?></th>
                                <th><?= $makeSortLink('dataDecisao') ?></th>
                                <th>Status</th>
                                <th class="text-end">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $r): ?>
                            <tr>
                                <td><a href="/records/view/<?= $r['id'] ?>" class="fw-bold text-decoration-none"><?= $highlight($r['numeroProcesso'], $terms) ?></a></td>
                                <td><small><?= $highlight($r['nomeOrgaoJulgador'], $terms) ?></small></td>
                                <td><small><?= $highlight($r['ministroRelator'], $terms) ?></small></td>
                                <td><small><?= $r['dataDecisao'] ? date('d/m/Y', strtotime($r['dataDecisao'])) : '-' ?></small></td>
                                <td>
                                    <?php if($r['category_id']): ?>
                                        <span class="badge bg-success" style="font-weight:500"><?= View::e($r['category']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark" style="font-weight:500">Pendente</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="/records/view/<?= $r['id'] ?>" class="btn btn-sm btn-light border">Ver Detalhes</a>
                                </td>
                            </tr>
                            <?php if(!empty($terms) && !empty($r['ementa'])): ?>
                            <tr>
                                <td colspan="6" class="border-0 pt-0 pb-3">
                                    <div class="snippet bg-light p-2 rounded border-start border-4 border-info">
                                        <small class="text-muted fw-bold">Trecho da Ementa:</small><br>
                                        <?= $getSnippet($r['ementa'], $terms) ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                 <div class="d-flex justify-content-center mt-3">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&<?= http_build_query(array_diff_key($_GET, ['page'=>1])) ?>" class="btn btn-outline-primary btn-sm me-2">Anterior</a>
                    <?php endif; ?>
                    <span class="align-self-center text-muted small">Página <?= $page ?></span>
                    <?php if (count($items) >= 20): ?>
                        <a href="?page=<?= $page + 1 ?>&<?= http_build_query(array_diff_key($_GET, ['page'=>1])) ?>" class="btn btn-outline-primary btn-sm ms-2">Próxima</a>
                    <?php endif; ?>
                </div>
            <?php elseif (isset($items)): ?>
                <div class="alert alert-info text-center">Nenhum registro encontrado para esta pesquisa.</div>
            <?php else: ?>
                 <div class="text-center text-muted mt-5">
                    <p>Utilize os filtros acima para pesquisar processos.</p>
                 </div>
            <?php endif; ?>
        </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadSavedSearches();
    loadHistory();
    setupAutocomplete();
});

function setupAutocomplete() {
    const input = document.getElementById('q-input');
    const suggestions = document.getElementById('autocomplete-suggestions');
    let timeout = null;

    input.addEventListener('input', function() {
        clearTimeout(timeout);
        const val = this.value;
        if (val.length < 2) {
            suggestions.style.display = 'none';
            return;
        }
        
        timeout = setTimeout(() => {
            fetch('/records/autocomplete?term=' + encodeURIComponent(val))
            .then(r => r.json())
            .then(data => {
                suggestions.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(item => {
                        const a = document.createElement('a');
                        a.className = 'list-group-item list-group-item-action small';
                        a.href = '#';
                        a.innerText = item;
                        a.onclick = (e) => {
                            e.preventDefault();
                            input.value = item;
                            suggestions.style.display = 'none';
                        };
                        suggestions.appendChild(a);
                    });
                    suggestions.style.display = 'block';
                } else {
                    suggestions.style.display = 'none';
                }
            });
        }, 300);
    });
    
    document.addEventListener('click', function(e) {
        if (e.target !== input && e.target !== suggestions) {
            suggestions.style.display = 'none';
        }
    });
}

function saveCurrentSearch() {
    const name = prompt("Dê um nome para identificar esta pesquisa nos Favoritos:");
    if (!name) return;
    
    const form = document.getElementById('advanced-form');
    const fd = new FormData(form);
    const criteria = {};
    fd.forEach((value, key) => {
        if (value) criteria[key] = value;
    });
    
    fetch('/records/save-search', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({name: name, criteria: criteria})
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) {
            loadSavedSearches();
            alert('Pesquisa salva com sucesso!');
        } else {
            alert('Erro ao salvar: ' + (res.error || 'Desconhecido'));
        }
    });
}

function loadSavedSearches() {
    fetch('/records/saved-searches')
    .then(r => r.json())
    .then(data => {
        const list = document.getElementById('saved-searches-list');
        list.innerHTML = '';
        if (data.length === 0) {
            list.innerHTML = '<span class="text-muted p-2 small">Nenhum favorito.</span>';
            return;
        }
        data.forEach(s => {
            const item = document.createElement('div');
            item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center p-2';
            const qs = new URLSearchParams(s.criteria).toString();
            item.innerHTML = `
                <a href="/records/advanced?${qs}" class="text-truncate text-decoration-none text-dark small" style="flex:1" title="${s.name}">${s.name}</a>
                <button class="btn btn-sm text-danger py-0 px-1" onclick="deleteSearch('${s.id}')">&times;</button>
            `;
            list.appendChild(item);
        });
    });
}

function deleteSearch(id) {
    if (!confirm('Remover este favorito?')) return;
    const fd = new FormData();
    fd.append('id', id);
    fetch('/records/delete-search', {method:'POST', body:fd})
    .then(r => r.json())
    .then(res => {
        if (res.ok) loadSavedSearches();
    });
}

function loadHistory() {
    fetch('/records/history')
    .then(r => r.json())
    .then(data => {
        const list = document.getElementById('history-list');
        list.innerHTML = '';
        if (data.length === 0) {
            list.innerHTML = '<span class="text-muted p-2 small">Sem histórico.</span>';
            return;
        }
        data.forEach(h => {
            const item = document.createElement('a');
            item.className = 'list-group-item list-group-item-action small p-2 text-truncate';
            
            let desc = h.term || '';
            const crit = h.criteria;
            if (crit.tribunal) desc += (desc?' | ':'') + 'Trib: ' + crit.tribunal;
            if (crit.juiz) desc += (desc?' | ':'') + 'Juiz: ' + crit.juiz;
            if (!desc) desc = 'Pesquisa Geral';
            
            const qs = new URLSearchParams(crit).toString();
            let fullQs = qs;
            if (h.term && !qs.includes('q=')) {
                fullQs += (fullQs ? '&' : '') + 'q=' + encodeURIComponent(h.term);
            }
            
            item.href = `/records/advanced?${fullQs}`;
            item.innerText = desc;
            item.title = desc;
            list.appendChild(item);
        });
    });
}
</script>
