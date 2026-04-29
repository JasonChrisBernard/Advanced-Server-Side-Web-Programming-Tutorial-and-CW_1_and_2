<div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
        <h2>Analytics Graphs</h2>
        <p class="text-muted mb-0">
            API-powered visual analysis of alumni programmes, graduation trends, industry sectors, certifications, courses, profile readiness, and employment outcomes.
        </p>
    </div>

    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary" href="<?php echo site_url('staff/reports/charts-export'); ?>">
            Export Chart CSV
        </a>

        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
            Print Report
        </button>
    </div>
</div>

<div id="chartLoading" class="alert alert-info">
    Loading live chart data from the analytics API...
</div>

<div id="chartError" class="alert alert-danger d-none"></div>

<div id="insightCards" class="row mb-3"></div>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Alumni by Programme</h5>
                    <button class="btn btn-sm btn-outline-secondary download-chart" data-chart="programmeChart">PNG</button>
                </div>
                <canvas id="programmeChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Alumni by Industry Sector</h5>
                    <button class="btn btn-sm btn-outline-secondary download-chart" data-chart="industryChart">PNG</button>
                </div>
                <canvas id="industryChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Graduation Year Trend</h5>
                    <button class="btn btn-sm btn-outline-secondary download-chart" data-chart="graduationChart">PNG</button>
                </div>
                <canvas id="graduationChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Top Certifications</h5>
                    <button class="btn btn-sm btn-outline-secondary download-chart" data-chart="certificationChart">PNG</button>
                </div>
                <canvas id="certificationChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Top Short Professional Courses</h5>
                    <button class="btn btn-sm btn-outline-secondary download-chart" data-chart="courseChart">PNG</button>
                </div>
                <canvas id="courseChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Profile Completion Readiness</h5>
                    <button class="btn btn-sm btn-outline-secondary download-chart" data-chart="completionChart">PNG</button>
                </div>
                <canvas id="completionChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Current Employment by Industry</h5>
                    <button class="btn btn-sm btn-outline-secondary download-chart" data-chart="employmentChart">PNG</button>
                </div>
                <canvas id="employmentChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Top Current Employers</h5>
                    <button class="btn btn-sm btn-outline-secondary download-chart" data-chart="employerChart">PNG</button>
                </div>
                <canvas id="employerChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const chartDataUrl = <?php echo json_encode(site_url('staff/analytics/charts')); ?>;
    const palette = ['#2563eb', '#16a34a', '#f97316', '#9333ea', '#dc2626', '#0891b2', '#ca8a04', '#4f46e5', '#be185d', '#0f766e'];
    const charts = {};

    function colours(count, opacity) {
        return Array.from({ length: count }, function (_, index) {
            const hex = palette[index % palette.length];
            if (!opacity) {
                return hex;
            }

            const red = parseInt(hex.slice(1, 3), 16);
            const green = parseInt(hex.slice(3, 5), 16);
            const blue = parseInt(hex.slice(5, 7), 16);
            return 'rgba(' + red + ',' + green + ',' + blue + ',' + opacity + ')';
        });
    }

    function labels(items) {
        return (items || []).map(function (item) {
            return item.label;
        });
    }

    function totals(items) {
        return (items || []).map(function (item) {
            return Number(item.total);
        });
    }

    function baseOptions(title) {
        return {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                title: {
                    display: true,
                    text: title
                }
            }
        };
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[character];
        });
    }

    function makeChart(id, type, title, items, extraOptions) {
        const element = document.getElementById(id);

        if (!element || !items || items.length === 0) {
            return;
        }

        charts[id] = new Chart(element, {
            type: type,
            data: {
                labels: labels(items),
                datasets: [{
                    label: title,
                    data: totals(items),
                    backgroundColor: colours(items.length, type === 'line' ? 0.18 : 0.82),
                    borderColor: colours(items.length),
                    borderWidth: 2,
                    tension: 0.35,
                    fill: type === 'line'
                }]
            },
            options: Object.assign(baseOptions(title), extraOptions || {})
        });
    }

    function renderInsights(insights) {
        const container = document.getElementById('insightCards');
        container.innerHTML = '';

        (insights || []).forEach(function (insight) {
            const column = document.createElement('div');
            column.className = 'col-md-6 col-xl mb-3';
            column.innerHTML =
                '<div class="card border-' + escapeHtml(insight.tone) + ' h-100">' +
                    '<div class="card-body">' +
                        '<div class="text-uppercase small text-' + escapeHtml(insight.tone) + ' fw-bold">' + escapeHtml(insight.label) + '</div>' +
                        '<div class="h5 mt-2">' + escapeHtml(insight.value) + '</div>' +
                        '<p class="text-muted mb-0 small">' + escapeHtml(insight.context) + '</p>' +
                    '</div>' +
                '</div>';
            container.appendChild(column);
        });
    }

    function showError(message) {
        const error = document.getElementById('chartError');
        error.textContent = message;
        error.classList.remove('d-none');
    }

    async function loadCharts() {
        try {
            const response = await fetch(chartDataUrl, {
                headers: {
                    Accept: 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Analytics API returned HTTP ' + response.status);
            }

            const payload = await response.json();
            const chartSets = payload.charts || {};

            renderInsights(payload.insights);
            makeChart('programmeChart', 'bar', 'Alumni Count', chartSets.programmes);
            makeChart('industryChart', 'doughnut', 'Alumni Count', chartSets.industries);
            makeChart('graduationChart', 'line', 'Graduates', chartSets.graduation_years);
            makeChart('certificationChart', 'bar', 'Alumni Count', chartSets.certifications, { indexAxis: 'y' });
            makeChart('courseChart', 'bar', 'Alumni Count', chartSets.courses);
            makeChart('completionChart', 'polarArea', 'Alumni Count', chartSets.profile_completion);
            makeChart('employmentChart', 'radar', 'Current Jobs', chartSets.employment_by_industry);
            makeChart('employerChart', 'bar', 'Current Alumni', chartSets.top_employers, { indexAxis: 'y' });
        } catch (error) {
            showError(error.message || 'Could not load analytics charts.');
        } finally {
            document.getElementById('chartLoading').classList.add('d-none');
        }
    }

    document.querySelectorAll('.download-chart').forEach(function (button) {
        button.addEventListener('click', function () {
            const chart = charts[button.dataset.chart];

            if (!chart) {
                return;
            }

            const link = document.createElement('a');
            link.href = chart.toBase64Image();
            link.download = button.dataset.chart + '.png';
            link.click();
        });
    });

    loadCharts();
</script>
