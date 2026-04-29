<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo html_escape($title ?? 'AR Alumni'); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?php echo site_url('dashboard'); ?>">
            AR Alumni
        </a>

        <div>
            <?php if ($this->session->userdata('logged_in')): ?>
                <?php if ($this->session->userdata('role') === 'developer'): ?>
                    <a class="btn btn-outline-info btn-sm me-2" href="<?php echo site_url('api-docs'); ?>">
                        API Docs
                    </a>
                    <a class="btn btn-outline-warning btn-sm me-2" href="<?php echo site_url('api-keys'); ?>">
                        API Keys
                    </a>

                    <a class="btn btn-outline-warning btn-sm me-2" href="<?php echo site_url('bidding/run-selection'); ?>">
                        Run Selection
                    </a>
                <?php endif; ?>

                <?php if ($this->session->userdata('role') === 'alumnus'): ?>
                    <a class="btn btn-outline-light btn-sm me-2" href="<?php echo site_url('bidding'); ?>">
                        Bidding
                    </a>

                    <a class="btn btn-outline-light btn-sm me-2" href="<?php echo site_url('profile'); ?>">
                        Profile
                    </a>
                <?php endif; ?>

                <span class="text-white me-3">
                    <?php echo html_escape($this->session->userdata('full_name')); ?>
                </span>

                <a class="btn btn-outline-light btn-sm" href="<?php echo site_url('logout'); ?>">
                    Logout
                </a>

            <?php else: ?>

                <a class="btn btn-outline-light btn-sm me-2" href="<?php echo site_url('login'); ?>">
                    Login
                </a>

                <a class="btn btn-warning btn-sm" href="<?php echo site_url('register'); ?>">
                    Register
                </a>

            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="container">
