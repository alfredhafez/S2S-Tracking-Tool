<?php
/**
 * S2S Postback Receiver (Optional)
 * URL: s2s.php?offer=ID&tid=TID&amount=&status=
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/model.php';
require_once __DIR__ . '/includes/macros.php';

// Get parameters
$offer_id = $_GET['offer'] ?? $_GET['offer_id'] ?? null;
$transaction_id = $_GET['tid'] ?? $_GET['transaction_id'] ?? $_GET['sub1'] ?? null;
$amount = $_GET['amount'] ?? null;
$status = $_GET['status'] ?? 'approved';
$goal = $_GET['goal'] ?? null;

$response = [
    'success' => false,
    'message' => '',
    'data' => []
];

try {
    // Validate required parameters
    if (empty($offer_id) || !is_numeric($offer_id)) {
        throw new Exception('Missing or invalid offer ID');
    }
    
    if (empty($transaction_id)) {
        throw new Exception('Missing transaction ID');
    }
    
    // Validate offer exists
    $offer = get_offer($offer_id);
    if (!$offer) {
        throw new Exception('Offer not found');
    }
    
    // Get or create conversion
    $existing_conversion = get_conversion_by_tid($offer_id, $transaction_id);
    
    if ($existing_conversion) {
        // Update existing conversion
        $stmt = db()->prepare("
            UPDATE conversions 
            SET goal = COALESCE(?, goal), 
                amount = COALESCE(?, amount), 
                status = ?
            WHERE offer_id = ? AND transaction_id = ?
        ");
        
        $stmt->execute([$goal, $amount, $status, $offer_id, $transaction_id]);
        
        $response['message'] = 'Conversion updated successfully';
        $response['data'] = [
            'action' => 'updated',
            'offer_id' => $offer_id,
            'transaction_id' => $transaction_id,
            'previous_status' => $existing_conversion['status'],
            'new_status' => $status
        ];
    } else {
        // Record new conversion
        $conversion_recorded = record_conversion($offer_id, $transaction_id, $goal, $amount, $status);
        
        if ($conversion_recorded) {
            $response['message'] = 'Conversion recorded successfully';
            $response['data'] = [
                'action' => 'created',
                'offer_id' => $offer_id,
                'transaction_id' => $transaction_id,
                'status' => $status
            ];
        } else {
            throw new Exception('Failed to record conversion');
        }
    }
    
    // Log the incoming postback
    log_postback(
        $offer_id,
        $transaction_id,
        $_SERVER['REQUEST_URI'],
        200,
        json_encode($response),
        null
    );
    
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    
    // Log the error
    log_postback(
        $offer_id,
        $transaction_id,
        $_SERVER['REQUEST_URI'],
        400,
        null,
        $e->getMessage()
    );
    
    http_response_code(400);
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>