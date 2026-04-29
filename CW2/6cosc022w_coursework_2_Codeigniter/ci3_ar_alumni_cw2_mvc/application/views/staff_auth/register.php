<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">

        <div class="card shadow-sm">
            <div class="card-body">
                <h3 class="mb-3">University Staff Registration</h3>

                <p class="text-muted">
                    Register using your University of Eastminster staff email address.
                </p>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo html_escape($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php echo form_open('staff/register'); ?>

                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="full_name" class="form-control"
                               value="<?php echo html_escape($old['full_name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">University Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?php echo html_escape($old['email'] ?? ''); ?>"
                               placeholder="name@eastminster.ac.uk" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <input type="text" name="department" class="form-control"
                               value="<?php echo html_escape($old['department'] ?? ''); ?>"
                               placeholder="Computer Science" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Job Title</label>
                        <input type="text" name="job_title" class="form-control"
                               value="<?php echo html_escape($old['job_title'] ?? ''); ?>"
                               placeholder="Graduate Outcomes Analyst" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                        <small class="text-muted">
                            Minimum 8 characters, uppercase, lowercase, number and special character.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        Register
                    </button>

                <?php echo form_close(); ?>

                <p class="mt-3 mb-0">
                    Already have an account?
                    <a href="<?php echo site_url('staff/login'); ?>">Login here</a>
                </p>
            </div>
        </div>

    </div>
</div>