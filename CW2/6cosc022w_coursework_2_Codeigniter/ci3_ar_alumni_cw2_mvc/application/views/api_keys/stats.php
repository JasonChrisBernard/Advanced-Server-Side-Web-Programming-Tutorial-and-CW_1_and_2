<div class="card shadow-sm">
    <div class="card-body">
        <h3>API Usage Statistics</h3>

        <p><strong>Client Name:</strong> <?php echo html_escape($apiKey['key_name']); ?></p>
        <p><strong>Platform:</strong> <?php echo html_escape($apiKey['client_platform']); ?></p>
        <p><strong>Key Prefix:</strong> <code><?php echo html_escape($apiKey['key_prefix']); ?>...</code></p>
        <p><strong>Scopes:</strong> <code><?php echo html_escape($apiKey['scopes']); ?></code></p>
        <p><strong>Total Requests:</strong> <?php echo (int) $apiKey['total_requests']; ?></p>
        <p><strong>Last Request:</strong> <?php echo html_escape($apiKey['last_request_at'] ?: 'No requests yet'); ?></p>

        <hr>

        <h4>Access Logs</h4>

        <?php if (empty($logs)): ?>
            <div class="alert alert-info">
                No usage logs for this key yet.
            </div>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <tr>
                        <th>Timestamp</th>
                        <th>Endpoint</th>
                        <th>Method</th>
                        <th>Required Scope</th>
                        <th>Access Result</th>
                        <th>Status</th>
                        <th>IP</th>
                        <th>User Agent</th>
                    </tr>

                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo html_escape($log['created_at']); ?></td>
                            <td><code><?php echo html_escape($log['endpoint']); ?></code></td>
                            <td><?php echo html_escape($log['http_method']); ?></td>
                            <td><code><?php echo html_escape($log['required_scope'] ?: '-'); ?></code></td>

                            <td>
                                <?php if ($log['access_result'] === 'allowed'): ?>
                                    <span class="badge bg-success">Allowed</span>
                                <?php elseif ($log['access_result'] === 'forbidden_scope'): ?>
                                    <span class="badge bg-warning text-dark">Forbidden Scope</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary"><?php echo html_escape($log['access_result'] ?: '-'); ?></span>
                                <?php endif; ?>
                            </td>

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

                            <td style="max-width: 350px;">
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