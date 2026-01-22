<?php
$title = 'Dashboard Analítico';
ob_start();
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Dashboard Analítico</h1>
            <p class="text-muted small mb-0">Visão geral e métricas de desempenho em tempo real.</p>
        </div>
        <div>
            <button class="btn btn-sm btn-outline-primary me-2" onclick="refreshDashboard()">
                <i class="bi bi-arrow-clockwise"></i> Atualizar
            </button>
            <button class="btn btn-sm btn-outline-secondary" onclick="exportReport()">
                <i class="bi bi-download"></i> Exportar
            </button>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow mb-4">
        <div class="card-body py-3">
            <form id="filterForm" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Período</label>
                    <select class="form-select form-select-sm" id="periodSelect" onchange="toggleCustomDates()">
                        <option value="today">Hoje</option>
                        <option value="week">Esta Semana</option>
                        <option value="month" selected>Este Mês</option>
                        <option value="custom">Personalizado</option>
                    </select>
                </div>
                <div class="col-md-3 custom-date d-none">
                    <label class="form-label small fw-bold">Início</label>
                    <input type="date" class="form-control form-control-sm" id="startDate">
                </div>
                <div class="col-md-3 custom-date d-none">
                    <label class="form-label small fw-bold">Fim</label>
                    <input type="date" class="form-control form-control-sm" id="endDate">
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-primary btn-sm w-100" onclick="loadDashboardData()">
                        <i class="bi bi-filter"></i> Aplicar Filtros
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPIs -->
    <div class="row mb-4">
        <!-- Volume -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total de Registros</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpiVolume">...</div>
                            <small class="text-muted" id="kpiVolumeChange">Carregando...</small>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-file-earmark-text fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categorization Rate -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Taxa de Categorização</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpiRate">...</div>
                            <small class="text-muted" id="kpiRateChange">Carregando...</small>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-tags fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Diversity -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Categorias Ativas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpiDiversity">...</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-diagram-3 fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Decisions -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Decisões no Período</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="kpiDecisions">...</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-gavel fs-2 text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row mb-4">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Tendência de Importação</h6>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Status dos Registros</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4 pb-2">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row mb-4">
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top 5 Categorias</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6 mb-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Registros Recentes</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-bordered table-sm" id="recentTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Processo</th>
                                    <th>Classe</th>
                                    <th>Data</th>
                                    <th>Categoria</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data populated by JS -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let trendChart = null;
    let statusChart = null;
    let categoryChart = null;

    document.addEventListener('DOMContentLoaded', function() {
        loadDashboardData();
    });

    function toggleCustomDates() {
        const period = document.getElementById('periodSelect').value;
        const customInputs = document.querySelectorAll('.custom-date');
        customInputs.forEach(el => {
            el.classList.toggle('d-none', period !== 'custom');
        });
    }

    async function loadDashboardData() {
        const period = document.getElementById('periodSelect').value;
        let url = `/admin/api/analytics?period=${period}`;
        
        if (period === 'custom') {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            if (start) url += `&start=${start}`;
            if (end) url += `&end=${end}`;
        }

        try {
            const response = await fetch(url);
            const data = await response.json();
            updateDashboard(data);
        } catch (error) {
            console.error('Error loading analytics:', error);
            alert('Erro ao carregar dados do dashboard.');
        }
    }

    function updateDashboard(data) {
        // KPIs
        document.getElementById('kpiVolume').textContent = data.kpi.volume.value.toLocaleString();
        document.getElementById('kpiVolumeChange').textContent = `${data.kpi.volume.change > 0 ? '+' : ''}${data.kpi.volume.change}% vs anterior`;
        document.getElementById('kpiVolumeChange').className = data.kpi.volume.change >= 0 ? 'text-success small' : 'text-danger small';

        document.getElementById('kpiRate').textContent = `${data.kpi.rate.value}%`;
        document.getElementById('kpiRateChange').textContent = `${data.kpi.rate.change > 0 ? '+' : ''}${data.kpi.rate.change} pts`;
        document.getElementById('kpiRateChange').className = data.kpi.rate.change >= 0 ? 'text-success small' : 'text-danger small';

        document.getElementById('kpiDiversity').textContent = data.kpi.diversity.value;
        document.getElementById('kpiDecisions').textContent = data.kpi.decisions.value.toLocaleString();

        // Charts
        updateTrendChart(data.charts.trend);
        updateStatusChart(data.charts.distribution);
        updateCategoryChart(data.charts.categories);

        // Table
        updateRecentTable(data.table);
    }

    function updateTrendChart(data) {
        const ctx = document.getElementById('trendChart').getContext('2d');
        if (trendChart) trendChart.destroy();

        trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Registros Importados',
                    data: data.data,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2] } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    function updateStatusChart(data) {
        const ctx = document.getElementById('statusChart').getContext('2d');
        if (statusChart) statusChart.destroy();

        statusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.map(d => d.label),
                datasets: [{
                    data: data.map(d => d.value),
                    backgroundColor: ['#1cc88a', '#f6c23e'],
                    hoverBackgroundColor: ['#17a673', '#dda20a'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '70%',
            }
        });
    }

    function updateCategoryChart(data) {
        const ctx = document.getElementById('categoryChart').getContext('2d');
        if (categoryChart) categoryChart.destroy();

        categoryChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.name),
                datasets: [{
                    label: 'Registros',
                    data: data.map(d => d.count),
                    backgroundColor: '#4e73df',
                    borderRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true }
                }
            }
        });
    }

    function updateRecentTable(data) {
        const tbody = document.querySelector('#recentTable tbody');
        tbody.innerHTML = '';
        data.forEach(row => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${row.numeroProcesso}</td>
                <td>${row.siglaClasse}</td>
                <td>${row.dataDecisao || '-'}</td>
                <td><span class="badge bg-secondary">${row.category || 'N/A'}</span></td>
            `;
            tbody.appendChild(tr);
        });
    }

    function refreshDashboard() {
        loadDashboardData();
    }
    
    function exportReport() {
        alert('Funcionalidade de exportação em desenvolvimento.');
    }
</script>

<style>
    .chart-area { position: relative; height: 320px; width: 100%; }
    .chart-pie { position: relative; height: 320px; width: 100%; }
    .chart-bar { position: relative; height: 320px; width: 100%; }
    .border-left-primary { border-left: 0.25rem solid #4e73df !important; }
    .border-left-success { border-left: 0.25rem solid #1cc88a !important; }
    .border-left-info { border-left: 0.25rem solid #36b9cc !important; }
    .border-left-warning { border-left: 0.25rem solid #f6c23e !important; }
</style>