<?php
/**
 * Admin - Logs Viewer
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/model.php';

// Check admin authentication
require_admin();

$tab = $_GET['tab'] ?? 'postbacks';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 50;
$offset = ($page - 1) * $per_page;

$logs = [];
$total_count = 0;

switch ($tab) {
    case 'clicks':
        $logs = get_clicks($per_page, $offset);
        $total_count = count_clicks();
        break;
        
    case 'conversions':
        $logs = get_conversions($per_page, $offset);
        $total_count = count_conversions();
        break;
        
    case 'postbacks':
    default:
        $logs = get_postbacks($per_page, $offset);
        $stmt = db()->prepare("SELECT COUNT(*) FROM postbacks");
        $stmt->execute();
        $total_count = $stmt->fetchColumn();
        break;
}

$total_pages = ceil($total_count / $per_page);

$current_page = 'logs';
$page_title = 'Activity Logs';
$page_description = 'View detailed logs of clicks, conversions, and postback requests';

include '_partials/header.php';
?>

<div style="margin-bottom: 30px;">
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="?tab=postbacks" class="btn <?= $tab === 'postbacks' ? '' : 'btn-secondary' ?>">
            Postbacks
        </a>
        <a href="?tab=conversions" class="btn <?= $tab === 'conversions' ? '' : 'btn-secondary' ?>">
            Conversions
        </a>
        <a href="?tab=clicks" class="btn <?= $tab === 'clicks' ? '' : 'btn-secondary' ?>">
            Clicks
        </a>
        
        <div style="margin-left: auto;">
            <button onclick="window.location.reload()" class="btn btn-secondary">
                🔄 Refresh
            </button>
        </div>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2><?= ucfirst($tab) ?> Logs</h2>
        <div style="color: #a1a1aa;">
            Total: <?= number_format($total_count) ?> records
        </div>
    </div>
    
    <?php if (empty($logs)): ?>
        <div class="empty-state">
            <h3>No <?= $tab ?> found</h3>
            <p>No activity recorded yet. Start testing to see logs appear here.</p>
            <?php if ($tab === 'postbacks'): ?>
                <a href="../postback-test.php" class="btn">Test Postback</a>
            <?php elseif ($tab === 'clicks'): ?>
                <a href="offers.php" class="btn">View Offers</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        
        <?php if ($tab === 'postbacks'): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Offer</th>
                            <th>Transaction ID</th>
                            <th>Status</th>
                            <th>URL</th>
                            <th>Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <div style="font-size: 0.9rem;"><?= date('M j, Y', strtotime($log['created_at'])) ?></div>
                                    <div style="font-size: 0.8rem; color: #a1a1aa;"><?= date('H:i:s', strtotime($log['created_at'])) ?></div>
                                </td>
                                <td>
                                    <?php if ($log['offer_name']): ?>
                                        <strong><?= h($log['offer_name']) ?></strong>
                                    <?php else: ?>
                                        <span style="color: #a1a1aa;">Manual Test</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['transaction_id']): ?>
                                        <code style="font-size: 0.8rem;"><?= h(substr($log['transaction_id'], 0, 20)) ?><?= strlen($log['transaction_id']) > 20 ? '...' : '' ?></code>
                                    <?php else: ?>
                                        <span style="color: #a1a1aa;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['http_code']): ?>
                                        <span class="badge <?= ($log['http_code'] >= 200 && $log['http_code'] < 300) ? 'badge-success' : 'badge-error' ?>">
                                            <?= h($log['http_code']) ?>
                                        </span>
                                    <?php elseif ($log['error']): ?>
                                        <span class="badge badge-error">ERROR</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">PENDING</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-family: monospace; font-size: 0.8rem; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= h($log['url']) ?>
                                    </div>
                                    <button class="copy-btn" data-copy="<?= h($log['url']) ?>">Copy</button>
                                </td>
                                <td>
                                    <?php if ($log['error']): ?>
                                        <div style="color: #f87171; font-size: 0.8rem; max-width: 200px;">
                                            <?= h(substr($log['error'], 0, 100)) ?><?= strlen($log['error']) > 100 ? '...' : '' ?>
                                        </div>
                                    <?php elseif ($log['response']): ?>
                                        <div style="font-family: monospace; font-size: 0.8rem; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            <?= h(substr($log['response'], 0, 100)) ?><?= strlen($log['response']) > 100 ? '...' : '' ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #a1a1aa;">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ($tab === 'conversions'): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Offer</th>
                            <th>Transaction ID</th>
                            <th>Goal</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <div style="font-size: 0.9rem;"><?= date('M j, Y', strtotime($log['created_at'])) ?></div>
                                    <div style="font-size: 0.8rem; color: #a1a1aa;"><?= date('H:i:s', strtotime($log['created_at'])) ?></div>
                                </td>
                                <td>
                                    <strong><?= h($log['offer_name'] ?? 'Unknown Offer') ?></strong>
                                </td>
                                <td>
                                    <code style="font-size: 0.8rem;"><?= h(substr($log['transaction_id'], 0, 20)) ?><?= strlen($log['transaction_id']) > 20 ? '...' : '' ?></code>
                                </td>
                                <td>
                                    <?php if ($log['goal']): ?>
                                        <span style="color: #667eea;"><?= h($log['goal']) ?></span>
                                    <?php else: ?>
                                        <span style="color: #a1a1aa;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['amount']): ?>
                                        <span style="color: #4ade80;">$<?= h($log['amount']) ?></span>
                                    <?php else: ?>
                                        <span style="color: #a1a1aa;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= $log['status'] === 'approved' ? 'badge-success' : 'badge-info' ?>">
                                        <?= h(strtoupper($log['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ($tab === 'clicks'): ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Offer</th>
                            <th>Transaction ID</th>
                            <th>IP Address</th>
                            <th>User Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <div style="font-size: 0.9rem;"><?= date('M j, Y', strtotime($log['created_at'])) ?></div>
                                    <div style="font-size: 0.8rem; color: #a1a1aa;"><?= date('H:i:s', strtotime($log['created_at'])) ?></div>
                                </td>
                                <td>
                                    <strong><?= h($log['offer_name'] ?? 'Unknown Offer') ?></strong>
                                </td>
                                <td>
                                    <code style="font-size: 0.8rem;"><?= h(substr($log['transaction_id'], 0, 20)) ?><?= strlen($log['transaction_id']) > 20 ? '...' : '' ?></code>
                                </td>
                                <td>
                                    <code style="font-size: 0.8rem;"><?= h($log['ip']) ?></code>
                                </td>
                                <td>
                                    <div style="font-size: 0.8rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= h($log['ua'] ?: 'Unknown') ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div style="display: flex; justify-content: center; align-items: center; gap: 10px; margin-top: 30px;">
                <?php if ($page > 1): ?>
                    <a href="?tab=<?= h($tab) ?>&page=<?= $page - 1 ?>" class="btn btn-sm btn-secondary">‹ Previous</a>
                <?php endif; ?>
                
                <div style="display: flex; gap: 5px;">
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    if ($start > 1): ?>
                        <a href="?tab=<?= h($tab) ?>&page=1" class="btn btn-sm btn-secondary">1</a>
                        <?php if ($start > 2): ?>
                            <span style="color: #a1a1aa;">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?tab=<?= h($tab) ?>&page=<?= $i ?>" 
                           class="btn btn-sm <?= $i === $page ? '' : 'btn-secondary' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($end < $total_pages): ?>
                        <?php if ($end < $total_pages - 1): ?>
                            <span style="color: #a1a1aa;">...</span>
                        <?php endif; ?>
                        <a href="?tab=<?= h($tab) ?>&page=<?= $total_pages ?>" class="btn btn-sm btn-secondary"><?= $total_pages ?></a>
                    <?php endif; ?>
                </div>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?tab=<?= h($tab) ?>&page=<?= $page + 1 ?>" class="btn btn-sm btn-secondary">Next ›</a>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 15px; color: #a1a1aa; font-size: 0.9rem;">
                Showing <?= number_format($offset + 1) ?> - <?= number_format(min($offset + $per_page, $total_count)) ?> of <?= number_format($total_count) ?> records
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    // Add click handlers for copy buttons
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const text = this.getAttribute('data-copy');
            copyToClipboard(text);
        });
    });
    
    // Auto-refresh every 30 seconds for real-time monitoring
    // Uncomment the line below to enable auto-refresh
    // enableAutoRefresh(30);
</script>

<?php include '_partials/footer.php'; ?>