<div class="row justify-content-center">
    <div class="col-md-8">

        <div class="card shadow-sm">
            <div class="card-body">
                <h3>Run Winner Selection</h3>

                <p class="text-muted">
                    This simulates the automated midnight winner selection. In production, the same model method can be triggered by a scheduled cron job or Windows Task Scheduler.
                </p>

                <?php echo form_open('bidding/run-selection'); ?>

                    <div class="mb-3">
                        <label class="form-label">Feature Date</label>
                        <input 
                            type="date" 
                            name="feature_date" 
                            class="form-control"
                            value="<?php echo html_escape($featureDate); ?>"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-warning">
                        Run Selection
                    </button>

                <?php echo form_close(); ?>

                <?php if ($result): ?>
                    <hr>

                    <div class="alert alert-info">
                        <?php echo html_escape($result['message']); ?>
                    </div>

                    <?php if (!empty($result['winner'])): ?>
                        <h5>Selected Winner</h5>
                        <p><strong>Name:</strong> <?php echo html_escape($result['winner']['full_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo html_escape($result['winner']['email']); ?></p>
                        <p><strong>Feature Date:</strong> <?php echo html_escape($result['winner']['feature_date']); ?></p>
                    <?php endif; ?>
                <?php endif; ?>

            </div>
        </div>

    </div>
</div>