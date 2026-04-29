<div class="row justify-content-center">
    <div class="col-md-8">

        <div class="card shadow-sm">
            <div class="card-body">
                <h3><?php echo html_escape($title); ?></h3>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo html_escape($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php echo form_open($action); ?>

                    <?php foreach ($config['fields'] as $field => $label): ?>

                        <?php
                            $value = isset($item[$field]) ? $item[$field] : '';
                        ?>

                        <?php if ($field === 'description'): ?>

                            <div class="mb-3">
                                <label class="form-label"><?php echo html_escape($label); ?></label>
                                <textarea 
                                    name="<?php echo html_escape($field); ?>" 
                                    class="form-control" 
                                    rows="4"
                                ><?php echo html_escape($value); ?></textarea>
                            </div>

                        <?php elseif ($field === 'is_current'): ?>

                            <div class="form-check mb-3">
                                <input 
                                    type="checkbox" 
                                    name="<?php echo html_escape($field); ?>" 
                                    value="1"
                                    class="form-check-input"
                                    id="is_current"
                                    <?php echo ((int) $value === 1) ? 'checked' : ''; ?>
                                >

                                <label class="form-check-label" for="is_current">
                                    <?php echo html_escape($label); ?>
                                </label>

                                <div class="form-text">
                                    Tick this if this is your current job. End date can be empty.
                                </div>
                            </div>

                        <?php elseif (strpos($field, 'date') !== false): ?>

                            <div class="mb-3">
                                <label class="form-label"><?php echo html_escape($label); ?></label>
                                <input 
                                    type="date" 
                                    name="<?php echo html_escape($field); ?>" 
                                    class="form-control"
                                    value="<?php echo html_escape($value); ?>"
                                >
                            </div>

                        <?php elseif ($field === 'official_url'): ?>

                            <div class="mb-3">
                                <label class="form-label"><?php echo html_escape($label); ?></label>
                                <input 
                                    type="url" 
                                    name="<?php echo html_escape($field); ?>" 
                                    class="form-control"
                                    value="<?php echo html_escape($value); ?>"
                                    placeholder="https://example.com"
                                    required
                                >
                            </div>

                        <?php else: ?>

                            <div class="mb-3">
                                <label class="form-label"><?php echo html_escape($label); ?></label>
                                <input 
                                    type="text" 
                                    name="<?php echo html_escape($field); ?>" 
                                    class="form-control"
                                    value="<?php echo html_escape($value); ?>"
                                    required
                                >
                            </div>

                        <?php endif; ?>

                    <?php endforeach; ?>

                    <button type="submit" class="btn btn-primary">
                        Save
                    </button>

                    <a class="btn btn-outline-secondary" href="<?php echo site_url('profile/items/' . $type); ?>">
                        Cancel
                    </a>

                <?php echo form_close(); ?>
            </div>
        </div>

    </div>
</div>