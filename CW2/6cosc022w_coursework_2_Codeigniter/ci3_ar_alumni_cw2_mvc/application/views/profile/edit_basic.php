<div class="row justify-content-center">
    <div class="col-md-8">

        <div class="card shadow-sm">
            <div class="card-body">
                <h3>Edit Basic Profile</h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo html_escape($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php echo form_open('profile/edit'); ?>

                    <div class="mb-3">
                        <label class="form-label">Personal Information</label>
                        <textarea 
                            name="personal_info" 
                            class="form-control" 
                            rows="4"
                            required
                        ><?php echo html_escape($profile['personal_info'] ?? ''); ?></textarea>
                        <small class="text-muted">
                            Example: location, interests, professional background, or alumni summary.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Professional Headline</label>
                        <input 
                            type="text" 
                            name="headline" 
                            class="form-control"
                            value="<?php echo html_escape($profile['headline'] ?? ''); ?>"
                            placeholder="Example: Software Engineer at ABC Ltd"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Biography</label>
                        <textarea 
                            name="biography" 
                            class="form-control" 
                            rows="6"
                            required
                        ><?php echo html_escape($profile['biography'] ?? ''); ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">LinkedIn Profile URL</label>
                        <input 
                            type="url" 
                            name="linkedin_url" 
                            class="form-control"
                            value="<?php echo html_escape($profile['linkedin_url'] ?? ''); ?>"
                            placeholder="https://www.linkedin.com/in/your-profile"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Save Profile
                    </button>

                    <a class="btn btn-outline-secondary" href="<?php echo site_url('profile'); ?>">
                        Cancel
                    </a>

                <?php echo form_close(); ?>
            </div>
        </div>

    </div>
</div>