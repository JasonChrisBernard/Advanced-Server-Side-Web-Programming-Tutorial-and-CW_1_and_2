<div class="card shadow-sm">
    <div class="card-body">
        <h3>Development Email Outbox</h3>

        <p class="text-muted">
            This page is for local testing only. In a real system, these messages would be sent using SMTP email.
        </p>

        <?php if (empty($emails)): ?>
            <div class="alert alert-info">
                No emails found.
            </div>
        <?php else: ?>

            <?php foreach ($emails as $email): ?>
                <div class="border rounded p-3 mb-3 bg-white">
                    <p class="mb-1">
                        <strong>To:</strong>
                        <?php echo html_escape($email['to_email']); ?>
                    </p>

                    <p class="mb-1">
                        <strong>Subject:</strong>
                        <?php echo html_escape($email['subject']); ?>
                    </p>

                    <p class="mb-1">
                        <strong>Created:</strong>
                        <?php echo html_escape($email['created_at']); ?>
                    </p>

                    <pre class="bg-light p-3 border rounded"><?php echo html_escape($email['body']); ?></pre>

                    <?php
                        preg_match('/https?:\/\/[^\s]+/', $email['body'], $matches);
                        $link = $matches[0] ?? '';
                    ?>

                    <?php if ($link): ?>
                        <a class="btn btn-sm btn-primary" href="<?php echo html_escape($link); ?>">
                            Open Link
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>
</div>