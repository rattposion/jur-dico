<?php
use App\Core\View;
?>
<style>
    :root {
        --dash-bg: #f8fafc;
        --dash-card-bg: #ffffff;
        --dash-border: #e2e8f0;
        --dash-text: #1e293b;
        --dash-text-muted: #64748b;
        --dash-primary: #3b82f6;
        --dash-success: #10b981;
        --dash-warning: #f59e0b;
        --dash-danger: #ef4444;
        --dash-info: #6366f1;
    }

    .dashboard-container {
        padding: 2rem;
        background-color: var(--dash-bg);
        min-height: 100vh;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .dash-header {
        margin-bottom: 2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .dash-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--dash-text);
        margin: 0;
    }

    .dash-subtitle {
        color: var(--dash-text-muted);
        font-size: 0.95rem;
    }

    /* Quick Filters (Class Cards) */
    .quick-filters {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .qf-card {
        background: var(--dash-card-bg);
        border: 1px solid var(--dash-border);
        border-radius: 12px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }

    .qf-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border-color: var(--dash-primary);
    }

    .qf-card.active {
        background: var(--dash-primary);
        border-color: var(--dash-primary);
        color: white;
    }

    .qf-card.active .qf-label, .qf-card.active .qf-icon {
        color: white;
    }

    .qf-icon {
        color: var(--dash-text-muted);
        margin-bottom: 0.5rem;
    }

    .qf-label {
        font-weight: 600;
        font-size: 0.9rem;
        color: var(--dash-text);
    }

    /* Filter Bar */
    .filter-bar {
        background: var(--dash-card-bg);
        padding: 1.5rem;
        border-radius: 12px;
        border: 1px solid var(--dash-border);
        margin-bottom: 2rem;
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: flex-end;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        flex: 1;
        min-width: 200px;
    }

    .filter-label {
        font-size: 0.85rem;
        font-weight: 500;
        color: var(--dash-text-muted);
    }

    .filter-input, .filter-select {
        padding: 0.6rem;
        border: 1px solid var(--dash-border);
        border-radius: 6px;
        font-size: 0.95rem;
        color: var(--dash-text);
        background-color: white;
        transition: border-color 0.2s;
    }

    .filter-input:focus, .filter-select:focus {
        outline: none;
        border-color: var(--dash-primary);
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .btn-apply {
        background-color: var(--dash-primary);
        color: white;
        border: none;
        padding: 0.6rem 1.5rem;
        border-radius: 6px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s;
        height: 42px;
    }

    .btn-apply:hover {
        background-color: #2563eb;
    }

    /* KPI Grid */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .kpi-card {
        background: var(--dash-card-bg);
        padding: 1.5rem;
        border-radius: 12px;
        border: 1px solid var(--dash-border);
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        cursor: pointer;
        transition: transform 0.2s;
    }

    .kpi-card:hover {
        transform: translateY(-2px);
    }

    .kpi-title {
        color: var(--dash-text-muted);
        font-size: 0.85rem;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 0.5rem;
    }

    .kpi-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--dash-text);
        margin-bottom: 0.25rem;
    }

    .kpi-trend {
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .trend-up { color: var(--dash-success); }
    .trend-down { color: var(--dash-danger); }
    .trend-neutral { color: var(--dash-text-muted); }

    /* Charts Grid */
    .charts-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
        gap: 1.5rem;
    }

    .chart-card {
        background: var(--dash-card-bg);
        padding: 1.5rem;
        border-radius: 12px;
        border: 1px solid var(--dash-border);
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        min-height: 400px;
        display: flex;
        flex-direction: column;
    }

    .chart-header {
        margin-bottom: 1.5rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .chart-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--dash-text);
        margin: 0;
    }

    .chart-container {
        flex: 1;
        position: relative;
        min-height: 300px;
    }
</style>

