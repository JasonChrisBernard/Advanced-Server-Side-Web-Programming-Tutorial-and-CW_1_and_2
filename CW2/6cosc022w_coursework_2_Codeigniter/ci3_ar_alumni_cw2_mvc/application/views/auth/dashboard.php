<div class="card shadow-sm">
    <div class="card-body">
        <h3>Dashboard</h3>

        <p class="mb-1">
            Welcome,
            <strong><?php echo html_escape($this->session->userdata('full_name')); ?></strong>
        </p>

        <p class="mb-1">
            Email:
            <?php echo html_escape($this->session->userdata('email')); ?>
        </p>

        <p class="mb-3">
            Role:
            <?php echo html_escape($this->session->userdata('role')); ?>
        </p>

        <div class="alert alert-success">
            Login session is working correctly.
        </div>

        <?php if ($this->session->userdata('role') === 'alumnus'): ?>
            <a class="btn btn-primary" href="<?php echo site_url('profile'); ?>">
                Manage Alumni Profile
            </a>

            <a class="btn btn-warning" href="<?php echo site_url('bidding'); ?>">
                Blind Bidding System
            </a>
        <?php endif; ?>
    </div>
</div>