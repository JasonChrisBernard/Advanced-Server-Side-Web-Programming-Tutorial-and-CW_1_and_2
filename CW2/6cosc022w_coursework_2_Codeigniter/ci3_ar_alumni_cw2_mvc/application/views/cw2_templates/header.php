<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo html_escape($title ?? 'University Analytics Dashboard'); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container">
        <a class="navbar-brand" href="<?php echo site_url('staff/dashboard'); ?>">
            University Analytics
        </a>

        <div>
            <?php if ($this->session->userdata('staff_logged_in')): ?>

                <a class="btn btn-outline-light btn-sm me-2" href="<?php echo site_url('staff/dashboard'); ?>">
                    Dashboard
                </a>

                <a class="btn btn-outline-light btn-sm me-2" href="<?php echo site_url('staff/graphs'); ?>">
                    Graphs
                </a>

                <a class="btn btn-outline-light btn-sm me-2" href="<?php echo site_url('staff/alumni'); ?>">
                    Alumni
                </a>

                <a class="btn btn-outline-light btn-sm me-2" href="<?php echo site_url('staff/reports/charts-export'); ?>">
                    Export CSV
                </a>

                <a class="btn btn-outline-light btn-sm me-2" href="<?php echo site_url('api-docs'); ?>">
                    API Docs
                </a>

                <span class="text-white me-3">
                    <?php echo html_escape($this->session->userdata('staff_full_name')); ?>
                </span>

                <a class="btn btn-outline-light btn-sm" href="<?php echo site_url('staff/logout'); ?>">
                    Logout
                </a>

            <?php else: ?>

                <a class="btn btn-outline-light btn-sm me-2" href="<?php echo site_url('staff/login'); ?>">
                    Login
                </a>

                <a class="btn btn-light btn-sm" href="<?php echo site_url('staff/register'); ?>">
                    Register
                </a>

            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container pb-5">
