<?php use App\Core\View; ?>
<div class="container">
    <div class="header-content mb-2">
        <div>
            <h1>Navegador de Banco de Dados IA</h1>
            <p class="text-muted">Análise autônoma e verificação de integridade dos processos judiciais.</p>
        </div>
        <div>
            <button id="runScan" class="btn btn-primary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                Executar Varredura
            </button>
        </div>
    </div>

    <div class="grid mb-2">
        <div class="card stat-card">
            <div class="stat-value" id="st-total">-</div>
            <div class="stat-label">Total de Processos</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="st-ai">-</div>
            <div class="stat-label">Analisados por IA</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="st-issues" style="color:var(--legal-danger)">-</div>
            <div class="stat-label">Problemas Detectados</div>
        </div>
        <div class="card stat-card">
            <div class="stat-value" id="st-conf">-</div>
            <div class="stat-label">Baixa Confiança</div>
        </div>
    </div>

    <div class="chat-viewport split-view" style="height: 500px; border: 1px solid var(--legal-border); border-radius: 8px;">
        <!-- Left: List of Issues -->
        <div class="split-col col-left" style="flex: 1; max-width: 40%;">
            <div class="col-header">
                <h4>Relatório de Integridade</h4>
            </div>
            <div class="col-content" id="issuesList">
                <div class="text-center text-muted p-2">Clique em "Executar Varredura" para iniciar.</div>
            </div>
        </div>

        <!-- Right: Detail View -->
        <div class="split-col col-right" style="flex: 2; background: #f8fafc;">
            <div class="col-header">
                <h4>Detalhamento do Processo</h4>
            </div>
            <div class="col-content" id="detailView">
                <div class="text-center text-muted mt-2">Selecione um item à esquerda para ver detalhes.</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('runScan');
    const issuesList = document.getElementById('issuesList');
    const detailView = document.getElementById('detailView');

    const renderIssues = (items) => {
        issuesList.innerHTML = '';
        if (items.length === 0) {
            issuesList.innerHTML = '<div class="alert alert-success">Nenhum problema crítico detectado.</div>';
            return;
        }
        
        items.forEach(item => {
            const el = document.createElement('div');
            el.className = 'card p-2 mb-1 cursor-pointer hover-bg';
            el.style.cursor = 'pointer';
            el.innerHTML = `
                <div class="flex justify-between">
                    <strong>${item.numeroProcesso || 'Sem Número'}</strong>
                    <span class="badge ${item.ai_confidence < 0.6 ? 'text-danger' : ''}">
                        Conf: ${(item.ai_confidence * 100).toFixed(0)}%
                    </span>
                </div>
                <div class="text-muted small">ID: ${item.id}</div>
                <div class="small text-danger">
                    ${!item.ai_confidence ? 'Não analisado' : (item.ai_confidence < 0.6 ? 'Baixa confiança' : 'Dados incompletos')}
                </div>
            `;
            el.onclick = () => loadDetail(item.id);
            issuesList.appendChild(el);
        });
    };

    const loadDetail = async (id) => {
        detailView.innerHTML = '<div class="loader"></div> Carregando...';
        try {
            const r = await fetch(`/admin/ai/analysis/details?id=${id}`);
            const j = await r.json();
            if (j.ok) {
                const d = j.data;
                detailView.innerHTML = `
                    <div class="card">
                        <h3>Processo: ${d.numeroProcesso}</h3>
                        <table class="table">
                            <tr><th>ID</th><td>${d.id}</td></tr>
                            <tr><th>Classe</th><td>${d.siglaClasse} - ${d.descricaoClasse}</td></tr>
                            <tr><th>Relator</th><td>${d.ministroRelator}</td></tr>
                            <tr><th>Data Decisão</th><td>${d.dataDecisao}</td></tr>
                            <tr><th>Categoria IA</th><td>${d.ai_label || 'N/A'}</td></tr>
                            <tr><th>Confiança IA</th><td>${d.ai_confidence || 'N/A'}</td></tr>
                        </table>
                        <div class="mt-2">
                            <h4>Ementa</h4>
                            <pre style="white-space: pre-wrap; background:#eee; padding:10px;">${d.ementa || 'Não disponível'}</pre>
                        </div>
                        <div class="mt-2">
                            <h4>Decisão</h4>
                            <pre style="white-space: pre-wrap; background:#eee; padding:10px;">${d.decisao || 'Não disponível'}</pre>
                        </div>
                    </div>
                `;
            }
        } catch (e) {
            detailView.innerHTML = '<div class="alert alert-error">Erro ao carregar detalhes.</div>';
        }
    };

    btn.addEventListener('click', async () => {
        btn.disabled = true;
        btn.innerHTML = 'Verificando...';
        
        try {
            const r = await fetch('/admin/ai/analysis/report');
            const j = await r.json();
            
            if (j.ok) {
                document.getElementById('st-total').textContent = j.stats.total_processes;
                document.getElementById('st-ai').textContent = j.stats.ai_analyzed;
                document.getElementById('st-issues').textContent = j.stats.integrity_issues;
                document.getElementById('st-conf').textContent = j.stats.low_confidence;
                
                renderIssues(j.problems);
            } else {
                alert('Erro na análise: ' + (j.error || 'Desconhecido'));
            }
        } catch (e) {
            alert('Erro de conexão');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg> Executar Varredura';
        }
    });
});
</script>
