<?php
defined('ABSPATH') || exit;

class SaaSphere_Security {
    
    public static function init() {
        add_action('init', [__CLASS__, 'setup_security']);
        add_filter('authenticate', [__CLASS__, 'rate_limit_login'], 30, 3);
    }

    public static function setup_security() {
        if (!session_id() && !headers_sent()) {
            session_start(['cookie_httponly' => true, 'cookie_secure' => is_ssl()]);
        }
    }

    public static function verify_nonce($nonce, $action = 'saasphere_nonce') {
        return wp_verify_nonce($nonce, $action);
    }

    public static function verify_request() {
        if (!is_user_logged_in()) return false;
        if (!check_ajax_referer('saasphere_nonce', 'nonce', false)) return false;
        return true;
    }

    public static function rate_limit_login($user, $username, $password) {
        if (empty($username)) return $user;
        $ip = self::get_client_ip();
        $key = 'saasphere_login_attempts_' . md5($ip);
        $attempts = (int) get_transient($key);
        if ($attempts >= 5) {
            return new WP_Error('too_many_attempts', __('Trop de tentatives. Veuillez réessayer dans 15 minutes.', 'saasphere-erp'));
        }
        if (is_wp_error($user)) {
            set_transient($key, $attempts + 1, 15 * MINUTE_IN_SECONDS);
        } else {
            delete_transient($key);
        }
        return $user;
    }

    public static function sanitize_input($data, $type = 'text') {
        switch ($type) {
            case 'email': return sanitize_email($data);
            case 'url': return esc_url_raw($data);
            case 'int': return absint($data);
            case 'float': return (float) $data;
            case 'html': return wp_kses_post($data);
            case 'textarea': return sanitize_textarea_field($data);
            case 'array':
                if (!is_array($data)) return [];
                return array_map(function($v) { return sanitize_text_field($v); }, $data);
            default: return sanitize_text_field($data);
        }
    }

    public static function get_client_ip() {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = explode(',', $_SERVER[$header])[0];
                if (filter_var(trim($ip), FILTER_VALIDATE_IP)) return trim($ip);
            }
        }
        return '0.0.0.0';
    }

    public static function generate_token($user_id, $expiry = DAY_IN_SECONDS) {
        $payload = ['user_id' => $user_id, 'exp' => time() + $expiry, 'iat' => time()];
        $encoded = base64_encode(wp_json_encode($payload));
        $signature = hash_hmac('sha256', $encoded, wp_salt('auth'));
        return $encoded . '.' . $signature;
    }

    public static function verify_token($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 2) return false;
        $signature = hash_hmac('sha256', $parts[0], wp_salt('auth'));
        if (!hash_equals($signature, $parts[1])) return false;
        $payload = json_decode(base64_decode($parts[0]), true);
        if (!$payload || $payload['exp'] < time()) return false;
        return $payload;
    }

    public static function encrypt($data) {
        $key = wp_salt('auth');
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    public static function decrypt($data) {
        $key = wp_salt('auth');
        $decoded = base64_decode($data);
        $iv = substr($decoded, 0, 16);
        $encrypted = substr($decoded, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
}

SaaSphere_Security::init();
