<div class="row">
    <div class="col-md-5">

        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <h3>Blind Bidding System</h3>

                <p class="text-muted">
                    Place a bid for a featured alumni slot. You can see your own bid and whether you are currently winning, but the highest bid amount is hidden.
                </p>

                <?php if ($this->session->flashdata('success')): ?>
                    <div class="alert alert-success">
                        <?php echo html_escape($this->session->flashdata('success')); ?>
                    </div>
                <?php endif; ?>

                <?php if ($this->session->flashdata('errors')): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($this->session->flashdata('errors') as $error): ?>
                                <li><?php echo html_escape($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php echo form_open('bidding', ['method' => 'get']); ?>
                    <div class="mb-3">
                        <label class="form-label">Choose Feature Date</label>
                        <input 
                            type="date" 
                            name="feature_date" 
                            class="form-control"
                            value="<?php echo html_escape($featureDate); ?>"
                        >
                    </div>

                    <button type="submit" class="btn btn-outline-primary">
                        View Date
                    </button>
                <?php echo form_close(); ?>

                <hr>

                <p>
                    <strong>Selected Feature Date:</strong>
                    <?php echo html_escape($featureDate); ?>
                </p>

                <p>
                    <strong>Monthly Wins:</strong>
                    <?php echo (int) $monthlyWinCount; ?> / 3
                </p>

                <div class="alert alert-info">
                    <strong>Status:</strong>
                    <?php echo html_escape($blindStatus); ?>
                </div>

                <?php if ($dateAlreadySelected): ?>
                    <div class="alert alert-warning">
                        Winner selection has already been completed for this date. You cannot place or update bids.
                    </div>
                <?php elseif ($monthlyWinCount >= 3): ?>
                    <div class="alert alert-warning">
                        You have reached the monthly limit of 3 featured wins.
                    </div>
                <?php else: ?>

                    <?php echo form_open('bidding/place'); ?>

                        <input type="hidden" name="feature_date" value="<?php echo html_escape($featureDate); ?>">

                        <div class="mb-3">
                            <label class="form-label">
                                <?php echo $existingBid && $existingBid['status'] === 'active' ? 'Increase Your Bid Amount (£)' : 'Bid Amount (£)'; ?>
                            </label>

                            <input 
                                type="number" 
                                step="0.01" 
                                min="1" 
                                name="bid_amount" 
                                class="form-control"
                                placeholder="<?php echo $existingBid ? 'Must be greater than your current bid' : 'Example: 250'; ?>"
                                required
                            >

                            <?php if ($existingBid): ?>
                                <small class="text-muted">
                                    Your current bid: £<?php echo number_format((float) $existingBid['bid_amount'], 2); ?>.
                                    You can only increase it.
                                </small>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <?php echo $existingBid ? 'Increase Bid' : 'Place Bid'; ?>
                        </button>

                    <?php echo form_close(); ?>

                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Today’s Featured Alumni</h5>

                <?php if ($todayFeatured): ?>
                    <p class="mb-1">
                        <strong><?php echo html_escape($todayFeatured['full_name']); ?></strong>
                    </p>

                    <p class="text-muted">
                        <?php echo html_escape($todayFeatured['headline'] ?: 'No headline added'); ?>
                    </p>

                    <a class="btn btn-sm btn-outline-primary" href="<?php echo site_url('featured-today'); ?>">
                        View Featured Profile
                    </a>
                <?php else: ?>
                    <p class="text-muted mb-0">
                        No featured alumnus selected for today yet.
                    </p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <div class="col-md-7">

        <div class="card shadow-sm">
            <div class="card-body">
                <h4>Your Bidding History</h4>

                <?php if (empty($bids)): ?>
                    <div class="alert alert-info">
                        You have not placed any bids yet.
                    </div>
                <?php else: ?>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <tr>
                                <th>Feature Date</th>
                                <th>Your Bid</th>
                                <th>Status</th>
                                <th>Updated</th>
                                <th>Action</th>
                            </tr>

                            <?php foreach ($bids as $bid): ?>
                                <tr>
                                    <td><?php echo html_escape($bid['feature_date']); ?></td>
                                    <td>£<?php echo number_format((float) $bid['bid_amount'], 2); ?></td>
                                    <td>
                                        <?php if ($bid['status'] === 'won'): ?>
                                            <span class="badge bg-success">Won</span>
                                        <?php elseif ($bid['status'] === 'lost'): ?>
                                            <span class="badge bg-danger">Lost</span>
                                        <?php elseif ($bid['status'] === 'cancelled'): ?>
                                            <span class="badge bg-secondary">Cancelled</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary">Active</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo html_escape($bid['updated_at']); ?></td>
                                    <td>
                                        <?php if ($bid['status'] === 'active'): ?>
                                            <a 
                                                class="btn btn-sm btn-outline-primary mb-1"
                                                href="<?php echo site_url('bidding?feature_date=' . urlencode($bid['feature_date'])); ?>"
                                            >
                                                View / Increase
                                            </a>

                                            <?php echo form_open('bidding/cancel/' . $bid['id'], ['style' => 'display:inline;']); ?>
                                                <button 
                                                    type="submit" 
                                                    class="btn btn-sm btn-outline-danger mb-1"
                                                    onclick="return confirm('Are you sure you want to cancel this bid?');"
                                                >
                                                    Cancel
                                                </button>
                                            <?php echo form_close(); ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>

                <?php endif; ?>
            </div>
        </div>

    </div>
</div>