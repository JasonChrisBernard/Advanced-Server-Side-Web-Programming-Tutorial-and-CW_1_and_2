<div class="row justify-content-center">
    <div class="col-md-8">

        <div class="card shadow-sm">
            <div class="card-body">

                <h3>Featured Alumni Today</h3>

                <?php if (!$featured): ?>
                    <div class="alert alert-info">
                        No featured alumnus has been selected for today.
                    </div>
                <?php else: ?>

                    <div class="text-center mb-3">
                        <?php if (!empty($featured['profile_image_path'])): ?>
                            <img 
                                src="<?php echo base_url($featured['profile_image_path']); ?>" 
                                class="img-fluid rounded"
                                style="max-height: 250px;"
                            >
                        <?php endif; ?>
                    </div>

                    <h4><?php echo html_escape($featured['full_name']); ?></h4>

                    <p class="text-muted">
                        <?php echo html_escape($featured['headline'] ?: 'No headline added'); ?>
                    </p>

                    <p>
                        <?php echo nl2br(html_escape($featured['biography'] ?: 'No biography added.')); ?>
                    </p>

                    <?php if (!empty($featured['linkedin_url'])): ?>
                        <p>
                            <strong>LinkedIn:</strong>
                            <a href="<?php echo html_escape($featured['linkedin_url']); ?>" target="_blank">
                                <?php echo html_escape($featured['linkedin_url']); ?>
                            </a>
                        </p>
                    <?php endif; ?>

                    <p>
                        <strong>Feature Date:</strong>
                        <?php echo html_escape($featured['feature_date']); ?>
                    </p>

                <?php endif; ?>

            </div>
        </div>

    </div>
</div>