<?php use App\Core\View; ?>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Operações em Massa</h2>
        <a href="/admin" class="btn btn-outline-secondary">Voltar ao Painel</a>
    </div>

    <div class="card shadow-sm border-danger">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0">Exclusão por Período (Retenção)</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">
                Esta ferramenta mantém apenas os registros dentro do período selecionado.
                <br>
                <strong>Atenção:</strong> Registros anteriores ao Ano Inicial e posteriores ao Ano Final serão EXCLUÍDOS permanentemente.
            </p>

            <form id="bulkForm">
                <div class="row g-3 align-items-end mb-3">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Ano Inicial</label>
                        <input type="number" id="startYear" class="form-control" placeholder="Ex: 2015" min="1900" max="2100" required>
                        <div class="form-text text-danger">Registros anteriores a este ano serão EXCLUÍDOS.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Ano Final</label>
                        <input type="number" id="endYear" class="form-control" placeholder="Ex: 2026" min="1900" max="2100" required>
                        <div class="form-text text-danger">Registros posteriores a este ano serão EXCLUÍDOS.</div>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-primary w-100" onclick="previewOperation()">
                            Simular Operação
                        </button>
                    </div>
                </div>
            </form>

            <div id="previewResult" class="d-none mt-4 p-4 bg-light rounded border">
                <h5 class="border-bottom pb-2 mb-3">Resumo da Operação</h5>
                
                <div class="row text-center mb-4">
                    <div class="col-md-4">
                        <div class="card border-danger h-100">
                            <div class="card-body">
                                <h3 class="text-danger" id="countDeleteOld">0</h3>
                                <p class="mb-0 text-muted">Excluir Antigos</p>
                                <small class="text-secondary">(< <span id="dispStart"></span>)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-success h-100">
                            <div class="card-body">
                                <h3 class="text-success" id="countKeep">0</h3>
                                <p class="mb-0 text-muted">Manter (Safe)</p>
                                <small class="text-secondary">(<span id="dispRange"></span>)</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-danger h-100">
                            <div class="card-body">
                                <h3 class="text-danger" id="countDeleteNew">0</h3>
                                <p class="mb-0 text-muted">Excluir Novos</p>
                                <small class="text-secondary">(> <span id="dispEnd"></span>)</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning d-flex align-items-center" role="alert">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Warning:"><use xlink:href="#exclamation-triangle-fill"/></svg>
                    <div>
                        Confirma que deseja executar esta operação? A exclusão não poderá ser desfeita.
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-danger btn-lg" id="btnExecute" onclick="executeOperation()">
                        EXECUTAR OPERAÇÃO
                    </button>
                </div>
            </div>

            <div id="loading" class="d-none text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Processando...</span>
                </div>
                <p class="mt-2 text-muted">Processando registros... Por favor, aguarde.</p>
            </div>

            <div id="resultSuccess" class="d-none mt-4 alert alert-success">
                <h4 class="alert-heading">Sucesso!</h4>
                <p>Operação concluída com êxito.</p>
                <hr>
                <p class="mb-0" id="resultMsg"></p>
            </div>
            
            <div id="resultError" class="d-none mt-4 alert alert-danger">
                <p id="errorMsg"></p>
            </div>
        </div>
    </div>
</div>

<script>
async function previewOperation() {
    const start = document.getElementById('startYear').value;
    const end = document.getElementById('endYear').value;

    if (!start || !end || parseInt(start) > parseInt(end)) {
        alert('Por favor, insira um intervalo de anos válido.');
        return;
    }

    document.getElementById('loading').classList.remove('d-none');
    document.getElementById('previewResult').classList.add('d-none');
    document.getElementById('resultSuccess').classList.add('d-none');
    document.getElementById('resultError').classList.add('d-none');

    try {
        const res = await fetch('/admin/bulk-ops/preview', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({start, end})
        });
        const data = await res.json();

        document.getElementById('loading').classList.add('d-none');

        if (res.ok && data.success) {
            document.getElementById('countDeleteOld').textContent = data.delete_old_count;
            document.getElementById('countKeep').textContent = data.keep_count;
            document.getElementById('countDeleteNew').textContent = data.delete_new_count;
            document.getElementById('dispStart').textContent = start;
            document.getElementById('dispEnd').textContent = end;
            document.getElementById('dispRange').textContent = start + ' - ' + end;
            document.getElementById('previewResult').classList.remove('d-none');
        } else {
            alert('Erro ao simular: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (e) {
        document.getElementById('loading').classList.add('d-none');
        alert('Erro de conexão');
    }
}

async function executeOperation() {
    if (!confirm('Tem certeza absoluta? Esta ação não pode ser desfeita.')) return;

    const start = document.getElementById('startYear').value;
    const end = document.getElementById('endYear').value;

    document.getElementById('btnExecute').disabled = true;
    document.getElementById('loading').classList.remove('d-none');
    document.getElementById('previewResult').classList.add('d-none');

    try {
        const res = await fetch('/admin/bulk-ops/execute', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({start, end})
        });
        const data = await res.json();

        document.getElementById('loading').classList.add('d-none');

        if (res.ok && data.success) {
            document.getElementById('resultMsg').textContent = `${data.deleted_old} registros excluídos (anteriores) e ${data.deleted_new} registros excluídos (posteriores).`;
            document.getElementById('resultSuccess').classList.remove('d-none');
        } else {
            document.getElementById('errorMsg').textContent = data.error || 'Erro ao executar operação';
            document.getElementById('resultError').classList.remove('d-none');
        }
    } catch (e) {
        document.getElementById('loading').classList.add('d-none');
        document.getElementById('errorMsg').textContent = 'Erro de conexão';
        document.getElementById('resultError').classList.remove('d-none');
    } finally {
        document.getElementById('btnExecute').disabled = false;
    }
}
</script>
