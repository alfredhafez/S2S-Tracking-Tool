<?php
/**
 * Click tracking and redirection endpoint
 * URL: click.php?offer=ID&sub1={transaction_id} or &tid=...
 */

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/model.php';
require_once __DIR__ . '/includes/macros.php';

// Get parameters
$offer_id = $_GET['offer'] ?? $_GET['offer_id'] ?? null;
$transaction_id = $_GET['sub1'] ?? $_GET['tid'] ?? $_GET['transaction_id'] ?? null;

// Validate offer ID
if (empty($offer_id) || !is_numeric($offer_id)) {
    http_response_code(400);
    die('Error: Missing or invalid offer ID');
}

// Get offer details
$offer = get_offer($offer_id);
if (!$offer) {
    http_response_code(404);
    die('Error: Offer not found');
}

// Generate/ensure transaction ID
$transaction_id = ensure_tid($transaction_id);

// Record the click
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$click_recorded = record_click($offer_id, $transaction_id, ip(), $user_agent);

// Determine redirect URL
$redirect_url = '';

if (!empty($offer['partner_url_template'])) {
    // Use partner URL with macro replacement
    $macro_params = [
        'transaction_id' => $transaction_id,
        'tid' => $transaction_id,
        'sub1' => $transaction_id,
        'offer_id' => $offer_id,
        'offer_name' => $offer['name'],
        'offer_slug' => $offer['slug']
    ];
    
    $redirect_url = replace_macros($offer['partner_url_template'], $macro_params);
    
    // Validate the URL
    if (!is_safe_url($redirect_url)) {
        // Fall back to local offer page if partner URL is invalid
        $redirect_url = base_url("offer.php?id=$offer_id&tid=$transaction_id");
    }
} else {
    // Use local offer page as fallback
    $redirect_url = base_url("offer.php?id=$offer_id&tid=$transaction_id");
}

// Perform redirect
header("Location: $redirect_url", true, 302);
exit;
?>