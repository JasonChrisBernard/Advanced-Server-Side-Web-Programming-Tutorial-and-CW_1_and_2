<div class="row justify-content-center">
    <div class="col-md-7">

        <div class="card shadow-sm">
            <div class="card-body">
                <h3>Generate API Key</h3>

                <p class="text-muted">
                    Create a separate API key for each external client, such as an AR app, mobile app, or testing client.
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
                            placeholder="Example: AR Headset Client"
                            required
                        >

                        <small class="text-muted">
                            Use a meaningful name so you know which client is using this key.
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Access Scope</label>

                        <select name="scope" class="form-control">
                            <option value="read:alumni">read:alumni - Read featured alumni/profile data</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        Generate Key
                    </button>

                    <a class="btn btn-outline-secondary" href="<?php echo site_url('api-keys'); ?>">
                        Cancel
                    </a>

                <?php echo form_close(); ?>
            </div>
        </div>

    </div>
</div>