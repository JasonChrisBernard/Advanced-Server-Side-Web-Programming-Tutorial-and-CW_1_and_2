<div class="mb-4">
    <h2>View Alumni</h2>
    <p class="text-muted">
        Filter alumni by programme, graduation year, and industry sector.
    </p>
</div>

<?php
    $exportQuery = http_build_query(array_filter($filters, 'strlen'));
    $exportUrl = site_url('staff/alumni/export') . ($exportQuery ? '?' . $exportQuery : '');
?>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <?php echo form_open('staff/alumni', ['method' => 'get']); ?>

            <div class="row">
                <div class="col-md-4">
                    <label class="form-label">Programme</label>
                    <select name="programme" class="form-control">
                        <option value="">All Programmes</option>
                        <?php foreach ($programmeOptions as $option): ?>
                            <option 
                                value="<?php echo html_escape($option['degree_name']); ?>"
                                <?php echo ($filters['programme'] === $option['degree_name']) ? 'selected' : ''; ?>
                            >
                                <?php echo html_escape($option['degree_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Graduation Year</label>
                    <select name="graduation_year" class="form-control">
                        <option value="">All Years</option>
                        <?php foreach ($graduationYearOptions as $option): ?>
                            <option 
                                value="<?php echo html_escape($option['graduation_year']); ?>"
                                <?php echo ($filters['graduation_year'] === $option['graduation_year']) ? 'selected' : ''; ?>
                            >
                                <?php echo html_escape($option['graduation_year']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Industry Sector</label>
                    <select name="industry_sector" class="form-control">
                        <option value="">All Industries</option>
                        <?php foreach ($industryOptions as $option): ?>
                            <option 
                                value="<?php echo html_escape($option['industry_sector']); ?>"
                                <?php echo ($filters['industry_sector'] === $option['industry_sector']) ? 'selected' : ''; ?>
                            >
                                <?php echo html_escape($option['industry_sector']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">
                    Apply Filters
                </button>

                <a class="btn btn-outline-secondary" href="<?php echo site_url('staff/alumni'); ?>">
                    Clear
                </a>

                <a class="btn btn-outline-success" href="<?php echo html_escape($exportUrl); ?>">
                    Export Filtered CSV
                </a>
            </div>

        <?php echo form_close(); ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <h5>Alumni Results</h5>

        <p class="text-muted">
            Showing <?php echo count($alumni); ?> alumni record(s). Use the CSV export to submit the same filtered report evidence.
        </p>

        <?php if (empty($alumni)): ?>
            <div class="alert alert-info">
                No alumni matched the selected filters.
            </div>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Programme</th>
                        <th>Graduation Date</th>
                        <th>Industry Sector</th>
                        <th>Current Role</th>
                        <th>Completion</th>
                        <th>Action</th>
                    </tr>

                    <?php foreach ($alumni as $item): ?>
                        <tr>
                            <td><?php echo html_escape($item['full_name']); ?></td>
                            <td><?php echo html_escape($item['email']); ?></td>
                            <td><?php echo html_escape($item['degree_name'] ?: '-'); ?></td>
                            <td><?php echo html_escape($item['completion_date'] ?: '-'); ?></td>
                            <td><?php echo html_escape($item['industry_sector'] ?: '-'); ?></td>
                            <td>
                                <?php echo html_escape(($item['job_title'] ?: '-') . ($item['company_name'] ? ' at ' . $item['company_name'] : '')); ?>
                            </td>
                            <td><?php echo (int) ($item['profile_completion_percent'] ?? 0); ?>%</td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('staff/alumni/' . $item['id']); ?>">
                                    View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

        <?php endif; ?>
    </div>
</div>
