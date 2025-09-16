<?php
require_once __DIR__ . '/bootstrap.php';

function get_offer(int $offer_id): ?array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM offers WHERE id = ?');
    $stmt->execute([$offer_id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function replace_macros(string $url, string $transaction_id): string {
    $replacements = [
        '{transaction_id}' => $transaction_id,
        '{tid}' => $transaction_id,
        '{sub1}' => $transaction_id,
    ];
    return strtr($url, $replacements);
}

function ensure_tid(?string $tid): string {
    $tid = trim((string)$tid);
    if ($tid === '' || $tid === '{transaction_id}' || $tid === '{tid}') {
        return bin2hex(random_bytes(8));
    }
    return $tid;
}

function record_click(int $offer_id, string $transaction_id, string $ip): void {
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO clicks (offer_id, transaction_id, ip) VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE ip = VALUES(ip), created_at = VALUES(created_at)');
    $stmt->execute([$offer_id, $transaction_id, $ip]);
}

function find_click(int $offer_id, string $transaction_id): ?array {
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM clicks WHERE offer_id = ? AND transaction_id = ?');
    $stmt->execute([$offer_id, $transaction_id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function record_conversion(string $transaction_id, int $offer_id, ?float $payout = null, string $status = 'approved'): bool {
    $pdo = db();
    // Make sure the click exists to convert (idempotent)
    if (!find_click($offer_id, $transaction_id)) {
        record_click($offer_id, $transaction_id, get_client_ip());
    }
    $stmt = $pdo->prepare('UPDATE clicks SET converted = 1, status = ?, payout = COALESCE(?, payout) WHERE offer_id = ? AND transaction_id = ?');
    $stmt->execute([$status, $payout, $offer_id, $transaction_id]);
    return $stmt->rowCount() >= 0;
}

function log_postback(array $params): void {
    $pdo = db();
    $stmt = $pdo->prepare('INSERT INTO postbacks (offer_id, transaction_id, status, payout, ip, raw_query) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        isset($params['offer_id']) ? (int)$params['offer_id'] : null,
        $params['transaction_id'] ?? null,
        $params['status'] ?? null,
        isset($params['payout']) ? (float)$params['payout'] : null,
        get_client_ip(),
        http_build_query($_GET ?: [])
    ]);
}