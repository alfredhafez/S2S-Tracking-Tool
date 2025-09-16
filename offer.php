<?php
/**
 * Local offer page with conversion form
 * URL: offer.php?id=ID&tid=TID
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/model.php';
require_once __DIR__ . '/includes/macros.php';
require_once __DIR__ . '/includes/postback_client.php';

// Get parameters
$offer_id = $_GET['id'] ?? null;
$transaction_id = $_GET['tid'] ?? null;

// Validate parameters
if (empty($offer_id) || !is_numeric($offer_id)) {
    http_response_code(400);
    die('Error: Missing or invalid offer ID');
}

if (empty($transaction_id)) {
    http_response_code(400);
    die('Error: Missing transaction ID');
}

// Get offer details
$offer = get_offer($offer_id);
if (!$offer) {
    http_response_code(404);
    die('Error: Offer not found');
}

$conversion_result = null;
$postback_result = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_conversion'])) {
    // Validate CSRF token
    if (!csrf_verify($_POST['csrf_token'] ?? '')) {
        $conversion_result = ['error' => 'Invalid security token. Please refresh and try again.'];
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Basic validation
        if (empty($name) || empty($email)) {
            $conversion_result = ['error' => 'Please fill in all required fields.'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $conversion_result = ['error' => 'Please enter a valid email address.'];
        } else {
            try {
                // Get settings for conversion data
                $settings = get_settings();
                $goal = $settings['default_goal'] ?? 'conversion';
                $amount = $settings['default_amount'] ?? null;
                
                // Record conversion (idempotent)
                $conversion_recorded = record_conversion($offer_id, $transaction_id, $goal, $amount);
                
                // Fire postback
                $postback_result = fire_conversion_postback($offer_id, $transaction_id, [
                    'user_name' => $name,
                    'user_email' => $email
                ]);
                
                if ($postback_result['success']) {
                    $conversion_result = [
                        'success' => true,
                        'message' => 'Conversion recorded and postback fired successfully!'
                    ];
                } else {
                    $conversion_result = [
                        'success' => true,
                        'message' => 'Conversion recorded, but postback failed.',
                        'postback_error' => $postback_result['error']
                    ];
                }
                
            } catch (Exception $e) {
                $conversion_result = ['error' => 'Failed to process conversion: ' . $e->getMessage()];
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($offer['name']) ?> - S2S Tool</title>
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
            max-width: 800px;
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
        
        .offer-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .offer-info {
            margin-bottom: 30px;
        }
        
        .offer-info h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.8rem;
        }
        
        .offer-meta {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 10px 20px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #a1a1aa;
        }
        
        .offer-meta strong {
            color: #e1e1e1;
        }
        
        .conversion-form {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 25px;
            margin-top: 30px;
        }
        
        .conversion-form h3 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.4rem;
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
        
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            color: #e1e1e1;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input::placeholder {
            color: #a1a1aa;
        }
        
        .btn {
            width: 100%;
            padding: 14px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
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
        
        .postback-details {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
        }
        
        .postback-details h4 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .postback-url {
            background: rgba(0, 0, 0, 0.3);
            padding: 10px;
            border-radius: 5px;
            word-break: break-all;
            margin-bottom: 10px;
        }
        
        .postback-response {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-success {
            background: rgba(34, 197, 94, 0.2);
            color: #4ade80;
        }
        
        .status-error {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>S2S Tool</h1>
            <p>Test Offer Conversion</p>
        </div>
        
        <div class="offer-card">
            <div class="offer-info">
                <h2><?= h($offer['name']) ?></h2>
                
                <div class="offer-meta">
                    <strong>Offer ID:</strong> <span><?= h($offer['id']) ?></span>
                    <strong>Slug:</strong> <span><?= h($offer['slug']) ?></span>
                    <strong>Transaction ID:</strong> <span><?= h($transaction_id) ?></span>
                    <strong>Your IP:</strong> <span><?= h(ip()) ?></span>
                </div>
                
                <?php if (!empty($offer['notes'])): ?>
                    <div style="margin-top: 15px; padding: 15px; background: rgba(255, 255, 255, 0.05); border-radius: 10px;">
                        <strong>Notes:</strong> <?= h($offer['notes']) ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($conversion_result): ?>
                <div class="alert <?= isset($conversion_result['error']) ? 'alert-error' : 'alert-success' ?>">
                    <?= h($conversion_result['error'] ?? $conversion_result['message']) ?>
                    
                    <?php if (isset($conversion_result['postback_error'])): ?>
                        <br><strong>Postback Error:</strong> <?= h($conversion_result['postback_error']) ?>
                    <?php endif; ?>
                </div>
                
                <?php if ($postback_result): ?>
                    <div class="postback-details">
                        <h4>Postback Details</h4>
                        
                        <div><strong>URL:</strong></div>
                        <div class="postback-url"><?= h($postback_result['url']) ?></div>
                        
                        <div style="margin-bottom: 10px;">
                            <strong>Status:</strong> 
                            <span class="status-badge <?= $postback_result['success'] ? 'status-success' : 'status-error' ?>">
                                <?= $postback_result['success'] ? 'SUCCESS' : 'FAILED' ?>
                            </span>
                            
                            <?php if ($postback_result['http_code']): ?>
                                <span style="margin-left: 10px;">HTTP <?= h($postback_result['http_code']) ?></span>
                            <?php endif; ?>
                            
                            <span style="margin-left: 10px;"><?= h($postback_result['execution_time']) ?>ms</span>
                        </div>
                        
                        <?php if ($postback_result['response']): ?>
                            <div><strong>Response:</strong></div>
                            <div class="postback-response" style="background: rgba(0, 0, 0, 0.3); padding: 10px; border-radius: 5px;">
                                <?= h($postback_result['response']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($postback_result['error']): ?>
                            <div style="color: #f87171; margin-top: 10px;">
                                <strong>Error:</strong> <?= h($postback_result['error']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <div class="conversion-form">
                <h3>Complete Conversion</h3>
                <p style="color: #a1a1aa; margin-bottom: 20px;">
                    Submit this form to record a conversion and fire the S2S postback to your tracker.
                </p>
                
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter your name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                    
                    <button type="submit" name="submit_conversion" class="btn">Submit Conversion</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>