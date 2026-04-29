<div class="mb-4">
    <h2>University Analytics Dashboard</h2>
    <p class="text-muted">
        Real-time graduate intelligence generated from alumni profiles, certifications, courses, and employment history.
    </p>

    <div class="d-flex gap-2">
        <a class="btn btn-primary" href="<?php echo site_url('staff/graphs'); ?>">
            Open Full Graph Report
        </a>

        <a class="btn btn-outline-primary" href="<?php echo site_url('staff/reports/charts-export'); ?>">
            Export Chart CSV
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-2">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6>Total Alumni</h6>
                <h2 class="text-primary"><?php echo (int) $stats['total_alumni']; ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6>Programmes</h6>
                <h2 class="text-primary"><?php echo (int) $stats['total_programmes']; ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6>Industries</h6>
                <h2 class="text-primary"><?php echo (int) $stats['total_industries']; ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6>Certifications</h6>
                <h2 class="text-primary"><?php echo (int) $stats['total_certifications']; ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6>Courses</h6>
                <h2 class="text-primary"><?php echo (int) $stats['total_courses']; ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-2">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h6>Current Jobs</h6>
                <h2 class="text-primary"><?php echo (int) $stats['total_current_jobs']; ?></h2>
                <span class="badge text-bg-info">
                    <?php echo html_escape($stats['current_employment_rate']); ?>% employed
                </span>
            </div>
        </div>
    </div>
</div>

<div id="dashboardChartLoading" class="alert alert-info">
    Loading dashboard charts from the staff analytics API...
</div>

<div id="dashboardChartError" class="alert alert-danger d-none"></div>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5>Alumni by Programme</h5>
                <canvas id="programmeChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5>Alumni by Industry Sector</h5>
                <canvas id="industryChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h5>Top Professional Certifications</h5>

        <?php if (empty($topCertifications)): ?>
            <p class="text-muted">No certification data available.</p>
        <?php else: ?>
            <table class="table table-bordered">
                <tr>
                    <th>Certification</th>
                    <th>Alumni Count</th>
                </tr>

                <?php foreach ($topCertifications as $cert): ?>
                    <tr>
                        <td><?php echo html_escape($cert['label']); ?></td>
                        <td><?php echo (int) $cert['total']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h5>Latest Alumni Records</h5>

        <table class="table table-bordered align-middle">
            <tr>
                <th>Name</th>
                <th>Programme</th>
                <th>Graduation Date</th>
                <th>Industry</th>
                <th>Current Role</th>
                <th>Action</th>
            </tr>

            <?php foreach ($latestAlumni as $item): ?>
                <tr>
                    <td><?php echo html_escape($item['full_name']); ?></td>
                    <td><?php echo html_escape($item['degree_name'] ?: '-'); ?></td>
                    <td><?php echo html_escape($item['completion_date'] ?: '-'); ?></td>
                    <td><?php echo html_escape($item['industry_sector'] ?: '-'); ?></td>
                    <td>
                        <?php echo html_escape(($item['job_title'] ?: '-') . ($item['company_name'] ? ' at ' . $item['company_name'] : '')); ?>
                    </td>
                    <td>
                        <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('staff/alumni/' . $item['id']); ?>">
                            View
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <a class="btn btn-primary" href="<?php echo site_url('staff/alumni'); ?>">
            View All Alumni
        </a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const dashboardChartUrl = <?php echo json_encode(site_url('staff/analytics/charts')); ?>;
    const dashboardColours = ['#2563eb', '#16a34a', '#f97316', '#9333ea', '#dc2626', '#0891b2', '#ca8a04', '#4f46e5'];

    function dashboardLabels(items) {
        return (items || []).map(function (item) {
            return item.label;
        });
    }

    function dashboardTotals(items) {
        return (items || []).map(function (item) {
            return Number(item.total);
        });
    }

    function makeDashboardChart(id, type, label, items) {
        if (!items || items.length === 0) {
            return;
        }

        new Chart(document.getElementById(id), {
            type: type,
            data: {
                labels: dashboardLabels(items),
                datasets: [{
                    label: label,
                    data: dashboardTotals(items),
                    backgroundColor: dashboardColours,
                    borderColor: dashboardColours,
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    async function loadDashboardCharts() {
        try {
            const response = await fetch(dashboardChartUrl, {
                headers: {
                    Accept: 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error('Dashboard analytics API returned HTTP ' + response.status);
            }

            const payload = await response.json();
            const charts = payload.charts || {};

            makeDashboardChart('programmeChart', 'bar', 'Alumni Count', charts.programmes);
            makeDashboardChart('industryChart', 'doughnut', 'Alumni Count', charts.industries);
        } catch (error) {
            const alert = document.getElementById('dashboardChartError');
            alert.textContent = error.message || 'Could not load dashboard charts.';
            alert.classList.remove('d-none');
        } finally {
            document.getElementById('dashboardChartLoading').classList.add('d-none');
        }
    }

    loadDashboardCharts();
</script>
