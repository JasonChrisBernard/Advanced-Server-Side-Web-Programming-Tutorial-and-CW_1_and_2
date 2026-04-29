<div class="row justify-content-center">
    <div class="col-md-6">

        <div class="card shadow-sm">
            <div class="card-body">
                <h3>Upload Profile Image</h3>

                <?php if (!empty($profile['profile_image_path'])): ?>
                    <p>Current image:</p>
                    <img 
                        src="<?php echo base_url($profile['profile_image_path']); ?>" 
                        class="img-fluid rounded mb-3"
                        style="max-height: 220px;"
                    >
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

                <?php echo form_open_multipart('profile/image'); ?>

                    <div class="mb-3">
                        <label class="form-label">Profile Image</label>
                        <input 
                            type="file" 
                            name="profile_image" 
                            class="form-control"
                            accept=".jpg,.jpeg,.png"
                            required
                        >
                        <small class="text-muted">
                            Allowed: JPG, JPEG, PNG. Maximum size: 2MB.
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Upload Image
                    </button>

                    <a class="btn btn-outline-secondary" href="<?php echo site_url('profile'); ?>">
                        Cancel
                    </a>

                <?php echo form_close(); ?>
            </div>
        </div>

    </div>
</div>