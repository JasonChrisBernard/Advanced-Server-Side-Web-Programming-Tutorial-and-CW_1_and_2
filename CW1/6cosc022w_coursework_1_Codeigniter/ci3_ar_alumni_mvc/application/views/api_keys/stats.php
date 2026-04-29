<div class="card shadow-sm">
    <div class="card-body">
        <h3>API Usage Statistics</h3>

        <p>
            <strong>Client / Key Name:</strong>
            <?php echo html_escape($apiKey['key_name']); ?>
        </p>

        <p>
            <strong>Key Prefix:</strong>
            <code><?php echo html_escape($apiKey['key_prefix']); ?>...</code>
        </p>

        <p>
            <strong>Scopes:</strong>
            <code><?php echo html_escape($apiKey['scopes']); ?></code>
        </p>

        <p>
            <strong>Status:</strong>
            <?php if (!empty($apiKey['revoked_at'])): ?>
                <span class="badge bg-danger">Revoked</span>
            <?php else: ?>
                <span class="badge bg-success">Active</span>
            <?php endif; ?>
        </p>

        <p>
            <strong>Total Requests:</strong>
            <?php echo (int) $apiKey['total_requests']; ?>
        </p>

        <p>
            <strong>Last Request At:</strong>
            <?php echo html_escape($apiKey['last_request_at'] ?: 'No requests yet'); ?>
        </p>

        <hr>

        <h4>Endpoint Access Logs</h4>

        <?php if (empty($logs)): ?>
            <div class="alert alert-info">
                No usage logs for this API key yet.
            </div>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <tr>
                        <th>Timestamp</th>
                        <th>Endpoint</th>
                        <th>Method</th>
                        <th>Status Code</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                    </tr>

                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo html_escape($log['created_at']); ?></td>
                            <td><code><?php echo html_escape($log['endpoint']); ?></code></td>
                            <td><?php echo html_escape($log['http_method']); ?></td>
                            <td>
                                <?php if ((int) $log['status_code'] >= 200 && (int) $log['status_code'] < 300): ?>
                                    <span class="badge bg-success"><?php echo (int) $log['status_code']; ?></span>
                                <?php elseif ((int) $log['status_code'] >= 400): ?>
                                    <span class="badge bg-danger"><?php echo (int) $log['status_code']; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo (int) $log['status_code']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo html_escape($log['ip_address']); ?></td>
                            <td style="max-width: 300px;">
                                <?php echo html_escape($log['user_agent']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

        <?php endif; ?>

        <a class="btn btn-outline-secondary" href="<?php echo site_url('api-keys'); ?>">
            Back to API Keys
        </a>
    </div>
</div>