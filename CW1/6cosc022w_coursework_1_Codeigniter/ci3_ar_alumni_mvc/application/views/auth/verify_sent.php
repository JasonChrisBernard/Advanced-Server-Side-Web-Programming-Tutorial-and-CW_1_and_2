<div class="row justify-content-center">
    <div class="col-md-7">

        <div class="alert alert-success">
            <?php echo html_escape($this->session->flashdata('success') ?: 'Verification email sent.'); ?>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h3>Check Verification Email</h3>

                <p>
                    In this local coursework version, emails are stored in a development email outbox.
                </p>

                <p>
                    Open the development email outbox, then click the verification link.
                </p>

                <a class="btn btn-primary" href="<?php echo site_url('devmail'); ?>">
                    Open Dev Email Outbox
                </a>

                <a class="btn btn-outline-secondary" href="<?php echo site_url('login'); ?>">
                    Go to Login
                </a>
            </div>
        </div>

    </div>
</div>