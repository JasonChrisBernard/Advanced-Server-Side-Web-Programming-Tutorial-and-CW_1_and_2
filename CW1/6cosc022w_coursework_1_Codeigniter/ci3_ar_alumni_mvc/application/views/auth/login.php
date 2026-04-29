<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">

        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="mb-3">Login</h3>

                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success">
                        <?php echo html_escape($this->session->flashdata('success')); ?>
                    </div>
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

                <?php echo form_open('login'); ?>

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

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input
                            type="password"
                            name="password"
                            class="form-control"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Login
                    </button>

                <?php echo form_close(); ?>

                <div class="mt-3">
                    <a href="<?php echo site_url('forgot-password'); ?>">Forgot password?</a>
                </div>

                <div class="mt-2">
                    No account?
                    <a href="<?php echo site_url('register'); ?>">Register as alumni</a>
                </div>
            </div>
        </div>

    </div>
</div>