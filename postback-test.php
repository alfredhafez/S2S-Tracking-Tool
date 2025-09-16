<?php
/**
 * Manual Postback Testing Tool
 * URL: postback-test.php
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/model.php';
require_once __DIR__ . '/includes/macros.php';
require_once __DIR__ . '/includes/postback_client.php';

$test_result = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_postback'])) {
    // Validate CSRF token
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $test_result = ['error' => 'Invalid security token. Please refresh and try again.'];
    } else {
        $url = trim($_POST['url'] ?? '');
        $transaction_id = trim($_POST['transaction_id'] ?? '');
        
        if (empty($url)) {
            $test_result = ['error' => 'Please enter a postback URL.'];
        } elseif (!is_valid_url($url)) {
            $test_result = ['error' => 'Please enter a valid URL.'];
        } elseif (has_unresolved_macros($url)) {
            $unresolved = find_macros($url);
            $test_result = ['error' => 'URL contains unresolved macros: ' . implode(', ', $unresolved)];
        } else {
            // Build parameters
            $params = [];
            
            // Add transaction ID if provided
            if (!empty($transaction_id)) {
                $params['transaction_id'] = $transaction_id;
                $params['tid'] = $transaction_id;
                $params['sub1'] = $transaction_id;
            }
            
            // Add custom parameters
            $custom_params = trim($_POST['custom_params'] ?? '');
            if (!empty($custom_params)) {
                parse_str($custom_params, $parsed_params);
                $params = array_merge($params, $parsed_params);
            }
            
            // Replace macros in URL
            $final_url = replace_macros($url, $params);
            
            // Fire the postback
            $test_result = fire_postback($final_url, [], null, $transaction_id);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Postback Tester - S2S Tool</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1e1e2e 0%, #2d2d3a 100%);
            color: #e1e1e1;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #e1e1e1;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: #e1e1e1;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
            font-family: 'Courier New', monospace;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #a1a1aa;
        }
        
        .form-help {
            font-size: 0.9rem;
            color: #a1a1aa;
            margin-top: 5px;
        }
        
        .btn {
            padding: 14px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: rgba(34, 197, 94, 0.2);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #f87171;
        }
        
        .result-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
        }
        
        .result-card h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.4rem;
        }
        
        .result-url {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            margin-bottom: 15px;
            position: relative;
        }
        
        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            font-size: 12px;
            background: rgba(102, 126, 234, 0.2);
            border: 1px solid rgba(102, 126, 234, 0.3);
            border-radius: 5px;
            color: #667eea;
            cursor: pointer;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .status-item {
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
        }
        
        .status-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .status-label {
            color: #a1a1aa;
            font-size: 0.9rem;
        }
        
        .status-success .status-value {
            color: #4ade80;
        }
        
        .status-error .status-value {
            color: #f87171;
        }
        
        .response-box {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 8px;
            max-height: 300px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        .examples {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .examples h4 {
            color: #667eea;
            margin-bottom: 15px;
        }
        
        .example-item {
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .example-item:hover {
            background: rgba(0, 0, 0, 0.3);
        }
        
        .navigation {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .navigation a {
            color: #667eea;
            text-decoration: none;
            margin: 0 15px;
            padding: 8px 16px;
            border-radius: 8px;
            transition: background 0.3s ease;
        }
        
        .navigation a:hover {
            background: rgba(102, 126, 234, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Postback Tester</h1>
            <p>Manual S2S Postback Testing Tool</p>
        </div>
        
        <div class="navigation">
            <a href="admin/">← Admin Panel</a>
            <a href="admin/settings.php">Settings</a>
            <a href="admin/logs.php">View Logs</a>
        </div>
        
        <div class="card">
            <form method="post" id="postback-form">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                
                <div class="form-group">
                    <label for="url">Postback URL</label>
                    <input type="url" id="url" name="url" placeholder="https://tr.optimawall.com/pbtr?transaction_id={transaction_id}&goal={goal}" 
                           value="<?= h($_POST['url'] ?? '') ?>" required>
                    <div class="form-help">
                        Enter the complete postback URL. Macros like {transaction_id}, {goal}, {amount} will be replaced.
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="transaction_id">Transaction ID</label>
                    <input type="text" id="transaction_id" name="transaction_id" placeholder="Leave empty to auto-generate" 
                           value="<?= h($_POST['transaction_id'] ?? '') ?>">
                    <div class="form-help">
                        Optional. If not provided, a test transaction ID will be generated.
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="custom_params">Additional Parameters</label>
                    <textarea id="custom_params" name="custom_params" placeholder="goal=test_conversion&amount=1.00&status=approved"><?= h($_POST['custom_params'] ?? '') ?></textarea>
                    <div class="form-help">
                        Optional query string format parameters (key=value&key2=value2). These will be available for macro replacement.
                    </div>
                </div>
                
                <button type="submit" name="send_postback" class="btn">Send Postback</button>
                <button type="button" class="btn btn-secondary" onclick="loadFromSettings()">Load from Settings</button>
            </form>
            
            <div class="examples">
                <h4>Example URLs (click to use):</h4>
                <div class="example-item" onclick="setUrl(this.textContent)">
                    https://tr.optimawall.com/pbtr?transaction_id={transaction_id}&goal={goal}&amount={amount}
                </div>
                <div class="example-item" onclick="setUrl(this.textContent)">
                    https://postback-test.com/postback?tid={transaction_id}&offer_id={offer_id}&status={status}
                </div>
                <div class="example-item" onclick="setUrl(this.textContent)">
                    https://httpbin.org/get?transaction_id={transaction_id}&test=true
                </div>
            </div>
        </div>
        
        <?php if ($test_result): ?>
            <div class="result-card">
                <h3>Postback Test Results</h3>
                
                <?php if (isset($test_result['error'])): ?>
                    <div class="alert alert-error">
                        <?= h($test_result['error']) ?>
                    </div>
                <?php else: ?>
                    <div class="status-grid">
                        <div class="status-item <?= $test_result['success'] ? 'status-success' : 'status-error' ?>">
                            <div class="status-value"><?= $test_result['success'] ? 'SUCCESS' : 'FAILED' ?></div>
                            <div class="status-label">Status</div>
                        </div>
                        
                        <div class="status-item">
                            <div class="status-value"><?= h($test_result['http_code'] ?? 'N/A') ?></div>
                            <div class="status-label">HTTP Code</div>
                        </div>
                        
                        <div class="status-item">
                            <div class="status-value"><?= h($test_result['execution_time']) ?>ms</div>
                            <div class="status-label">Response Time</div>
                        </div>
                    </div>
                    
                    <div>
                        <h4 style="color: #667eea; margin-bottom: 10px;">Request URL:</h4>
                        <div class="result-url">
                            <?= h($test_result['url']) ?>
                            <button class="copy-btn" onclick="copyToClipboard('<?= h(addslashes($test_result['url'])) ?>')">Copy</button>
                        </div>
                    </div>
                    
                    <?php if ($test_result['response']): ?>
                        <div>
                            <h4 style="color: #667eea; margin-bottom: 10px;">Response Body:</h4>
                            <div class="response-box"><?= h($test_result['response']) ?></div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($test_result['error']): ?>
                        <div style="margin-top: 15px;">
                            <h4 style="color: #f87171; margin-bottom: 10px;">Error:</h4>
                            <div style="color: #f87171; font-family: 'Courier New', monospace;">
                                <?= h($test_result['error']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function setUrl(url) {
            document.getElementById('url').value = url;
        }
        
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show feedback
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = 'Copied!';
                setTimeout(() => {
                    btn.textContent = originalText;
                }, 2000);
            });
        }
        
        async function loadFromSettings() {
            try {
                const response = await fetch('admin/settings.php?api=get_postback_url');
                const data = await response.json();
                if (data.success && data.url) {
                    document.getElementById('url').value = data.url;
                    if (data.params) {
                        document.getElementById('custom_params').value = data.params;
                    }
                } else {
                    alert('Could not load settings. Please configure your postback URL in the admin panel first.');
                }
            } catch (error) {
                alert('Failed to load settings: ' + error.message);
            }
        }
    </script>
</body>
</html>