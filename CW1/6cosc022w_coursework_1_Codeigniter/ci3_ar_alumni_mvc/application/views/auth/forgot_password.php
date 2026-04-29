<div class="row justify-content-center">
    <div class="col-md-6">

        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="mb-3">Forgot Password</h3>

                <p class="text-muted">
                    Enter your registered email address. A reset link will be created.
                </p>

                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success">
                        <?php echo html_escape($this->session->flashdata('success')); ?>
                    </div>

                    <a class="btn btn-outline-primary mb-3" href="<?php echo site_url('devmail'); ?>">
                        Open Dev Email Outbox
                    </a>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo html_escape($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php echo form_open('forgot-password'); ?>

                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input
                            type="email"
                            name="email"
                            class="form-control"
                            value="<?php echo html_escape($old['email'] ?? ''); ?>"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Create Reset Link
                    </button>

                <?php echo form_close(); ?>

                <p class="mt-3 mb-0">
                    <a href="<?php echo site_url('login'); ?>">Back to login</a>
                </p>
            </div>
        </div>

    </div>
</div>