<div class="dashboard-container">
    <div class="dash-header">
        <div>
            <h1 class="dash-title">Dashboard Estratégico</h1>
            <p class="dash-subtitle">Visão consolidada dos processos jurídicos</p>
        </div>
        <div>
            <span class="badge" style="background: #e0f2fe; color: #0369a1; padding: 0.5rem 1rem; border-radius: 9999px; font-size: 0.85rem;">
                <span id="lastUpdate">Atualizado agora</span>
            </span>
        </div>
    </div>

    <!-- Quick Filters / Classes -->
    <div class="quick-filters">
        <div class="qf-card active" data-class="">
            <div class="qf-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
            </div>
            <div class="qf-label">Todos os Processos</div>
        </div>
        <div class="qf-card" data-class="ACO">
            <div class="qf-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"></path></svg>
            </div>
            <div class="qf-label">ACOR (Estrutural)</div>
        </div>
        <div class="qf-card" data-class="DTXT">
            <div class="qf-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            </div>
            <div class="qf-label">DTXT (Estrutural)</div>
        </div>
        <div class="qf-card" data-class="ACP">
            <div class="qf-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            </div>
            <div class="qf-label">Ação Civil Pública</div>
        </div>
        <div class="qf-card" data-class="AP">
            <div class="qf-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
            </div>
            <div class="qf-label">Ação Popular</div>
        </div>
        <div class="qf-card" data-class="MSC">
            <div class="qf-icon">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            </div>
            <div class="qf-label">Mandado Seg. Coletivo</div>
        </div>
    </div>

    <!-- Filters Bar -->
    <form id="dashFilters" class="filter-bar" onsubmit="return false">
        <input type="hidden" name="class" id="inputClass" value="">
        
        <div class="filter-group">
            <label class="filter-label">Classificação Técnica</label>
            <select name="classification" class="filter-select">
                <option value="">Todas</option>
                <option value="estrutural">Processo Estrutural</option>
                <option value="nao_estrutural">Processo Não Estrutural</option>
                <option value="nao_estrutural_tecnicas">Não Estrutural (com Técnicas)</option>
            </select>
        </div>

        <div class="filter-group">
            <label class="filter-label">Período</label>
            <div style="display: flex; gap: 0.5rem;">
                <input type="date" name="start" class="filter-input" style="flex:1">
                <input type="date" name="end" class="filter-input" style="flex:1">
            </div>
        </div>

        <div class="filter-group">
            <label class="filter-label">Classificação Técnica (Categoria)</label>
            <select name="category" class="filter-select">
                <option value="">Todas as Classificações</option>
                <?php if(isset($categories) && is_array($categories)): ?>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <button type="button" class="btn-apply" id="applyFilters">Atualizar Dados</button>
    </form>

    <!-- KPI Grid -->
    <div class="kpi-grid">
        <div class="kpi-card" onclick="navigateToRecords('total')">
            <div class="kpi-title">Total de Registros</div>
            <div class="kpi-value" id="kpiTotal">-</div>
            <div class="kpi-trend">
                <span id="kpiTotalChange">0%</span>
                <span class="trend-neutral">vs período anterior</span>
            </div>
        </div>
        <div class="kpi-card" onclick="navigateToRecords('categorized')">
            <div class="kpi-title">Taxa de Categorização</div>
            <div class="kpi-value" id="kpiRate">0%</div>
            <div class="kpi-trend">
                <span id="kpiRateChange">0%</span>
                <span class="trend-neutral">vs período anterior</span>
            </div>
        </div>
        <div class="kpi-card" onclick="navigateToRecords('pending')">
            <div class="kpi-title">Pendentes de Análise</div>
            <div class="kpi-value" id="kpiPending">-</div>
            <div class="kpi-trend trend-neutral">
                Aguardando classificação
            </div>
        </div>
        <div class="kpi-card" onclick="navigateToRecords('decided')">
            <div class="kpi-title">Processos com Decisão</div>
            <div class="kpi-value" id="kpiDecisions">-</div>
            <div class="kpi-trend trend-neutral">
                No período selecionado
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
        <!-- Trend Line -->
        <div class="chart-card" style="grid-column: span 2;">
            <div class="chart-header">
                <h3 class="chart-title">Evolução Temporal (Data da Decisão)</h3>
            </div>
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <!-- Distribution Pie -->
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">Status da Classificação</h3>
            </div>
            <div class="chart-container">
                <canvas id="pieChart"></canvas>
            </div>
        </div>

        <!-- Top Categories Bar -->
        <div class="chart-card">
            <div class="chart-header">
                <h3 class="chart-title">Principais Categorias</h3>
            </div>
            <div class="chart-container">
                <canvas id="barChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart Instances
    let trendChart = null;
    let pieChart = null;
    let barChart = null;

    // Elements
    const qfCards = document.querySelectorAll('.qf-card');
    const inputClass = document.getElementById('inputClass');
    const btnApply = document.getElementById('applyFilters');
    
    // Quick Filter Logic
    qfCards.forEach(card => {
        card.addEventListener('click', () => {
            qfCards.forEach(c => c.classList.remove('active'));
            card.classList.add('active');
            inputClass.value = card.dataset.class;
            fetchAnalytics();
        });
    });

    btnApply.addEventListener('click', fetchAnalytics);

    // Initial Load
    fetchAnalytics();

    function fetchAnalytics() {
        const formData = new FormData(document.getElementById('dashFilters'));
        const params = new URLSearchParams();
        
        for(let [k, v] of formData.entries()) {
            if(v) params.append(k, v);
        }

        // Auto-detect period type
        if (params.get('start') || params.get('end')) {
            params.set('period', 'custom');
        } else if (!params.get('period')) {
            params.set('period', 'month');
        }

        // UI Loading State
        document.body.style.cursor = 'wait';
        
        fetch('/admin/api/analytics?' + params.toString())
            .then(res => res.json())
            .then(data => {
                updateDashboard(data);
                document.getElementById('lastUpdate').innerText = 'Atualizado: ' + new Date().toLocaleTimeString();
            })
            .catch(err => console.error(err))
            .finally(() => {
                document.body.style.cursor = 'default';
            });
    }

    function updateDashboard(data) {
        // Update KPIs
        if (data.kpi) {
            updateKpi('kpiTotal', data.kpi.volume.value, data.kpi.volume.change);
            updateKpi('kpiRate', data.kpi.rate.value + '%', data.kpi.rate.change, true);
            
            // Calc Pending
            const catVal = data.charts.distribution.find(d => d.label === 'Categorizado')?.value || 0;
            const pendVal = data.charts.distribution.find(d => d.label === 'Pendente')?.value || 0;
            document.getElementById('kpiPending').innerText = pendVal.toLocaleString();
            
            document.getElementById('kpiDecisions').innerText = data.kpi.decisions.value.toLocaleString();
        }

        // Update Charts
        if (data.charts) {
            renderTrendChart(data.charts.trend);
            renderPieChart(data.charts.distribution);
            renderBarChart(data.charts.categories);
        }
    }

    function updateKpi(id, value, change, isRate = false) {
        const el = document.getElementById(id);
        const elChange = document.getElementById(id + 'Change');
        
        if(el) el.innerText = isRate ? value : value.toLocaleString();
        
        if(elChange) {
            const val = parseFloat(change);
            elChange.innerText = (val > 0 ? '+' : '') + val + '%';
            elChange.className = '';
            if(val > 0) elChange.classList.add('trend-up');
            else if(val < 0) elChange.classList.add('trend-down');
            else elChange.classList.add('trend-neutral');
        }
    }

    // --- Chart Rendering ---

    function renderTrendChart(data) {
        const ctx = document.getElementById('trendChart').getContext('2d');
        if (trendChart) trendChart.destroy();

        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Registros',
                    data: data.data,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                    x: { grid: { display: false } }
                },
                onClick: (e) => navigateToRecords('trend')
            }
        });
    }

    function renderPieChart(data) {
        const ctx = document.getElementById('pieChart').getContext('2d');
        if (pieChart) pieChart.destroy();

        pieChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(d => d.label),
                datasets: [{
                    data: data.map(d => d.value),
                    backgroundColor: ['#10b981', '#fbbf24'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
                cutout: '70%',
                onClick: (e, elements) => {
                    if(elements.length > 0) {
                        const index = elements[0].index;
                        const label = data[index].label;
                        navigateToRecords(label === 'Pendente' ? 'pending' : 'categorized');
                    }
                }
            }
        });
    }

    function renderBarChart(data) {
        const ctx = document.getElementById('barChart').getContext('2d');
        if (barChart) barChart.destroy();

        barChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.name),
                datasets: [{
                    label: 'Processos',
                    data: data.map(d => d.count),
                    backgroundColor: '#6366f1',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true },
                    y: { grid: { display: false } }
                },
                onClick: (e, elements) => {
                    if(elements.length > 0) {
                        // In a real app we'd need the ID, but here we can search by name or similar
                        // For now, just generic redirect
                        navigateToRecords('category');
                    }
                }
            }
        });
    }

    // Global Drill-down handler
    window.navigateToRecords = function(type) {
        const form = document.getElementById('dashFilters');
        const fd = new FormData(form);
        const params = new URLSearchParams();

        // Base filters
        const cls = fd.get('class');
        const cat = fd.get('category');
        if(cls) params.append('filters[siglaClasse]', cls);
        if(cat) params.append('filters[category_id]', cat);
        
        // Date filters
        /* The records list might not support date ranges directly in the same format, 
           but let's try to pass them or just the type context */

        if (type === 'pending') {
            params.append('filters[category_id]', 'null'); // Special indicator for pending
        }
        
        // For 'decided', we might want to filter by status or decision date, but API might not support it yet.
        // We'll just go to records with the active Class/Category filters.

        window.location.href = '/records?' + params.toString();
    };
});
</script>
