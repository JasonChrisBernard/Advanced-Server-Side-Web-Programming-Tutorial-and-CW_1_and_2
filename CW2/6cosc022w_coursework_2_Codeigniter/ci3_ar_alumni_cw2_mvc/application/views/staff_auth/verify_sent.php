<div class="row justify-content-center">
    <div class="col-md-7">

        <div class="alert alert-success">
            <?php echo html_escape($this->session->flashdata('success') ?: 'Verification email sent.'); ?>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h3>Check Staff Verification Email</h3>

                <p>
                    In this local coursework version, staff emails are stored in the development email outbox.
                </p>

                <a class="btn btn-primary" href="<?php echo site_url('staff/devmail'); ?>">
                    Open Staff Dev Email Outbox
                </a>

                <a class="btn btn-outline-secondary" href="<?php echo site_url('staff/login'); ?>">
                    Go to Login
                </a>
            </div>
        </div>

    </div>
</div>