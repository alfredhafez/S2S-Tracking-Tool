<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/model.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect(base_url('admin/'));
}

// Check admin authentication
require_admin();

// Get dashboard statistics
$stats = get_dashboard_stats();
$recent_clicks = get_clicks(10);
$recent_conversions = get_conversions(10);
$recent_postbacks = get_postbacks(10);
$top_offers = get_top_offers(5);

$current_page = 'dashboard';
$page_title = 'Dashboard';
$page_description = 'Monitor your S2S postback testing activity';

include '_partials/header.php';
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-value"><?= number_format_short($stats['today']['clicks']) ?></div>
        <div class="stat-label">Today's Clicks</div>
        <div class="stat-change positive">CTR: <?= $stats['today']['ctr'] ?>%</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value"><?= number_format_short($stats['today']['conversions']) ?></div>
        <div class="stat-label">Today's Conversions</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value"><?= number_format_short($stats['week']['clicks']) ?></div>
        <div class="stat-label">7-Day Clicks</div>
        <div class="stat-change positive">CTR: <?= $stats['week']['ctr'] ?>%</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value"><?= number_format_short($stats['week']['conversions']) ?></div>
        <div class="stat-label">7-Day Conversions</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value"><?= number_format_short($stats['total']['offers']) ?></div>
        <div class="stat-label">Total Offers</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-value"><?= $stats['total']['ctr'] ?>%</div>
        <div class="stat-label">Overall CTR</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
    <div class="card">
        <h2>Top Performing Offers (30 days)</h2>
        <?php if (empty($top_offers)): ?>
            <div class="empty-state">
                <h3>No data yet</h3>
                <p>Start testing offers to see performance metrics here.</p>
                <a href="offers.php" class="btn">Manage Offers</a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Offer</th>
                            <th>Clicks</th>
                            <th>Conversions</th>
                            <th>CTR</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_offers as $offer): ?>
                            <tr>
                                <td>
                                    <strong><?= h($offer['name']) ?></strong>
                                </td>
                                <td><?= number_format($offer['clicks']) ?></td>
                                <td><?= number_format($offer['conversions']) ?></td>
                                <td>
                                    <span class="badge <?= $offer['ctr'] > 5 ? 'badge-success' : ($offer['ctr'] > 1 ? 'badge-info' : 'badge-error') ?>">
                                        <?= $offer['ctr'] ?>%
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>Quick Actions</h2>
        <div style="display: grid; gap: 15px;">
            <a href="offers.php?action=create" class="btn">Create New Offer</a>
            <a href="settings.php" class="btn btn-secondary">Configure Settings</a>
            <a href="../postback-test.php" class="btn btn-secondary">Test Postback</a>
            <a href="logs.php" class="btn btn-secondary">View Logs</a>
        </div>
        
        <h3 style="margin-top: 30px;">Settings Status</h3>
        <?php
        $settings = get_settings();
        $has_postback_url = !empty($settings['postback_base_url']);
        ?>
        <div style="margin-top: 15px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                <span class="badge <?= $has_postback_url ? 'badge-success' : 'badge-error' ?>">
                    <?= $has_postback_url ? 'CONFIGURED' : 'NOT SET' ?>
                </span>
                <span>Postback URL</span>
            </div>
            
            <?php if (!$has_postback_url): ?>
                <div class="alert alert-error">
                    Please configure your postback URL in Settings to start testing.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <h2>Recent Activity</h2>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 30px;">
        <div>
            <h3>Recent Clicks</h3>
            <?php if (empty($recent_clicks)): ?>
                <div class="empty-state">
                    <p>No clicks recorded yet.</p>
                </div>
            <?php else: ?>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($recent_clicks as $click): ?>
                        <div style="padding: 10px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); font-size: 0.9rem;">
                            <div style="color: #667eea; font-weight: 600;">
                                <?= h($click['offer_name'] ?? 'Unknown Offer') ?>
                            </div>
                            <div style="color: #a1a1aa; font-size: 0.8rem;">
                                TID: <?= h(substr($click['transaction_id'], 0, 20)) ?>...
                            </div>
                            <div style="color: #a1a1aa; font-size: 0.8rem;">
                                <?= time_ago($click['created_at']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div>
            <h3>Recent Conversions</h3>
            <?php if (empty($recent_conversions)): ?>
                <div class="empty-state">
                    <p>No conversions recorded yet.</p>
                </div>
            <?php else: ?>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($recent_conversions as $conversion): ?>
                        <div style="padding: 10px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); font-size: 0.9rem;">
                            <div style="color: #4ade80; font-weight: 600;">
                                <?= h($conversion['offer_name'] ?? 'Unknown Offer') ?>
                            </div>
                            <div style="color: #a1a1aa; font-size: 0.8rem;">
                                TID: <?= h(substr($conversion['transaction_id'], 0, 20)) ?>...
                            </div>
                            <div style="color: #a1a1aa; font-size: 0.8rem;">
                                <?php if ($conversion['amount']): ?>
                                    $<?= h($conversion['amount']) ?> - 
                                <?php endif; ?>
                                <?= time_ago($conversion['created_at']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div>
            <h3>Recent Postbacks</h3>
            <?php if (empty($recent_postbacks)): ?>
                <div class="empty-state">
                    <p>No postbacks fired yet.</p>
                </div>
            <?php else: ?>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($recent_postbacks as $postback): ?>
                        <div style="padding: 10px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); font-size: 0.9rem;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="badge <?= ($postback['http_code'] >= 200 && $postback['http_code'] < 300) ? 'badge-success' : 'badge-error' ?>">
                                    <?= h($postback['http_code'] ?? 'ERR') ?>
                                </span>
                                <span style="color: #667eea; font-weight: 600;">
                                    <?= h($postback['offer_name'] ?? 'Test') ?>
                                </span>
                            </div>
                            <div style="color: #a1a1aa; font-size: 0.8rem;">
                                <?php if ($postback['transaction_id']): ?>
                                    TID: <?= h(substr($postback['transaction_id'], 0, 20)) ?>...
                                <?php endif; ?>
                            </div>
                            <div style="color: #a1a1aa; font-size: 0.8rem;">
                                <?= time_ago($postback['created_at']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '_partials/footer.php'; ?>