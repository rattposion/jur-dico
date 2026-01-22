<?php use App\Core\View; ?>
<div class="container mt-2">
  <div class="card">
    <div class="card-header-flex">
      <h2>Assistente de Importação</h2>
      <a href="/admin" class="btn btn-secondary btn-sm">Voltar ao Dashboard</a>
    </div>

    <form method="post" action="/admin/upload" enctype="multipart/form-data" id="mainForm">
        <input type="hidden" name="csrf" value="<?= View::e($csrf) ?>">

        <div id="step1-container">
            <div class="alert alert-info" style="margin-bottom: 1.5rem; background: #eff6ff; border: 1px solid #dbeafe; color: #1e40af; padding: 1rem; border-radius: 8px;">
                <h4 style="margin:0 0 0.5rem 0; font-size:1rem; display:flex; align-items:center; gap:0.5rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                    Instruções
                </h4>
                <ul style="margin:0; padding-left:1.5rem; font-size:0.9rem;">
                <li>Selecione o arquivo JSON contendo os registros processuais.</li>
                <li>Indique a qual aba/sessão os registros pertencem (ex: Ação Civil Pública).</li>
                <li>Verifique a prévia dos dados antes de confirmar a importação.</li>
                </ul>
            </div>

            <div class="filter-grid" style="grid-template-columns: 1fr;">
                <div class="filter-group">
                    <label for="record_type_select">Destino (Aba/Sessão) <span style="color:red">*</span></label>
                    <select id="record_type_select" name="record_type" class="form-select" required>
                        <option value="">Selecione o tipo de registro...</option>
                        <option value="acp">Ação Civil Pública</option>
                        <option value="ap">Ação Popular</option>
                        <option value="msc">Mandado de Segurança Coletivo</option>
                        <option value="acor">Processo Estrutural ACOR</option>
                        <option value="dtxt">Processo Estrutural DTXT</option>
                        <option value="general">Outros/Geral</option>
                    </select>
                    <small>Todos os registros do arquivo serão classificados com este tipo.</small>
                </div>

                <div class="filter-group">
                    <label for="jsonFile">Arquivo JSON <span style="color:red">*</span></label>
                    <input type="file" id="jsonFile" name="file" accept=".json" class="form-control" required>
                    <small>Formatos aceitos: .json (Array de objetos ou { items: [...] })</small>
                </div>
            </div>

            <div class="filter-actions" style="margin-top:1rem; border-top: 1px solid var(--legal-border); padding-top: 1rem;">
                <button type="button" class="btn btn-primary w-100 justify-center" id="btnPreview">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                    Carregar e Visualizar Prévia
                </button>
            </div>
        </div>

        <!-- Preview Section -->
        <div id="preview-section" class="wizard-preview-section">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
                <h3 style="margin:0;">Prévia dos Dados</h3>
                <span class="badge-class" id="previewBadge" style="font-size:0.9rem;"></span>
            </div>
            
            <div id="previewError" class="alert alert-danger" style="display:none; color: #dc2626; background: #fef2f2; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;"></div>
            
            <div class="table-responsive" style="margin-bottom: 2rem; border: 1px solid var(--legal-border); border-radius: 8px; max-height: 400px; overflow-y: auto;">
                <table class="table table-hover" id="previewTable">
                    <thead class="sticky-thead">
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Número Processo</th>
                            <th>Classe</th>
                            <th>Relator</th>
                            <th>Data Decisão</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="wizard-stats">
                <div class="wizard-stat-item">
                    <span>Total de Registros</span>
                    <strong id="totalRecords">0</strong>
                </div>
                 <div class="wizard-stat-item">
                    <span>Destino</span>
                    <strong style="color:var(--legal-royal);" id="selectedTypeDisplay">-</strong>
                </div>
                <div class="wizard-stat-item">
                    <span>Status JSON</span>
                    <strong style="color:var(--legal-success);">Válido</strong>
                </div>
            </div>
            
            <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                <button type="button" class="btn btn-secondary" id="btnCancel">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    Cancelar / Corrigir
                </button>
                <button type="submit" class="btn btn-primary" id="btnConfirm">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    Confirmar Importação
                </button>
            </div>
        </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnPreview = document.getElementById('btnPreview');
    const btnCancel = document.getElementById('btnCancel');
    const fileInput = document.getElementById('jsonFile');
    const typeSelect = document.getElementById('record_type_select');
    const step1 = document.getElementById('step1-container');
    const previewSection = document.getElementById('preview-section');
    const previewTableBody = document.querySelector('#previewTable tbody');
    const totalRecordsEl = document.getElementById('totalRecords');
    const selectedTypeDisplay = document.getElementById('selectedTypeDisplay');
    const previewBadge = document.getElementById('previewBadge');

    btnPreview.addEventListener('click', function() {
        // Validation
        if (!typeSelect.value) {
            alert('Por favor, selecione o destino (Aba/Sessão) dos registros.');
            typeSelect.focus();
            return;
        }
        if (!fileInput.files.length) {
            alert('Por favor, selecione um arquivo JSON.');
            fileInput.focus();
            return;
        }

        const file = fileInput.files[0];
        if (file.type !== 'application/json' && !file.name.toLowerCase().endsWith('.json')) {
            alert('Apenas arquivos JSON são permitidos neste assistente.');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            try {
                const json = JSON.parse(e.target.result);
                let records = [];
                
                // Handle both raw array and { items: [...] } format
                if (Array.isArray(json)) {
                    records = json;
                } else if (json.items && Array.isArray(json.items)) {
                    records = json.items;
                } else {
                    throw new Error('O arquivo JSON deve conter um array de objetos ou um objeto com a propriedade "items".');
                }

                if (records.length === 0) {
                    throw new Error('O arquivo JSON está vazio.');
                }

                // Check for required fields in the first few records
                const requiredFields = ['numeroProcesso', 'classe', 'relator', 'dataDecisao']; 
                // Note: Actual DB requirements might differ, but this is for UI feedback
                const sample = records[0];
                const missing = requiredFields.filter(f => !sample.hasOwnProperty(f) && !sample.hasOwnProperty('numero')); // 'numero' is alias for numeroProcesso sometimes
                
                // Show Preview
                renderPreview(records.slice(0, 10)); // Show top 10
                
                totalRecordsEl.textContent = records.length;
                const typeText = typeSelect.options[typeSelect.selectedIndex].text;
                selectedTypeDisplay.textContent = typeText;
                previewBadge.textContent = typeText;

                // Switch View
                step1.style.opacity = '0.5';
                step1.style.pointerEvents = 'none';
                previewSection.style.display = 'block';
                
                // Scroll to preview
                previewSection.scrollIntoView({ behavior: 'smooth' });

            } catch (err) {
                alert('Erro ao processar arquivo JSON: ' + err.message);
            }
        };
        reader.readAsText(file);
    });

    btnCancel.addEventListener('click', function() {
        step1.style.opacity = '1';
        step1.style.pointerEvents = 'auto';
        previewSection.style.display = 'none';
        fileInput.value = ''; // Clear file to force re-selection if needed? No, keep it.
        // Actually, user might want to change file, so keeping value is fine, but they need to re-click preview.
        step1.scrollIntoView({ behavior: 'smooth' });
    });

    function renderPreview(data) {
        previewTableBody.innerHTML = '';
        data.forEach((row, index) => {
            const tr = document.createElement('tr');
            
            const num = row.numeroProcesso || row.numero || '-';
            const classe = (row.siglaClasse || '') + ' ' + (row.descricaoClasse || row.classe || '');
            const relator = row.ministroRelator || row.relator || '-';
            let dataDec = row.dataDecisao || row.data_julgamento || '-';
            
            // Simple date format attempt
            if (dataDec !== '-' && dataDec.length >= 10) {
                try {
                    dataDec = new Date(dataDec).toLocaleDateString('pt-BR');
                } catch(e) {}
            }

            tr.innerHTML = `
                <td>${index + 1}</td>
                <td style="font-family:monospace; color:var(--legal-navy);">${escapeHtml(num)}</td>
                <td><span class="badge" style="background:#eef2ff; color:var(--legal-royal); padding:2px 8px; border-radius:4px; font-size:0.85rem;">${escapeHtml(classe)}</span></td>
                <td>${escapeHtml(relator)}</td>
                <td>${escapeHtml(dataDec)}</td>
            `;
            previewTableBody.appendChild(tr);
        });
    }

    function escapeHtml(text) {
        if (text == null) return '';
        return String(text)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
});
</script>
