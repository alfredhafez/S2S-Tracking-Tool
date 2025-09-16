<?php
/**
 * Admin - Settings Management
 */

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/model.php';
require_once __DIR__ . '/../includes/postback_client.php';

// Check admin authentication
require_admin();

$message = null;
$error = null;
$test_result = null;

// Handle API requests
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['api']) {
        case 'get_postback_url':
            $settings = get_settings();
            $response = [
                'success' => !empty($settings['postback_base_url']),
                'url' => $settings['postback_base_url'] ?? '',
                'params' => ''
            ];
            
            // Build sample parameters
            if ($response['success']) {
                $params = [];
                if (!empty($settings['default_goal'])) {
                    $params['goal'] = $settings['default_goal'];
                }
                if (!empty($settings['default_amount'])) {
                    $params['amount'] = $settings['default_amount'];
                }
                if (!empty($settings['extra_params'])) {
                    $extra = json_decode($settings['extra_params'], true) ?: [];
                    $params = array_merge($params, $extra);
                }
                if (!empty($params)) {
                    $response['params'] = http_build_query($params);
                }
            }
            
            echo json_encode($response);
            exit;
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'API endpoint not found']);
            exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_settings':
                $postback_base_url = trim($_POST['postback_base_url'] ?? '');
                $default_goal = trim($_POST['default_goal'] ?? '');
                $default_amount = trim($_POST['default_amount'] ?? '');
                $extra_params = trim($_POST['extra_params'] ?? '');
                
                // Validate postback URL
                if (empty($postback_base_url)) {
                    $error = 'Postback base URL is required.';
                } elseif (!is_valid_url($postback_base_url)) {
                    $error = 'Please enter a valid postback URL.';
                } else {
                    // Validate extra parameters
                    $extra_params_array = [];
                    if (!empty($extra_params)) {
                        // Try to decode as JSON first
                        $decoded = json_decode($extra_params, true);
                        if ($decoded !== null) {
                            $extra_params_array = $decoded;
                        } else {
                            // Try to parse as query string
                            parse_str($extra_params, $parsed);
                            if (!empty($parsed)) {
                                $extra_params_array = $parsed;
                                $extra_params = json_encode($parsed); // Store as JSON
                            } else {
                                $error = 'Extra parameters must be in valid JSON or query string format.';
                            }
                        }
                    }
                    
                    if (!$error) {
                        $data = [
                            'postback_base_url' => $postback_base_url,
                            'default_goal' => $default_goal ?: null,
                            'default_amount' => $default_amount ?: null,
                            'extra_params' => $extra_params ?: null
                        ];
                        
                        if (update_settings($data)) {
                            $message = 'Settings updated successfully!';
                        } else {
                            $error = 'Failed to update settings.';
                        }
                    }
                }
                break;
                
            case 'test_postback':
                try {
                    $test_result = test_postback();
                    if ($test_result['success']) {
                        $message = 'Test postback sent successfully!';
                    } else {
                        $error = 'Test postback failed: ' . ($test_result['error'] ?? 'Unknown error');
                    }
                } catch (Exception $e) {
                    $error = 'Test failed: ' . $e->getMessage();
                }
                break;
        }
    }
}

// Get current settings
$settings = get_settings();

// Validate current configuration
$validation_errors = validate_postback_config();

$current_page = 'settings';
$page_title = 'Settings';
$page_description = 'Configure your S2S postback settings and test connectivity';

include '_partials/header.php';
?>

<?php if ($message): ?>
    <div class="alert alert-success"><?= h($message) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error"><?= h($error) ?></div>
<?php endif; ?>

