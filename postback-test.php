<?php
require_once __DIR__ . '/includes/bootstrap.php';

$response = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $url = trim($_POST['url'] ?? '');
    
    if (!$url) {
        $error = 'URL is required';
    } else {
        // Make the request
        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'S2S-Tracking-Tool/1.0'
            ]
        ]);
        
        $start_time = microtime(true);
        $response_body = @file_get_contents($url, false, $context);
        $end_time = microtime(true);
        
        if ($response_body === false) {
            $error = 'Failed to make request to: ' . $url;
        } else {
            $response = [
                'url' => $url,
                'body' => $response_body,
                'time' => round(($end_time - $start_time) * 1000, 2) . 'ms',
                'headers' => $http_response_header ?? []
            ];
        }
    }
}

// Get offers for the form helper
try {
    $pdo = db();
    $offers_stmt = $pdo->query('SELECT id, name FROM offers ORDER BY id');
    $offers = $offers_stmt->fetchAll();
} catch (Exception $e) {
    $offers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postback Tester - S2S Tracking Tool</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Postback Tester</h1>
        
        <form method="POST" class="postback-form">
            <div class="form-group">
                <label for="url">Postback URL:</label>
                <input type="url" id="url" name="url" 
                       value="<?= h($_POST['url'] ?? base_url() . '/s2s.php?offer=1&tid=test123&amount=1.23&status=approved') ?>" 
                       required>
                <small>Example: <?= h(base_url()) ?>/s2s.php?offer=1&tid=test123&amount=1.23&status=approved</small>
            </div>
            
            <button type="submit">Send Postback</button>
        </form>
        
        <?php if ($offers): ?>
        <div class="url-builder">
            <h2>URL Builder</h2>
            <div class="form-group">
                <label for="offer_select">Offer:</label>
                <select id="offer_select">
                    <option value="">Select an offer...</option>
                    <?php foreach ($offers as $offer): ?>
                        <option value="<?= h($offer['id']) ?>"><?= h($offer['name']) ?> (ID: <?= h($offer['id']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="tid_input">Transaction ID:</label>
                <input type="text" id="tid_input" value="test123" placeholder="test123">
            </div>
            <div class="form-group">
                <label for="amount_input">Amount:</label>
                <input type="number" id="amount_input" step="0.01" value="1.23" placeholder="1.23">
            </div>
            <div class="form-group">
                <label for="status_select">Status:</label>
                <select id="status_select">
                    <option value="approved">Approved</option>
                    <option value="pending">Pending</option>
                    <option value="rejected">Rejected</option>
                </select>
            </div>
            <button type="button" onclick="buildUrl()">Build URL</button>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert error">
                <strong>Error:</strong> <?= h($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($response): ?>
            <div class="response">
                <h2>Response</h2>
                <div class="response-details">
                    <p><strong>URL:</strong> <?= h($response['url']) ?></p>
                    <p><strong>Response Time:</strong> <?= h($response['time']) ?></p>
                    
                    <?php if ($response['headers']): ?>
                        <h3>Headers</h3>
                        <pre><?= h(implode("\n", $response['headers'])) ?></pre>
                    <?php endif; ?>
                    
                    <h3>Response Body</h3>
                    <pre class="response-body"><?= h($response['body']) ?></pre>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="links">
            <a href="offer.php?id=1&tid=test123">Test Offer Page</a> |
            <a href="click.php?offer=1&sub1=test123">Test Click</a>
        </div>
    </div>
    
    <script>
    function buildUrl() {
        const baseUrl = '<?= h(base_url()) ?>';
        const offer = document.getElementById('offer_select').value;
        const tid = document.getElementById('tid_input').value || 'test123';
        const amount = document.getElementById('amount_input').value || '1.23';
        const status = document.getElementById('status_select').value;
        
        if (!offer) {
            alert('Please select an offer');
            return;
        }
        
        const url = `${baseUrl}/s2s.php?offer=${offer}&tid=${encodeURIComponent(tid)}&amount=${amount}&status=${status}`;
        document.getElementById('url').value = url;
    }
    </script>
</body>
</html>