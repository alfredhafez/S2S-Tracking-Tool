<?php
/**
 * Admin - Offers Management
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/model.php';
require_once __DIR__ . '/../includes/macros.php';

// Check admin authentication
require_admin();

$action = $_GET['action'] ?? 'list';
$offer_id = $_GET['id'] ?? null;
$message = null;
$error = null;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        switch ($action) {
            case 'create':
                $name = trim($_POST['name'] ?? '');
                $slug = trim($_POST['slug'] ?? '');
                $partner_url_template = trim($_POST['partner_url_template'] ?? '');
                $notes = trim($_POST['notes'] ?? '');
                
                if (empty($name)) {
                    $error = 'Offer name is required.';
                } else {
                    // Auto-generate slug if not provided
                    if (empty($slug)) {
                        $slug = sanitize_slug($name);
                    } else {
                        $slug = sanitize_slug($slug);
                    }
                    
                    // Check if slug already exists
                    $existing = get_offer_by_slug($slug);
                    if ($existing) {
                        $error = 'An offer with this slug already exists.';
                    } else {
                        $data = [
                            'name' => $name,
                            'slug' => $slug,
                            'partner_url_template' => $partner_url_template,
                            'notes' => $notes
                        ];
                        
                        if (create_offer($data)) {
                            $message = 'Offer created successfully!';
                            $action = 'list'; // Redirect to list
                        } else {
                            $error = 'Failed to create offer.';
                        }
                    }
                }
                break;
                
            case 'edit':
                $name = trim($_POST['name'] ?? '');
                $slug = trim($_POST['slug'] ?? '');
                $partner_url_template = trim($_POST['partner_url_template'] ?? '');
                $notes = trim($_POST['notes'] ?? '');
                
                if (empty($name) || empty($offer_id)) {
                    $error = 'Offer name and ID are required.';
                } else {
                    $slug = sanitize_slug($slug ?: $name);
                    
                    // Check if slug already exists (excluding current offer)
                    $existing = get_offer_by_slug($slug);
                    if ($existing && $existing['id'] != $offer_id) {
                        $error = 'An offer with this slug already exists.';
                    } else {
                        $data = [
                            'name' => $name,
                            'slug' => $slug,
                            'partner_url_template' => $partner_url_template,
                            'notes' => $notes
                        ];
                        
                        if (update_offer($offer_id, $data)) {
                            $message = 'Offer updated successfully!';
                            $action = 'list'; // Redirect to list
                        } else {
                            $error = 'Failed to update offer.';
                        }
                    }
                }
                break;
                
            case 'delete':
                if (empty($offer_id)) {
                    $error = 'Offer ID is required.';
                } else {
                    if (delete_offer($offer_id)) {
                        $message = 'Offer deleted successfully!';
                        $action = 'list'; // Redirect to list
                    } else {
                        $error = 'Failed to delete offer.';
                    }
                }
                break;
        }
    }
}

// Get data for display
$offers = [];
$offer = null;

if ($action === 'list') {
    $offers = get_offers();
} elseif (($action === 'edit' || $action === 'view') && $offer_id) {
    $offer = get_offer($offer_id);
    if (!$offer) {
        $error = 'Offer not found.';
        $action = 'list';
    }
}

$current_page = 'offers';
$page_title = ucfirst($action) . ' Offers';
$page_description = 'Manage your test offers and generate tracking links';

include '_partials/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-success"><?= h($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php endif; ?>

<?php if ($action === 'list'): ?>
    <div style="margin-bottom: 30px;">
        <a href="?action=create" class="btn">+ Create New Offer</a>
    </div>
    
    <div class="card">
        <h2>All Offers</h2>
        
        <?php if (empty($offers)): ?>
            <div class="empty-state">
                <h3>No offers found</h3>
                <p>Create your first offer to start testing postback flows.</p>
                <a href="?action=create" class="btn">Create Offer</a>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Partner URL</th>
                            <th>URLs</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($offers as $offer): ?>
                            <tr>
                                <td>
                                    <strong><?= h($offer['name']) ?></strong>
                                    <?php if ($offer['notes']): ?>
                                        <br><small style="color: #a1a1aa;"><?= h(substr($offer['notes'], 0, 100)) ?><?= strlen($offer['notes']) > 100 ? '...' : '' ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <code><?= h($offer['slug']) ?></code>
                                </td>
                                <td>
                                    <?php if ($offer['partner_url_template']): ?>
                                        <div style="font-family: monospace; font-size: 0.8rem; word-break: break-all;">
                                            <?= h(substr($offer['partner_url_template'], 0, 60)) ?><?= strlen($offer['partner_url_template']) > 60 ? '...' : '' ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: #a1a1aa;">Local only</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $click_url = base_url("click.php?offer={$offer['id']}&sub1={transaction_id}");
                                    $local_url = base_url("offer.php?id={$offer['id']}&tid={transaction_id}");
                                    ?>
                                    <div style="font-size: 0.8rem;">
                                        <div style="margin-bottom: 5px;">
                                            <strong>Click URL:</strong>
                                            <button class="copy-btn" data-copy="<?= h($click_url) ?>">Copy</button>
                                        </div>
                                        <div class="code-block" style="font-size: 0.7rem;">
                                            <?= h($click_url) ?>
                                        </div>
                                        
                                        <div style="margin: 10px 0 5px 0;">
                                            <strong>Local URL:</strong>
                                            <button class="copy-btn" data-copy="<?= h($local_url) ?>">Copy</button>
                                        </div>
                                        <div class="code-block" style="font-size: 0.7rem;">
                                            <?= h($local_url) ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <a href="?action=edit&id=<?= $offer['id'] ?>" class="btn btn-sm">Edit</a>
                                    <a href="?action=view&id=<?= $offer['id'] ?>" class="btn btn-sm btn-secondary">View</a>
                                    <a href="?action=delete&id=<?= $offer['id'] ?>" class="btn btn-sm btn-danger" 
                                       onclick="return confirmDelete('<?= h($offer['name']) ?>')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($action === 'create' || $action === 'edit'): ?>
    <div style="margin-bottom: 30px;">
        <a href="?" class="btn btn-secondary">← Back to Offers</a>
    </div>
    
    <div class="card">
        <h2><?= $action === 'create' ? 'Create New Offer' : 'Edit Offer' ?></h2>
        
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="form-group">
                <label for="name">Offer Name *</label>
                <input type="text" id="name" name="name" placeholder="Adscend Media Sample" 
                       value="<?= h($offer['name'] ?? '') ?>" required>
                <div class="form-help">A descriptive name for your offer</div>
            </div>
            
            <div class="form-group">
                <label for="slug">Slug</label>
                <input type="text" id="slug" name="slug" placeholder="adscend-sample" 
                       value="<?= h($offer['slug'] ?? '') ?>">
                <div class="form-help">URL-friendly identifier (auto-generated if empty)</div>
            </div>
            
            <div class="form-group">
                <label for="partner_url_template">Partner URL Template</label>
                <textarea id="partner_url_template" name="partner_url_template" 
                          placeholder="https://rewardtk.com/click.php?aff=116268&camp=6067997&sub1={transaction_id}"><?= h($offer['partner_url_template'] ?? '') ?></textarea>
                <div class="form-help">
                    The partner's click URL with macros. Leave empty to always use local fallback page.
                    <br>Available macros: {transaction_id}, {tid}, {sub1}, {offer_id}, {offer_name}, {offer_slug}
                </div>
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" placeholder="Sample offer for testing Adscend Media postbacks"><?= h($offer['notes'] ?? '') ?></textarea>
                <div class="form-help">Optional notes about this offer</div>
            </div>
            
            <button type="submit" class="btn"><?= $action === 'create' ? 'Create Offer' : 'Update Offer' ?></button>
            <a href="?" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

<?php elseif ($action === 'view' && $offer): ?>
    <div style="margin-bottom: 30px;">
        <a href="?" class="btn btn-secondary">← Back to Offers</a>
        <a href="?action=edit&id=<?= $offer['id'] ?>" class="btn">Edit Offer</a>
    </div>
    
    <div class="card">
        <h2><?= h($offer['name']) ?></h2>
        
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 10px 20px; margin-bottom: 30px;">
            <strong>ID:</strong> <span><?= h($offer['id']) ?></span>
            <strong>Slug:</strong> <span><?= h($offer['slug']) ?></span>
            <strong>Created:</strong> <span><?= h($offer['created_at']) ?></span>
        </div>
        
        <?php if ($offer['partner_url_template']): ?>
            <h3>Partner URL Template</h3>
            <div class="code-block">
                <?= h($offer['partner_url_template']) ?>
                <button class="copy-btn" data-copy="<?= h($offer['partner_url_template']) ?>">Copy</button>
            </div>
        <?php endif; ?>
        
        <?php if ($offer['notes']): ?>
            <h3>Notes</h3>
            <div style="background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 10px;">
                <?= nl2br(h($offer['notes'])) ?>
            </div>
        <?php endif; ?>
        
        <h3>Generated URLs</h3>
        
        <?php
        $click_url = base_url("click.php?offer={$offer['id']}&sub1={transaction_id}");
        $local_url = base_url("offer.php?id={$offer['id']}&tid={transaction_id}");
        $test_tid = generate_transaction_id();
        $test_click_url = base_url("click.php?offer={$offer['id']}&sub1=$test_tid");
        $test_local_url = base_url("offer.php?id={$offer['id']}&tid=$test_tid");
        ?>
        
        <div style="margin-bottom: 20px;">
            <h4>Click URL (with macro)</h4>
            <div class="code-block">
                <?= h($click_url) ?>
                <button class="copy-btn" data-copy="<?= h($click_url) ?>">Copy</button>
            </div>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h4>Local Test URL (with macro)</h4>
            <div class="code-block">
                <?= h($local_url) ?>
                <button class="copy-btn" data-copy="<?= h($local_url) ?>">Copy</button>
            </div>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h4>Test Click URL (with sample TID)</h4>
            <div class="code-block">
                <?= h($test_click_url) ?>
                <button class="copy-btn" data-copy="<?= h($test_click_url) ?>">Copy</button>
            </div>
            <a href="<?= h($test_click_url) ?>" target="_blank" class="btn btn-sm">Test Click</a>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h4>Test Local URL (with sample TID)</h4>
            <div class="code-block">
                <?= h($test_local_url) ?>
                <button class="copy-btn" data-copy="<?= h($test_local_url) ?>">Copy</button>
            </div>
            <a href="<?= h($test_local_url) ?>" target="_blank" class="btn btn-sm">Test Local Page</a>
        </div>
    </div>

<?php endif; ?>

<script>
    // Add click handlers for copy buttons
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const text = this.getAttribute('data-copy');
            copyToClipboard(text);
        });
    });
</script>

<?php include '_partials/footer.php'; ?>