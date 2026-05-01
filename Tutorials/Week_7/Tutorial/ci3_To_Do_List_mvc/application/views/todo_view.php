<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>CodeIgniter To-Do List</title>

    <!-- Bootstrap CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<div class="container mt-5">

    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">My To-Do List</h3>
                </div>

                <div class="card-body">

                    <p class="text-muted">
                        This app remembers your To-Do actions using a CodeIgniter session.
                    </p>

                    <div class="alert alert-secondary">
                        <strong>Debug Session User ID:</strong>
                        <?php echo html_escape($user_id); ?>
                    </div>

                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success">
                            <?php echo html_escape($success); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo html_escape($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo site_url('todo/add'); ?>" class="mb-4">
                        <div class="mb-3">
                            <label for="action_title" class="form-label">New To-Do Action</label>
                            <input 
                                type="text" 
                                name="action_title" 
                                id="action_title" 
                                class="form-control" 
                                placeholder="Example: Finish CodeIgniter tutorial"
                            >
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Add To-Do
                        </button>
                    </form>

                    <h5>Current To-Do Actions</h5>

                    <?php if (empty($actions)): ?>
                        <div class="alert alert-info">
                            No To-Do actions yet. Add your first one above.
                        </div>
                    <?php else: ?>
                        <ul class="list-group">
                            <?php foreach ($actions as $action): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <?php echo html_escape($action['action_title']); ?>
                                    </span>

                                    <small class="text-muted">
                                        <?php echo html_escape($action['created_at']); ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>

</div>

</body>
</html>