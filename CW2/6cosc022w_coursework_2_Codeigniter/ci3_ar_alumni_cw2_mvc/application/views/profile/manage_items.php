<div class="card shadow-sm">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3><?php echo html_escape($config['title']); ?></h3>

            <a class="btn btn-primary" href="<?php echo site_url('profile/items/' . $type . '/create'); ?>">
                Add <?php echo html_escape($config['single']); ?>
            </a>
        </div>

        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success">
                <?php echo html_escape($this->session->flashdata('success')); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
            <div class="alert alert-info">
                No records added yet.
            </div>
        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <tr>
                        <?php foreach ($config['fields'] as $field => $label): ?>
                            <th><?php echo html_escape($label); ?></th>
                        <?php endforeach; ?>
                        <th>Actions</th>
                    </tr>

                    <?php foreach ($items as $item): ?>
                        <tr>
                            <?php foreach ($config['fields'] as $field => $label): ?>
                                <td>
                                    <?php if ($field === 'official_url' && !empty($item[$field])): ?>

                                        <a href="<?php echo html_escape($item[$field]); ?>" target="_blank">
                                            Open URL
                                        </a>

                                    <?php elseif ($field === 'is_current'): ?>

                                        <?php echo ((int) $item[$field] === 1) ? 'Yes' : 'No'; ?>

                                    <?php elseif ($field === 'description'): ?>

                                        <?php echo nl2br(html_escape($item[$field] ?? '')); ?>

                                    <?php else: ?>

                                        <?php echo html_escape($item[$field] ?? ''); ?>

                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>

                            <td>
                                <a 
                                    class="btn btn-sm btn-outline-primary mb-1"
                                    href="<?php echo site_url('profile/items/' . $type . '/edit/' . $item['id']); ?>"
                                >
                                    Edit
                                </a>

                                <?php echo form_open('profile/items/' . $type . '/delete/' . $item['id'], ['style' => 'display:inline;']); ?>
                                    <button 
                                        type="submit" 
                                        class="btn btn-sm btn-outline-danger mb-1"
                                        onclick="return confirm('Are you sure you want to delete this record?');"
                                    >
                                        Delete
                                    </button>
                                <?php echo form_close(); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

        <?php endif; ?>

        <a class="btn btn-outline-secondary" href="<?php echo site_url('profile'); ?>">
            Back to Profile
        </a>
    </div>
</div>