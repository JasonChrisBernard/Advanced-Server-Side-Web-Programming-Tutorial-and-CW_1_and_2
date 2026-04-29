<div class="row justify-content-center">
    <div class="col-md-7">

        <div class="alert alert-<?php echo html_escape($type ?? 'info'); ?>">
            <h4><?php echo html_escape($heading ?? 'Message'); ?></h4>

            <p class="mb-0">
                <?php echo html_escape($message ?? ''); ?>
            </p>
        </div>

        <a class="btn btn-primary" href="<?php echo site_url('staff/login'); ?>">
            Go to Staff Login
        </a>

    </div>
</div>