<?php if (!empty($validation_errors)): ?>
    <div class="alert alert-error">
        <strong>Configuration Issues:</strong>
        <ul style="margin: 10px 0 0 20px;">
            <?php foreach ($validation_errors as $err): ?>
                <li><?= h($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <h2>Postback Configuration</h2>
    <p style="color: #a1a1aa; margin-bottom: 30px;">
        Configure your S2S postback URL and default parameters. This URL will be called when conversions are fired from offer pages.
    </p>
    
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <input type="hidden" name="action" value="update_settings">
        
        <div class="form-group">
            <label for="postback_base_url">Postback Base URL *</label>
            <input type="url" id="postback_base_url" name="postback_base_url" 
                   placeholder="https://tr.optimawall.com/pbtr?transaction_id={transaction_id}&goal={goal}"
                   value="<?= h($settings['postback_base_url'] ?? '') ?>" required>
            <div class="form-help">
                Your tracker's postback URL. Macros like {transaction_id}, {goal}, {amount} will be replaced automatically.
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="default_goal">Default Goal</label>
                <input type="text" id="default_goal" name="default_goal" 
                       placeholder="conversion"
                       value="<?= h($settings['default_goal'] ?? '') ?>">
                <div class="form-help">Default goal/event name for conversions</div>
            </div>
            
            <div class="form-group">
                <label for="default_amount">Default Amount</label>
                <input type="number" id="default_amount" name="default_amount" 
                       step="0.01" min="0" placeholder="1.00"
                       value="<?= h($settings['default_amount'] ?? '') ?>">
                <div class="form-help">Default conversion amount/payout</div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="extra_params">Extra Parameters</label>
            <textarea id="extra_params" name="extra_params" 
                      placeholder='{"source":"s2s_tool","version":"1.0"} or source=s2s_tool&version=1.0'><?= h($settings['extra_params'] ?? '') ?></textarea>
            <div class="form-help">
                Additional parameters to include in all postbacks. Use JSON format or query string format (key=value&key2=value2).
            </div>
        </div>
        
        <button type="submit" class="btn">Save Settings</button>
    </form>
</div>

<div class="card">
    <h2>Test Postback</h2>
    <p style="color: #a1a1aa; margin-bottom: 20px;">
        Test your postback configuration by sending a sample postback with test data.
    </p>
    
    <?php if (empty($settings['postback_base_url'])): ?>
        <div class="alert alert-info">
            Please configure your postback URL above before testing.
        </div>
    <?php else: ?>
        <?php
        $test_params = [
            'transaction_id' => 'TEST_' . generate_transaction_id(),
            'goal' => $settings['default_goal'] ?? 'test_conversion',
            'amount' => $settings['default_amount'] ?? '1.00',
            'status' => 'approved'
        ];
        
        try {
            $test_url = build_postback_url($test_params);
        } catch (Exception $e) {
            $test_url = 'Error: ' . $e->getMessage();
        }
        ?>
        
        <div style="margin-bottom: 20px;">
            <h4>Test URL that will be called:</h4>
            <div class="code-block">
                <?= h($test_url) ?>
                <button class="copy-btn" data-copy="<?= h($test_url) ?>">Copy</button>
            </div>
        </div>
        
        <div style="margin-bottom: 20px;">
            <h4>Test Parameters:</h4>
            <div style="background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 10px; font-family: monospace;">
                <?php foreach ($test_params as $key => $value): ?>
                    <div><?= h($key) ?>: <?= h($value) ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <form method="post" style="display: inline;">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="action" value="test_postback">
            <button type="submit" class="btn">Send Test Postback</button>
        </form>
    <?php endif; ?>
    
    <?php if ($test_result): ?>
        <div style="margin-top: 30px;">
            <h3>Test Results</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;">
                <div style="text-align: center; padding: 15px; background: rgba(255, 255, 255, 0.05); border-radius: 10px;">
                    <div style="font-size: 1.5rem; font-weight: 700; color: <?= $test_result['success'] ? '#4ade80' : '#f87171' ?>;">
                        <?= $test_result['success'] ? 'SUCCESS' : 'FAILED' ?>
                    </div>
                    <div style="color: #a1a1aa; font-size: 0.9rem;">Status</div>
                </div>
                
                <div style="text-align: center; padding: 15px; background: rgba(255, 255, 255, 0.05); border-radius: 10px;">
                    <div style="font-size: 1.5rem; font-weight: 700; color: #667eea;">
                        <?= h($test_result['http_code'] ?? 'N/A') ?>
                    </div>
                    <div style="color: #a1a1aa; font-size: 0.9rem;">HTTP Code</div>
                </div>
                
                <div style="text-align: center; padding: 15px; background: rgba(255, 255, 255, 0.05); border-radius: 10px;">
                    <div style="font-size: 1.5rem; font-weight: 700; color: #667eea;">
                        <?= h($test_result['execution_time']) ?>ms
                    </div>
                    <div style="color: #a1a1aa; font-size: 0.9rem;">Response Time</div>
                </div>
            </div>
            
            <?php if ($test_result['response']): ?>
                <h4>Response Body:</h4>
                <div style="background: rgba(0, 0, 0, 0.3); padding: 15px; border-radius: 8px; max-height: 200px; overflow-y: auto; font-family: monospace; font-size: 0.9rem;">
                    <?= h($test_result['response']) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($test_result['error']): ?>
                <h4 style="color: #f87171; margin-top: 15px;">Error:</h4>
                <div style="color: #f87171; font-family: monospace;">
                    <?= h($test_result['error']) ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Configuration Examples</h2>
    
    <h3>Popular Tracker Examples</h3>
    
    <div style="margin-bottom: 20px;">
        <h4>Optima Wall</h4>
        <div class="code-block" style="cursor: pointer;" onclick="setPostbackUrl(this.textContent.trim())">
            https://tr.optimawall.com/pbtr?transaction_id={transaction_id}&goal={goal}&amount={amount}
        </div>
    </div>
    
    <div style="margin-bottom: 20px;">
        <h4>Generic S2S Tracker</h4>
        <div class="code-block" style="cursor: pointer;" onclick="setPostbackUrl(this.textContent.trim())">
            https://yourtracker.com/postback?tid={transaction_id}&offer_id={offer_id}&payout={amount}&status=approved
        </div>
    </div>
    
    <div style="margin-bottom: 20px;">
        <h4>Testing with httpbin.org</h4>
        <div class="code-block" style="cursor: pointer;" onclick="setPostbackUrl(this.textContent.trim())">
            https://httpbin.org/get?transaction_id={transaction_id}&goal={goal}&test=true
        </div>
    </div>
    
    <h3>Extra Parameters Examples</h3>
    
    <div style="margin-bottom: 15px;">
        <h4>JSON Format</h4>
        <div class="code-block" style="cursor: pointer;" onclick="setExtraParams(this.textContent.trim())">
            {"source":"s2s_tool","version":"1.0","affiliate_id":"12345"}
        </div>
    </div>
    
    <div style="margin-bottom: 15px;">
        <h4>Query String Format</h4>
        <div class="code-block" style="cursor: pointer;" onclick="setExtraParams(this.textContent.trim())">
            source=s2s_tool&version=1.0&affiliate_id=12345
        </div>
    </div>
</div>

<script>
    function setPostbackUrl(url) {
        document.getElementById('postback_base_url').value = url;
    }
    
    function setExtraParams(params) {
        document.getElementById('extra_params').value = params;
    }
    
    // Add click handlers for copy buttons
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const text = this.getAttribute('data-copy');
            copyToClipboard(text);
        });
    });
</script>

<?php include '_partials/footer.php'; ?>