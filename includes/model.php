<?php
/**
 * Database Model Functions
 */

require_once __DIR__ . '/bootstrap.php';

/**
 * Settings Functions
 */
function get_settings() {
    $stmt = db()->prepare("SELECT * FROM settings WHERE id = 1");
    $stmt->execute();
    return $stmt->fetch() ?: [];
}

function update_settings($data) {
    $pdo = db();
    
    // Prepare extra_params as JSON if it's an array
    if (isset($data['extra_params']) && is_array($data['extra_params'])) {
        $data['extra_params'] = json_encode($data['extra_params']);
    }
    
    $stmt = $pdo->prepare("
        UPDATE settings SET 
            postback_base_url = ?, 
            default_goal = ?, 
            default_amount = ?, 
            extra_params = ?,
            updated_at = NOW()
        WHERE id = 1
    ");
    
    return $stmt->execute([
        $data['postback_base_url'] ?? '',
        $data['default_goal'] ?? '',
        $data['default_amount'] ?? null,
        $data['extra_params'] ?? ''
    ]);
}

/**
 * Offers Functions
 */
function get_offers($limit = null) {
    $sql = "SELECT * FROM offers ORDER BY created_at DESC";
    if ($limit) {
        $sql .= " LIMIT " . (int)$limit;
    }
    
    $stmt = db()->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll();
}

function get_offer($id) {
    $stmt = db()->prepare("SELECT * FROM offers WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function get_offer_by_slug($slug) {
    $stmt = db()->prepare("SELECT * FROM offers WHERE slug = ?");
    $stmt->execute([$slug]);
    return $stmt->fetch();
}

function create_offer($data) {
    $stmt = db()->prepare("
        INSERT INTO offers (name, slug, partner_url_template, notes, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    return $stmt->execute([
        $data['name'],
        $data['slug'],
        $data['partner_url_template'] ?? '',
        $data['notes'] ?? ''
    ]);
}

function update_offer($id, $data) {
    $stmt = db()->prepare("
        UPDATE offers SET 
            name = ?, 
            slug = ?, 
            partner_url_template = ?, 
            notes = ?
        WHERE id = ?
    ");
    
    return $stmt->execute([
        $data['name'],
        $data['slug'],
        $data['partner_url_template'] ?? '',
        $data['notes'] ?? '',
        $id
    ]);
}

function delete_offer($id) {
    $stmt = db()->prepare("DELETE FROM offers WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Clicks Functions
 */
function record_click($offer_id, $transaction_id, $ip, $user_agent = '') {
    $stmt = db()->prepare("
        INSERT IGNORE INTO clicks (offer_id, transaction_id, ip, ua, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    return $stmt->execute([$offer_id, $transaction_id, $ip, $user_agent]);
}

function get_clicks($limit = 50, $offset = 0) {
    $stmt = db()->prepare("
        SELECT c.*, o.name as offer_name 
        FROM clicks c 
        LEFT JOIN offers o ON c.offer_id = o.id 
        ORDER BY c.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

function count_clicks($date_filter = null) {
    $sql = "SELECT COUNT(*) FROM clicks";
    $params = [];
    
    if ($date_filter) {
        $sql .= " WHERE created_at >= ?";
        $params[] = $date_filter;
    }
    
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

/**
 * Conversions Functions
 */
function record_conversion($offer_id, $transaction_id, $goal = null, $amount = null, $status = 'approved') {
    $stmt = db()->prepare("
        INSERT IGNORE INTO conversions (offer_id, transaction_id, goal, amount, status, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    return $stmt->execute([$offer_id, $transaction_id, $goal, $amount, $status]);
}

function get_conversions($limit = 50, $offset = 0) {
    $stmt = db()->prepare("
        SELECT c.*, o.name as offer_name 
        FROM conversions c 
        LEFT JOIN offers o ON c.offer_id = o.id 
        ORDER BY c.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

function count_conversions($date_filter = null) {
    $sql = "SELECT COUNT(*) FROM conversions";
    $params = [];
    
    if ($date_filter) {
        $sql .= " WHERE created_at >= ?";
        $params[] = $date_filter;
    }
    
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function get_conversion_by_tid($offer_id, $transaction_id) {
    $stmt = db()->prepare("
        SELECT * FROM conversions 
        WHERE offer_id = ? AND transaction_id = ?
    ");
    $stmt->execute([$offer_id, $transaction_id]);
    return $stmt->fetch();
}

/**
 * Postbacks Functions
 */
function log_postback($offer_id, $transaction_id, $url, $http_code = null, $response = null, $error = null) {
    $stmt = db()->prepare("
        INSERT INTO postbacks (offer_id, transaction_id, url, http_code, response, error, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    return $stmt->execute([$offer_id, $transaction_id, $url, $http_code, $response, $error]);
}

function get_postbacks($limit = 50, $offset = 0) {
    $stmt = db()->prepare("
        SELECT p.*, o.name as offer_name 
        FROM postbacks p 
        LEFT JOIN offers o ON p.offer_id = o.id 
        ORDER BY p.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$limit, $offset]);
    return $stmt->fetchAll();
}

/**
 * Analytics Functions
 */
function get_dashboard_stats() {
    $pdo = db();
    
    // Today's stats
    $today_clicks = $pdo->query("SELECT COUNT(*) FROM clicks WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $today_conversions = $pdo->query("SELECT COUNT(*) FROM conversions WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    
    // 7 days stats
    $week_clicks = $pdo->query("SELECT COUNT(*) FROM clicks WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    $week_conversions = $pdo->query("SELECT COUNT(*) FROM conversions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
    
    // 30 days stats
    $month_clicks = $pdo->query("SELECT COUNT(*) FROM clicks WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
    $month_conversions = $pdo->query("SELECT COUNT(*) FROM conversions WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
    
    // All time stats
    $total_clicks = $pdo->query("SELECT COUNT(*) FROM clicks")->fetchColumn();
    $total_conversions = $pdo->query("SELECT COUNT(*) FROM conversions")->fetchColumn();
    $total_offers = $pdo->query("SELECT COUNT(*) FROM offers")->fetchColumn();
    
    return [
        'today' => [
            'clicks' => $today_clicks,
            'conversions' => $today_conversions,
            'ctr' => $today_clicks > 0 ? round(($today_conversions / $today_clicks) * 100, 2) : 0
        ],
        'week' => [
            'clicks' => $week_clicks,
            'conversions' => $week_conversions,
            'ctr' => $week_clicks > 0 ? round(($week_conversions / $week_clicks) * 100, 2) : 0
        ],
        'month' => [
            'clicks' => $month_clicks,
            'conversions' => $month_conversions,
            'ctr' => $month_clicks > 0 ? round(($month_conversions / $month_clicks) * 100, 2) : 0
        ],
        'total' => [
            'clicks' => $total_clicks,
            'conversions' => $total_conversions,
            'offers' => $total_offers,
            'ctr' => $total_clicks > 0 ? round(($total_conversions / $total_clicks) * 100, 2) : 0
        ]
    ];
}

function get_top_offers($limit = 5) {
    $stmt = db()->prepare("
        SELECT 
            o.id,
            o.name,
            COUNT(DISTINCT c.id) as clicks,
            COUNT(DISTINCT cv.id) as conversions,
            CASE 
                WHEN COUNT(DISTINCT c.id) > 0 
                THEN ROUND((COUNT(DISTINCT cv.id) / COUNT(DISTINCT c.id)) * 100, 2)
                ELSE 0 
            END as ctr
        FROM offers o
        LEFT JOIN clicks c ON o.id = c.offer_id AND c.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        LEFT JOIN conversions cv ON o.id = cv.offer_id AND cv.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY o.id, o.name
        ORDER BY clicks DESC, conversions DESC
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}