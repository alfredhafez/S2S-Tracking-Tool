<?php
require_once __DIR__ . '/includes/postback.php';

$offer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$transaction_id = ensure_tid($_GET['tid'] ?? null);

if ($offer_id <= 0) {
    http_response_code(400);
    echo '<link rel="stylesheet" href="css/style.css">';
    echo '<div class="container error">Missing or invalid offer ID.</div>';
    exit;
}

$offer = get_offer($offer_id);
if (!$offer) {
    http_response_code(404);
    echo '<link rel="stylesheet" href="css/style.css">';
    echo '<div class="container error">Offer not found.</div>';
    exit;
}

// Handle conversion simulation on POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payout = isset($_POST['payout']) ? (float)$_POST['payout'] : 1.0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : 'approved';
    
    if (empty($status)) $status = 'approved';
    
    record_conversion($transaction_id, $offer_id, $payout, $status);
    
    // Log this as a postback
    log_postback([
        'offer_id' => $offer_id,
        'transaction_id' => $transaction_id,
        'status' => $status,
        'payout' => $payout
    ]);
    
    $success_msg = "Conversion recorded: TID={$transaction_id}, Status={$status}, Payout={$payout}";
}

$click = find_click($offer_id, $transaction_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($offer['name']) ?> - S2S Tracking Tool</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1><?= h($offer['name']) ?></h1>
        
        <?php if (isset($success_msg)): ?>
            <div class="alert success"><?= h($success_msg) ?></div>
        <?php endif; ?>
        
        <div class="offer-details">
            <p><strong>Offer ID:</strong> <?= h($offer_id) ?></p>
            <p><strong>Transaction ID:</strong> <?= h($transaction_id) ?></p>
            
            <?php if ($click): ?>
                <p><strong>Click Status:</strong> 
                    <?= $click['converted'] ? 'Converted' : 'Clicked' ?>
                    <?php if ($click['converted']): ?>
                        (<?= h($click['status']) ?>, $<?= h($click['payout']) ?>)
                    <?php endif; ?>
                </p>
                <p><strong>Click Time:</strong> <?= h($click['created_at']) ?></p>
            <?php else: ?>
                <p><strong>Click Status:</strong> <span class="error">No click recorded</span></p>
            <?php endif; ?>
        </div>
        
        <div class="conversion-form">
            <h2>Simulate Conversion</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="payout">Payout Amount:</label>
                    <input type="number" id="payout" name="payout" step="0.01" value="1.00" required>
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <option value="approved">Approved</option>
                        <option value="pending">Pending</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <button type="submit">Simulate Conversion</button>
            </form>
        </div>
        
        <div class="links">
            <a href="postback-test.php">Postback Tester</a> |
            <a href="click.php?offer=<?= $offer_id ?>&sub1=<?= urlencode($transaction_id) ?>">Test Click Again</a>
        </div>
    </div>
</body>
</html>