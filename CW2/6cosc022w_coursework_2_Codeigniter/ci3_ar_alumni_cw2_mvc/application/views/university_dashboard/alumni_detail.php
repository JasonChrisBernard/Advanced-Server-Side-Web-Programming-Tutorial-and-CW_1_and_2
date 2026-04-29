<div class="mb-3">
    <a class="btn btn-outline-secondary" href="<?php echo site_url('staff/alumni'); ?>">
        Back to Alumni List
    </a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <h2><?php echo html_escape($alumni['full_name']); ?></h2>

        <p class="text-muted">
            <?php echo html_escape($alumni['headline'] ?: 'No headline available'); ?>
        </p>

        <p><strong>Email:</strong> <?php echo html_escape($alumni['email']); ?></p>
        <p><strong>Industry Sector:</strong> <?php echo html_escape($alumni['industry_sector'] ?: '-'); ?></p>
        <p><strong>Profile Completion:</strong> <?php echo (int) $alumni['profile_completion_percent']; ?>%</p>

        <?php if (!empty($alumni['linkedin_url'])): ?>
            <p>
                <strong>LinkedIn:</strong>
                <a href="<?php echo html_escape($alumni['linkedin_url']); ?>" target="_blank">
                    <?php echo html_escape($alumni['linkedin_url']); ?>
                </a>
            </p>
        <?php endif; ?>

        <hr>

        <h5>Biography</h5>
        <p><?php echo nl2br(html_escape($alumni['biography'] ?: 'No biography available.')); ?></p>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5>Degrees</h5>

                <?php foreach ($alumni['degrees'] as $degree): ?>
                    <div class="border-bottom mb-2 pb-2">
                        <strong><?php echo html_escape($degree['degree_name']); ?></strong><br>
                        <?php echo html_escape($degree['university_name']); ?><br>
                        Completion: <?php echo html_escape($degree['completion_date']); ?>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($alumni['degrees'])): ?>
                    <p class="text-muted">No degrees recorded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5>Certifications</h5>

                <?php foreach ($alumni['certifications'] as $cert): ?>
                    <div class="border-bottom mb-2 pb-2">
                        <strong><?php echo html_escape($cert['certification_name']); ?></strong><br>
                        Provider: <?php echo html_escape($cert['provider_name']); ?><br>
                        Completion: <?php echo html_escape($cert['completion_date']); ?>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($alumni['certifications'])): ?>
                    <p class="text-muted">No certifications recorded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5>Professional Courses</h5>

                <?php foreach ($alumni['courses'] as $course): ?>
                    <div class="border-bottom mb-2 pb-2">
                        <strong><?php echo html_escape($course['course_name']); ?></strong><br>
                        Provider: <?php echo html_escape($course['provider_name']); ?><br>
                        Completion: <?php echo html_escape($course['completion_date']); ?>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($alumni['courses'])): ?>
                    <p class="text-muted">No courses recorded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h5>Employment History</h5>

                <?php foreach ($alumni['employment'] as $job): ?>
                    <div class="border-bottom mb-2 pb-2">
                        <strong><?php echo html_escape($job['job_title']); ?></strong><br>
                        Company: <?php echo html_escape($job['company_name']); ?><br>
                        Start: <?php echo html_escape($job['start_date']); ?><br>
                        End: <?php echo $job['is_current'] ? 'Current' : html_escape($job['end_date']); ?>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($alumni['employment'])): ?>
                    <p class="text-muted">No employment history recorded.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>