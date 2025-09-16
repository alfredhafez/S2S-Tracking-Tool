<?php
require_once __DIR__ . '/includes/postback.php';

$offer_id = isset($_GET['offer']) ? (int)$_GET['offer'] : 0;
$transaction_id = ensure_tid($_GET['sub1'] ?? $_GET['tid'] ?? null);

if ($offer_id <= 0) {
    http_response_code(400);
    echo '<link rel="stylesheet" href="css/style.css">';
    echo '<div class="container error">Missing or invalid offer parameter.</div>';
    exit;
}

$offer = get_offer($offer_id);
if (!$offer) {
    http_response_code(404);
    echo '<link rel="stylesheet" href="css/style.css">';
    echo '<div class="container error">Offer not found.</div>';
    exit;
}

record_click($offer_id, $transaction_id, get_client_ip());

$redirect = trim((string)($offer['url'] ?? ''));
if ($redirect !== '') {
    $redirect = replace_macros($redirect, $transaction_id);
} else {
    $redirect = 'offer.php?id=' . urlencode((string)$offer_id) . '&tid=' . urlencode($transaction_id);
}

header('Location: ' . $redirect);
exit;