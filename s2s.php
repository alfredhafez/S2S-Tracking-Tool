<?php
require_once __DIR__ . '/includes/postback.php';

// Get parameters
$offer_id = isset($_GET['offer']) ? (int)$_GET['offer'] : 0;
$transaction_id = trim((string)($_GET['tid'] ?? $_GET['transaction_id'] ?? ''));
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : null;
$status = trim((string)($_GET['status'] ?? 'approved'));

// Basic validation
if ($offer_id <= 0 || $transaction_id === '') {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'ERROR: Missing required parameters (offer, tid)';
    exit;
}

if (empty($status)) {
    $status = 'approved';
}

try {
    // Record conversion
    $success = record_conversion($transaction_id, $offer_id, $amount, $status);
    
    // Log the postback
    log_postback([
        'offer_id' => $offer_id,
        'transaction_id' => $transaction_id,
        'status' => $status,
        'payout' => $amount
    ]);
    
    // Return success response
    http_response_code(200);
    header('Content-Type: text/plain');
    echo 'OK';
    
} catch (Exception $e) {
    // Log error and return failure
    error_log("S2S Postback Error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'ERROR: ' . $e->getMessage();
}