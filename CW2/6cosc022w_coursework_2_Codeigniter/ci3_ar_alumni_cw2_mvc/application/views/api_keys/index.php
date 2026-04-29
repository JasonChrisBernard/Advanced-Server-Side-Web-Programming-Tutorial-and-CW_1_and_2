<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3>Scoped API Key Management</h3>
                <p class="text-muted mb-0">
                    Generate separate keys for the analytics dashboard and mobile AR app.
                </p>
            </div>

            <a class="btn btn-primary" href="<?php echo site_url('api-keys/create'); ?>">
                Generate API Key
            </a>
        </div>

        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success">
                <?php echo html_escape($this->session->flashdata('success')); ?>
            </div>
        <?php endif; ?>

        <?php if ($this->session->flashdata('plain_api_key')): ?>
            <div class="alert alert-warning">
                <h5>Copy this API key now</h5>

                <p>This plain API key is shown only once.</p>

                <pre class="bg-light border p-3"><?php echo html_escape($this->session->flashdata('plain_api_key')); ?></pre>

                <p>
                    <strong>Scopes:</strong>
                    <code><?php echo html_escape($this->session->flashdata('generated_scopes')); ?></code>
                </p>

                <p class="mb-0">
                    Use it as:
                    <code>Authorization: Bearer YOUR_API_KEY</code>
                </p>
            </div>
        <?php endif; ?>

        <?php if (empty($apiKeys)): ?>
            <div class="alert alert-info">
                No API keys created yet.
            </div>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <tr>
                        <th>Client Name</th>
                        <th>Platform</th>
                        <th>Prefix</th>
                        <th>Scopes</th>
                        <th>Status</th>
                        <th>Total Requests</th>
                        <th>Last Used</th>
                        <th>Actions</th>
                    </tr>

                    <?php foreach ($apiKeys as $key): ?>
                        <tr>
                            <td><?php echo html_escape($key['key_name']); ?></td>

                            <td>
                                <?php if ($key['client_platform'] === 'analytics_dashboard'): ?>
                                    <span class="badge bg-primary">Analytics Dashboard</span>
                                <?php elseif ($key['client_platform'] === 'mobile_ar_app'): ?>
                                    <span class="badge bg-info text-dark">Mobile AR App</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo html_escape($key['client_platform']); ?></span>
                                <?php endif; ?>
                            </td>

                            <td><code><?php echo html_escape($key['key_prefix']); ?>...</code></td>

                            <td><code><?php echo html_escape($key['scopes']); ?></code></td>

                            <td>
                                <?php if (!empty($key['revoked_at'])): ?>
                                    <span class="badge bg-danger">Revoked</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Active</span>
                                <?php endif; ?>
                            </td>

                            <td><?php echo (int) $key['total_requests']; ?></td>

                            <td><?php echo html_escape($key['last_used_at'] ?: 'Never'); ?></td>

                            <td>
                                <a class="btn btn-sm btn-outline-primary mb-1"
                                   href="<?php echo site_url('api-keys/stats/' . $key['id']); ?>">
                                    Stats
                                </a>

                                <?php if (empty($key['revoked_at'])): ?>
                                    <?php echo form_open('api-keys/revoke/' . $key['id'], ['style' => 'display:inline;']); ?>
                                        <button 
                                            type="submit" 
                                            class="btn btn-sm btn-outline-danger mb-1"
                                            onclick="return confirm('Revoke this API key?');"
                                        >
                                            Revoke
                                        </button>
                                    <?php echo form_close(); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

        <?php endif; ?>
    </div>
</div>