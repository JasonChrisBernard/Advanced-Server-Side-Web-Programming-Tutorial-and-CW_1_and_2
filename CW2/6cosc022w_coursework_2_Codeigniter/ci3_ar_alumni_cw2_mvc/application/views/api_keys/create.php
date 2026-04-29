<div class="row justify-content-center">
    <div class="col-md-7">

        <div class="card shadow-sm">
            <div class="card-body">
                <h3>Generate Scoped API Key</h3>

                <p class="text-muted">
                    Each client application receives only the permissions it needs.
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

                <?php echo form_open('api-keys/create'); ?>

                    <div class="mb-3">
                        <label class="form-label">Client / Key Name</label>
                        <input 
                            type="text" 
                            name="key_name" 
                            class="form-control"
                            value="<?php echo html_escape($old['key_name'] ?? ''); ?>"
                            placeholder="Example: University Analytics Dashboard Client"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Client Platform</label>

                        <select name="client_platform" class="form-control" required>
                            <?php foreach ($platformOptions as $value => $label): ?>
                                <option 
                                    value="<?php echo html_escape($value); ?>"
                                    <?php echo (($old['client_platform'] ?? '') === $value) ? 'selected' : ''; ?>
                                >
                                    <?php echo html_escape($label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <small class="text-muted">
                            Analytics Dashboard gets read:alumni and read:analytics. Mobile AR App gets read:alumni_of_day only.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea 
                            name="description" 
                            class="form-control" 
                            rows="3"
                            placeholder="Example: Token used by the university analytics dashboard client."
                        ><?php echo html_escape($old['description'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Generate Scoped Key
                    </button>

                    <a class="btn btn-outline-secondary" href="<?php echo site_url('api-keys'); ?>">
                        Cancel
                    </a>

                <?php echo form_close(); ?>
            </div>
        </div>

    </div>
</div>