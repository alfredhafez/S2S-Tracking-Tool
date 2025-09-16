<?php
/**
 * Postback Client - Handles firing postbacks with cURL
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/model.php';
require_once __DIR__ . '/macros.php';

/**
 * Fire a postback using cURL
 */
function fire_postback($url, $params = [], $offer_id = null, $transaction_id = null) {
    $response = [
        'success' => false,
        'url' => '',
        'http_code' => null,
        'response' => null,
        'error' => null,
        'curl_error' => null,
        'execution_time' => 0
    ];
    
    $start_time = microtime(true);
    
    try {
        // Build the final URL with parameters
        $final_url = build_url($url, $params);
        $response['url'] = $final_url;
        
        // Validate URL
        if (!is_safe_url($final_url)) {
            throw new Exception('Invalid or unsafe URL: ' . $final_url);
        }
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt_array($ch, [
            CURLOPT_URL => $final_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => CURL_TIMEOUT,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => CURL_FOLLOW_REDIRECTS,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => CURL_SSL_VERIFY,
            CURLOPT_SSL_VERIFYHOST => CURL_SSL_VERIFY ? 2 : 0,
            CURLOPT_USERAGENT => 'S2S-Postback-Tool/1.0 (+' . base_url() . ')',
            CURLOPT_HTTPHEADER => [
                'Accept: application/json, text/plain, */*',
                'Cache-Control: no-cache'
            ]
        ]);
        
        // Execute the request
        $response['response'] = curl_exec($ch);
        $response['http_code'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            $response['curl_error'] = curl_error($ch);
            throw new Exception('cURL Error: ' . $response['curl_error']);
        }
        
        // Check HTTP status
        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $response['success'] = true;
        } else {
            $response['error'] = 'HTTP Error: ' . $response['http_code'];
        }
        
        curl_close($ch);
        
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        if (isset($ch)) {
            curl_close($ch);
        }
    }
    
    $response['execution_time'] = round((microtime(true) - $start_time) * 1000, 2); // in milliseconds
    
    // Log the postback
    log_postback(
        $offer_id,
        $transaction_id,
        $response['url'],
        $response['http_code'],
        substr($response['response'], 0, 1000), // Limit response length
        $response['error'] ?: $response['curl_error']
    );
    
    return $response;
}

/**
 * Build postback URL from settings and parameters
 */
function build_postback_url($params = []) {
    $settings = get_settings();
    
    if (empty($settings['postback_base_url'])) {
        throw new Exception('Postback base URL not configured in settings');
    }
    
    $base_url = $settings['postback_base_url'];
    
    // Start with default parameters from settings
    $postback_params = [];
    
    // Add default goal and amount if set
    if (!empty($settings['default_goal'])) {
        $postback_params['goal'] = $settings['default_goal'];
    }
    if (!empty($settings['default_amount'])) {
        $postback_params['amount'] = $settings['default_amount'];
    }
    
    // Add extra parameters from settings
    if (!empty($settings['extra_params'])) {
        $extra_params = [];
        
        // Try to decode as JSON first
        if (is_string($settings['extra_params'])) {
            $decoded = json_decode($settings['extra_params'], true);
            if ($decoded !== null) {
                $extra_params = $decoded;
            } else {
                // Parse as query string
                parse_str($settings['extra_params'], $extra_params);
            }
        } elseif (is_array($settings['extra_params'])) {
            $extra_params = $settings['extra_params'];
        }
        
        $postback_params = array_merge($postback_params, $extra_params);
    }
    
    // Override with provided parameters
    $postback_params = array_merge($postback_params, $params);
    
    // Replace macros in base URL
    $base_url = replace_macros($base_url, $postback_params);
    
    // Replace macros in parameters
    foreach ($postback_params as $key => $value) {
        $postback_params[$key] = replace_macros($value, $postback_params);
    }
    
    return build_url($base_url, $postback_params);
}

/**
 * Fire postback for a conversion
 */
function fire_conversion_postback($offer_id, $transaction_id, $additional_params = []) {
    try {
        // Get offer details
        $offer = get_offer($offer_id);
        if (!$offer) {
            throw new Exception('Offer not found: ' . $offer_id);
        }
        
        // Get conversion details
        $conversion = get_conversion_by_tid($offer_id, $transaction_id);
        
        // Build parameters
        $params = array_merge([
            'transaction_id' => $transaction_id,
            'tid' => $transaction_id,
            'sub1' => $transaction_id,
            'offer_id' => $offer_id,
            'offer_name' => $offer['name'],
            'offer_slug' => $offer['slug']
        ], $additional_params);
        
        // Add conversion data if available
        if ($conversion) {
            if (!empty($conversion['goal'])) {
                $params['goal'] = $conversion['goal'];
            }
            if (!empty($conversion['amount'])) {
                $params['amount'] = $conversion['amount'];
            }
            if (!empty($conversion['status'])) {
                $params['status'] = $conversion['status'];
            }
        }
        
        // Build postback URL
        $postback_url = build_postback_url($params);
        
        // Fire the postback
        return fire_postback($postback_url, [], $offer_id, $transaction_id);
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'url' => '',
            'http_code' => null,
            'response' => null,
            'execution_time' => 0
        ];
    }
}

/**
 * Test postback with sample data
 */
function test_postback($custom_params = []) {
    $test_params = array_merge([
        'transaction_id' => 'TEST_' . generate_transaction_id(),
        'goal' => 'test_conversion',
        'amount' => '1.00',
        'status' => 'approved'
    ], $custom_params);
    
    try {
        $postback_url = build_postback_url($test_params);
        return fire_postback($postback_url, [], null, $test_params['transaction_id']);
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'url' => '',
            'http_code' => null,
            'response' => null,
            'execution_time' => 0
        ];
    }
}

/**
 * Validate postback URL and check for issues
 */
function validate_postback_config() {
    $errors = [];
    $settings = get_settings();
    
    // Check if postback base URL is set
    if (empty($settings['postback_base_url'])) {
        $errors[] = 'Postback base URL is not configured';
        return $errors;
    }
    
    // Validate URL format
    if (!is_valid_url($settings['postback_base_url'])) {
        $errors[] = 'Postback base URL is not a valid URL';
    }
    
    // Check for unresolved macros in base URL
    if (has_unresolved_macros($settings['postback_base_url'])) {
        $unresolved = find_macros($settings['postback_base_url']);
        $errors[] = 'Postback base URL contains unresolved macros: ' . implode(', ', $unresolved);
    }
    
    // Validate extra parameters if set
    if (!empty($settings['extra_params'])) {
        $extra_params = [];
        
        // Try to decode/parse extra parameters
        if (is_string($settings['extra_params'])) {
            $decoded = json_decode($settings['extra_params'], true);
            if ($decoded !== null) {
                $extra_params = $decoded;
            } else {
                parse_str($settings['extra_params'], $extra_params);
                if (empty($extra_params)) {
                    $errors[] = 'Extra parameters are not in valid JSON or query string format';
                }
            }
        }
        
        // Check for unresolved macros in extra parameters
        foreach ($extra_params as $key => $value) {
            if (has_unresolved_macros($value)) {
                $unresolved = find_macros($value);
                $errors[] = "Extra parameter '$key' contains unresolved macros: " . implode(', ', $unresolved);
            }
        }
    }
    
    return $errors;
}