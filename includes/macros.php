<?php
/**
 * Macro replacement and transaction ID functions
 */

require_once __DIR__ . '/bootstrap.php';

/**
 * Generate a secure transaction ID
 */
function generate_transaction_id() {
    return 'TID_' . time() . '_' . generate_random_string(16);
}

/**
 * Ensure we have a valid transaction ID
 * Returns existing TID if valid, generates new one if missing or placeholder
 */
function ensure_tid($tid = null) {
    // List of placeholder values that should be replaced
    $placeholders = [
        '{transaction_id}',
        '{tid}',
        '{sub1}',
        'TRANSACTION_ID',
        'TID',
        'SUB1',
        ''
    ];
    
    if (empty($tid) || in_array($tid, $placeholders)) {
        return generate_transaction_id();
    }
    
    return $tid;
}

/**
 * Replace macros in a string
 */
function replace_macros($string, $params = []) {
    if (empty($string)) {
        return $string;
    }
    
    // Default parameters
    $defaults = [
        'transaction_id' => '',
        'tid' => '',
        'sub1' => '',
        'goal' => '',
        'amount' => '',
        'offer_id' => '',
        'ip' => ip(),
        'timestamp' => time(),
        'date' => date('Y-m-d'),
        'datetime' => date('Y-m-d H:i:s')
    ];
    
    $params = array_merge($defaults, $params);
    
    // Ensure transaction_id, tid, and sub1 are the same
    if (!empty($params['transaction_id'])) {
        $params['tid'] = $params['transaction_id'];
        $params['sub1'] = $params['transaction_id'];
    } elseif (!empty($params['tid'])) {
        $params['transaction_id'] = $params['tid'];
        $params['sub1'] = $params['tid'];
    } elseif (!empty($params['sub1'])) {
        $params['transaction_id'] = $params['sub1'];
        $params['tid'] = $params['sub1'];
    }
    
    // Build replacement array
    $replacements = [];
    foreach ($params as $key => $value) {
        $replacements['{' . $key . '}'] = $value;
        $replacements['{' . strtoupper($key) . '}'] = $value;
    }
    
    // Perform replacement using strtr for safety
    return strtr($string, $replacements);
}

/**
 * Extract parameters from URL query string
 */
function extract_url_params($url) {
    $params = [];
    $parsed = parse_url($url);
    
    if (isset($parsed['query'])) {
        parse_str($parsed['query'], $params);
    }
    
    return $params;
}

/**
 * Build URL with parameters
 */
function build_url($base_url, $params = []) {
    if (empty($params)) {
        return $base_url;
    }
    
    $parsed = parse_url($base_url);
    $existing_params = [];
    
    // Get existing query parameters
    if (isset($parsed['query'])) {
        parse_str($parsed['query'], $existing_params);
    }
    
    // Merge with new parameters (new params override existing ones)
    $all_params = array_merge($existing_params, $params);
    
    // Rebuild URL
    $result = '';
    if (isset($parsed['scheme'])) {
        $result .= $parsed['scheme'] . '://';
    }
    if (isset($parsed['host'])) {
        $result .= $parsed['host'];
    }
    if (isset($parsed['port'])) {
        $result .= ':' . $parsed['port'];
    }
    if (isset($parsed['path'])) {
        $result .= $parsed['path'];
    }
    
    // Add query string
    if (!empty($all_params)) {
        $result .= '?' . http_build_query($all_params);
    }
    
    // Add fragment
    if (isset($parsed['fragment'])) {
        $result .= '#' . $parsed['fragment'];
    }
    
    return $result;
}

/**
 * Validate that a string doesn't contain unresolved macros
 */
function has_unresolved_macros($string) {
    $macro_patterns = [
        '/\{[a-zA-Z_][a-zA-Z0-9_]*\}/',  // {macro_name}
        '/\[[a-zA-Z_][a-zA-Z0-9_]*\]/',  // [macro_name]
        '/\%[a-zA-Z_][a-zA-Z0-9_]*\%/'   // %macro_name%
    ];
    
    foreach ($macro_patterns as $pattern) {
        if (preg_match($pattern, $string)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Get list of macros found in a string
 */
function find_macros($string) {
    $macros = [];
    $patterns = [
        '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',  // {macro_name}
        '/\[([a-zA-Z_][a-zA-Z0-9_]*)\]/',  // [macro_name]
        '/\%([a-zA-Z_][a-zA-Z0-9_]*)\%/'   // %macro_name%
    ];
    
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $string, $matches)) {
            $macros = array_merge($macros, $matches[1]);
        }
    }
    
    return array_unique($macros);
}

/**
 * Sanitize slug for URL use
 */
function sanitize_slug($string) {
    // Convert to lowercase
    $string = strtolower($string);
    
    // Replace spaces and special characters with hyphens
    $string = preg_replace('/[^a-z0-9]+/', '-', $string);
    
    // Remove leading/trailing hyphens
    $string = trim($string, '-');
    
    // Limit length
    $string = substr($string, 0, 50);
    
    return $string;
}

/**
 * Validate URL format
 */
function is_valid_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Check if URL is safe (basic validation)
 */
function is_safe_url($url) {
    if (!is_valid_url($url)) {
        return false;
    }
    
    $parsed = parse_url($url);
    
    // Check for dangerous schemes
    $dangerous_schemes = ['javascript', 'data', 'vbscript', 'file'];
    if (isset($parsed['scheme']) && in_array(strtolower($parsed['scheme']), $dangerous_schemes)) {
        return false;
    }
    
    // Check for local/private IPs if needed
    if (isset($parsed['host'])) {
        $ip = gethostbyname($parsed['host']);
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            // This is a private/local IP, you might want to allow or disallow based on your needs
            // For now, we'll allow it but you can change this logic
        }
    }
    
    return true;
}

/**
 * URL encode parameters safely
 */
function url_encode_params($params) {
    $encoded = [];
    foreach ($params as $key => $value) {
        $encoded[urlencode($key)] = urlencode($value);
    }
    return $encoded;
}