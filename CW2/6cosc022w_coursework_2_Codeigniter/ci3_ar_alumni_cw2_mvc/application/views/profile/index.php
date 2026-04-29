<div class="row">
    <div class="col-md-4">

        <div class="card shadow-sm mb-3">
            <div class="card-body text-center">

                <?php if (!empty($profile['profile_image_path'])): ?>
                    <img 
                        src="<?php echo base_url($profile['profile_image_path']); ?>" 
                        alt="Profile Image"
                        class="img-fluid rounded mb-3"
                        style="max-height: 220px;"
                    >
                <?php else: ?>
                    <div class="bg-secondary text-white rounded p-5 mb-3">
                        No Image
                    </div>
                <?php endif; ?>

                <h4><?php echo html_escape($profile['full_name']); ?></h4>

                <p class="text-muted mb-2">
                    <?php echo html_escape($profile['headline'] ?: 'No headline added'); ?>
                </p>

                <a class="btn btn-outline-primary btn-sm" href="<?php echo site_url('profile/image'); ?>">
                    Upload / Change Image
                </a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Profile Completion</h5>

                <div class="progress mb-2">
                    <div 
                        class="progress-bar" 
                        role="progressbar"
                        style="width: <?php echo (int) $profile['profile_completion_percent']; ?>%;"
                    >
                        <?php echo (int) $profile['profile_completion_percent']; ?>%
                    </div>
                </div>

                <p class="text-muted mb-0">
                    Complete all sections to increase profile quality.
                </p>
            </div>
        </div>

    </div>

    <div class="col-md-8">

        <?php if ($this->session->flashdata('success')): ?>
            <div class="alert alert-success">
                <?php echo html_escape($this->session->flashdata('success')); ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h3>Manage Alumni Profile</h3>

                <p><strong>Email:</strong> <?php echo html_escape($profile['email']); ?></p>

                <p><strong>Personal Information:</strong></p>
                <p><?php echo nl2br(html_escape($profile['personal_info'] ?: 'Not added yet.')); ?></p>

                <p><strong>Biography:</strong></p>
                <p><?php echo nl2br(html_escape($profile['biography'] ?: 'Not added yet.')); ?></p>

                <p><strong>LinkedIn:</strong></p>

                <?php if (!empty($profile['linkedin_url'])): ?>
                    <a href="<?php echo html_escape($profile['linkedin_url']); ?>" target="_blank">
                        <?php echo html_escape($profile['linkedin_url']); ?>
                    </a>
                <?php else: ?>
                    <p class="text-muted">Not added yet.</p>
                <?php endif; ?>

                <div class="mt-3">
                    <a class="btn btn-primary" href="<?php echo site_url('profile/edit'); ?>">
                        Edit Basic Profile
                    </a>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h4>Profile Sections</h4>

                <table class="table table-bordered align-middle">
                    <tr>
                        <th>Section</th>
                        <th>Records</th>
                        <th>Action</th>
                    </tr>

                    <tr>
                        <td>Degrees</td>
                        <td><?php echo (int) $counts['degrees']; ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('profile/items/degrees'); ?>">
                                Manage
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td>Professional Certifications</td>
                        <td><?php echo (int) $counts['certifications']; ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('profile/items/certifications'); ?>">
                                Manage
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td>Professional Licences</td>
                        <td><?php echo (int) $counts['licences']; ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('profile/items/licences'); ?>">
                                Manage
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td>Short Professional Courses</td>
                        <td><?php echo (int) $counts['courses']; ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('profile/items/courses'); ?>">
                                Manage
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td>Employment History</td>
                        <td><?php echo (int) $counts['employment']; ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('profile/items/employment'); ?>">
                                Manage
                            </a>
                        </td>
                    </tr>
                </table>

            </div>
        </div>

    </div>
</div>