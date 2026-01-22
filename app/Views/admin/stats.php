<?php
use App\Core\View;
?>
<div class="container mt-2">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <div>
            <h1 style="font-size: 1.5rem; margin: 0; color: var(--legal-text);">Dashboard Estatístico</h1>
            <p style="color: var(--legal-muted); margin: 0.5rem 0 0;">Análise detalhada dos registros processuais</p>
        </div>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <div style="background: var(--legal-royal); color: white; padding: 0.5rem 1rem; border-radius: 8px; display: flex; align-items: center; gap: 0.5rem;">
                <div style="display: flex; flex-direction: column;">
                    <span style="font-size: 0.75rem; text-transform: uppercase; opacity: 0.9;">Total Geral</span>
                    <span style="font-size: 1.25rem; font-weight: 600;" id="total-count">...</span>
                </div>
            </div>
            <a href="/admin" class="btn btn-secondary">Voltar</a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card" style="margin-bottom: 2rem;">
        <div class="card-header">
            <h2 style="font-size: 1.1rem; margin: 0;">Filtros Globais</h2>
        </div>
        <div style="padding: 1.5rem;">
            <form id="stats-filter" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                <div class="form-group" style="margin:0">
                    <label style="font-size: 0.9rem; color: var(--legal-muted); display: block; margin-bottom: 0.5rem;">Ano Início</label>
                    <input type="number" name="startYear" placeholder="Ex: 2018" style="width: 100%; padding: 0.5rem; border: 1px solid var(--legal-border); border-radius: 4px;">
                </div>
                <div class="form-group" style="margin:0">
                    <label style="font-size: 0.9rem; color: var(--legal-muted); display: block; margin-bottom: 0.5rem;">Ano Fim</label>
                    <input type="number" name="endYear" placeholder="Ex: 2025" style="width: 100%; padding: 0.5rem; border: 1px solid var(--legal-border); border-radius: 4px;">
                </div>
                <div style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Atualizar Dados</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="tabs-nav" style="border-bottom: 1px solid var(--legal-border); margin-bottom: 1.5rem; display: flex; gap: 1rem; overflow-x: auto;">
        <button class="tab-btn active" data-target="todo">Visão Geral</button>
        <button class="tab-btn" data-target="acor">ACOR</button>
        <button class="tab-btn" data-target="dtxt">DTXT</button>
        <button class="tab-btn" data-target="acp">Ação Civil Pública</button>
        <button class="tab-btn" data-target="ap">Ação Popular</button>
        <button class="tab-btn" data-target="msc">Mandado de Segurança</button>
    </div>

    <!-- Tabs Content -->
    <div id="tabs-content">
        
        <!-- TODO Tab -->
        <div class="tab-pane active" id="content-todo">
            <div class="grid" style="grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div class="card">
                    <div class="card-header"><h3 style="font-size: 1rem; margin: 0;">Evolução Temporal</h3></div>
                    <div style="padding: 1rem; height: 300px;"><canvas id="chartTodoTrend"></canvas></div>
                </div>
                <div class="card">
                    <div class="card-header"><h3 style="font-size: 1rem; margin: 0;">Status</h3></div>
                    <div style="padding: 1rem; height: 300px;"><canvas id="chartTodoStatus"></canvas></div>
                </div>
            </div>
            <div class="card">
                <div class="card-header"><h3 style="font-size: 1rem; margin: 0;">Métricas Gerais</h3></div>
                <div style="padding: 1.5rem; display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; text-align: center;">
                    <div>
                        <div style="font-size: 0.9rem; color: var(--legal-muted); margin-bottom: 0.5rem;">Tempo Médio (Decisão)</div>
                        <div style="font-size: 1.25rem; font-weight: 600;" id="metric-avg-time">-</div>
                    </div>
                    <div>
                        <div style="font-size: 0.9rem; color: var(--legal-muted); margin-bottom: 0.5rem;">Ministros Relatores</div>
                        <div style="font-size: 1.25rem; font-weight: 600;" id="metric-relators-count">-</div>
                    </div>
                    <div>
                        <div style="font-size: 0.9rem; color: var(--legal-muted); margin-bottom: 0.5rem;">Tribunais de Origem</div>
                        <div style="font-size: 1.25rem; font-weight: 600;" id="metric-organs-count">-</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACOR Tab -->
        <div class="tab-pane" id="content-acor" style="display: none;">
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="card">
                    <div class="card-header"><h3 style="font-size: 1rem; margin: 0;">Partes Mais Frequentes (Origem)</h3></div>
                    <div style="padding: 1rem; height: 350px;"><canvas id="chartAcorParties"></canvas></div>
                </div>
                <div class="card">
                    <div class="card-header"><h3 style="font-size: 1rem; margin: 0;">Fases / Tipos de Decisão</h3></div>
                    <div style="padding: 1rem; height: 350px;"><canvas id="chartAcorPhases"></canvas></div>
                </div>
            </div>
        </div>

        <!-- DTXT Tab -->
        <div class="tab-pane" id="content-dtxt" style="display: none;">
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="card">
                    <div class="card-header"><h3 style="font-size: 1rem; margin: 0;">Volume Comparativo (DTXT vs ACOR)</h3></div>
                    <div style="padding: 1rem; height: 350px;"><canvas id="chartDtxtVolume"></canvas></div>
                </div>
                <div class="card">
                    <div class="card-header"><h3 style="font-size: 1rem; margin: 0;">Evolução Anual (DTXT)</h3></div>
                    <div style="padding: 1rem; height: 350px;"><canvas id="chartDtxtTrend"></canvas></div>
                </div>
            </div>
        </div>

        <!-- ACP Tab -->
        <div class="tab-pane" id="content-acp" style="display: none;">
            <div class="card" style="margin-bottom: 1.5rem;">
                <div class="card-header"><h3 style="font-size: 1rem; margin: 0;">Evolução de Ações Civis Públicas</h3></div>
                <div style="padding: 1rem; height: 300px;"><canvas id="chartAcpTrend"></canvas></div>
            </div>
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="card">
                    <div class="card-header"><h3 style="font-size: 1rem; margin: 0;">Órgãos Demandados</h3></div>
                    <div style="padding: 1rem; height: 350px;"><canvas id="chartAcpDefendants"></canvas></div>
                </div>
                <div class="card">
                    <div class="card-header"><h3 style="font-size: 1rem; margin: 0;">Decisões Favoráveis vs Desfavoráveis</h3></div>
                    <div style="padding: 1rem; height: 350px;"><canvas id="chartAcpSuccess"></canvas></div>
                </div>
            </div>
        </div>

        <!-- AP Tab -->
        <div class="tab-pane" id="content-ap" style="display: none;">
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="card">
                    <div class="card-header"><h3 style="font-size: 1rem; margin: 0;">Tempo Médio de Tramitação (Anos)</h3></div>
                    <div style="padding: 1.5rem; text-align: center; display: flex; flex-direction: column; justify-content: center; height: 350px;">
                        <div style="font-size: 3rem; font-weight: 700; color: var(--legal-royal);" id="metric-ap-time">-</div>
                        <p style="color: var(--legal-muted);">Desde autuação até decisão</p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h3 style="font-size: 1rem; margin: 0;">Fundamentações (Top Termos)</h3></div>
                    <div style="padding: 1rem; height: 350px;"><canvas id="chartApThemes"></canvas></div>
                </div>
            </div>
        </div>

        <!-- MSC Tab -->
        <div class="tab-pane" id="content-msc" style="display: none;">
            <div class="grid" style="grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                <div class="card">
                    <div class="card-header"><h3 style="font-size: 1rem; margin: 0;">Legitimados Ativos (Origem)</h3></div>
                    <div style="padding: 1rem; height: 350px;"><canvas id="chartMscLegitimates"></canvas></div>
                </div>
                <div class="card">
                    <div class="card-header"><h3 style="font-size: 1rem; margin: 0;">Taxa de Concessão</h3></div>
                    <div style="padding: 1rem; height: 350px;"><canvas id="chartMscSuccess"></canvas></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.tab-btn {
    background: none;
    border: none;
    padding: 0.75rem 1.25rem;
    font-weight: 500;
    color: var(--legal-muted);
    border-bottom: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
    white-space: nowrap;
}
.tab-btn:hover {
    color: var(--legal-text);
}
.tab-btn.active {
    color: var(--legal-royal);
    border-bottom-color: var(--legal-royal);
    font-weight: 600;
}
.grid {
    display: grid;
    /* grid-template-columns set inline for specific layouts */
}
@media (max-width: 768px) {
    .grid { grid-template-columns: 1fr !important; }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Tabs Logic
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active
            tabBtns.forEach(b => b.classList.remove('active'));
            tabPanes.forEach(p => p.style.display = 'none');
            
            // Add active
            btn.classList.add('active');
            const target = btn.dataset.target;
            document.getElementById('content-' + target).style.display = 'block';
        });
    });

    // Charts instances
    const charts = {};
    
    // Fetch data function
    function fetchStats() {
        const formData = new FormData(document.getElementById('stats-filter'));
        const params = new URLSearchParams(formData);
        
        fetch('/admin/api/stats?' + params.toString())
            .then(res => res.json())
            .then(data => {
                updateDashboard(data);
            })
            .catch(err => console.error(err));
    }
    
    // Update Dashboard Logic
    function updateDashboard(data) {
        document.getElementById('total-count').innerText = data.all.total;
        
        // --- Todo Tab ---
        renderLineChart('chartTodoTrend', 'Evolução Total', data.all.trend.labels, data.all.trend.values);
        renderPieChart('chartTodoStatus', ['Categorizados', 'Pendentes'], [data.all.status.categorized, data.all.status.pending]);
        document.getElementById('metric-avg-time').innerText = data.all.avg_time;
        document.getElementById('metric-relators-count').innerText = data.all.relators_count;
        document.getElementById('metric-organs-count').innerText = data.all.organs_count;

        // --- ACOR Tab ---
        renderBarChart('chartAcorParties', 'Processos por Origem', data.acor.parties.labels, data.acor.parties.values);
        renderPieChart('chartAcorPhases', data.acor.phases.labels, data.acor.phases.values);

        // --- DTXT Tab ---
        renderPieChart('chartDtxtVolume', ['DTXT', 'ACOR'], [data.dtxt.total, data.acor.total]);
        renderLineChart('chartDtxtTrend', 'Evolução DTXT', data.dtxt.trend.labels, data.dtxt.trend.values, '#1cc88a');

        // --- ACP Tab ---
        renderLineChart('chartAcpTrend', 'Evolução ACP', data.acp.trend.labels, data.acp.trend.values, '#36b9cc');
        renderBarChart('chartAcpDefendants', 'Top Órgãos Demandados', data.acp.defendants.labels, data.acp.defendants.values);
        renderPieChart('chartAcpSuccess', data.acp.success.labels, data.acp.success.values);

        // --- AP Tab ---
        document.getElementById('metric-ap-time').innerText = data.ap.avg_time;
        renderBarChart('chartApThemes', 'Termos Frequentes', data.ap.themes.labels, data.ap.themes.values, true); // Horizontal

        // --- MSC Tab ---
        renderBarChart('chartMscLegitimates', 'Top Legitimados', data.msc.legitimates.labels, data.msc.legitimates.values);
        renderPieChart('chartMscSuccess', data.msc.success.labels, data.msc.success.values);
    }

    // Chart Helpers
    function renderLineChart(id, label, labels, data, color = '#4e73df') {
        const ctx = document.getElementById(id).getContext('2d');
        if (charts[id]) charts[id].destroy();
        charts[id] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: data,
                    borderColor: color,
                    tension: 0.3,
                    fill: false
                }]
            },
            options: { maintainAspectRatio: false }
        });
    }

    function renderBarChart(id, label, labels, data, horizontal = false) {
        const ctx = document.getElementById(id).getContext('2d');
        if (charts[id]) charts[id].destroy();
        charts[id] = new Chart(ctx, {
            type: horizontal ? 'bar' : 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: label,
                    data: data,
                    backgroundColor: '#4e73df'
                }]
            },
            options: { 
                indexAxis: horizontal ? 'y' : 'x',
                maintainAspectRatio: false 
            }
        });
    }

    function renderPieChart(id, labels, data) {
        const ctx = document.getElementById(id).getContext('2d');
        if (charts[id]) charts[id].destroy();
        charts[id] = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b']
                }]
            },
            options: { maintainAspectRatio: false }
        });
    }

    // Initial Load
    fetchStats();
    
    // Filter Submit
    document.getElementById('stats-filter').addEventListener('submit', function(e) {
        e.preventDefault();
        fetchStats();
    });
});
</script>
