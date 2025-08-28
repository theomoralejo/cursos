<?php
if (!defined('LA_TOKEN_SECRET')) {
    // Use AUTH_KEY (from wp-config.php) if available; fallback to SECURE_AUTH_KEY or an auto-generated option.
    if ( defined('AUTH_KEY') && AUTH_KEY ) {
        define('LA_TOKEN_SECRET', AUTH_KEY);
    } elseif ( defined('SECURE_AUTH_KEY') && SECURE_AUTH_KEY ) {
        define('LA_TOKEN_SECRET', SECURE_AUTH_KEY);
    } else {
        // As a last resort, use an option-based secret to avoid undefined function errors.
        $opt = get_option('la_token_secret');
        if ( ! $opt ) {
            $opt = wp_generate_password(32, true, true);
            add_option('la_token_secret', $opt);
        }
        define('LA_TOKEN_SECRET', $opt);
    }
}
if ( ! defined('ABSPATH') ) exit;
function la_grant_access($user_id, $product_id = null, $days = 30, $note = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'la_access';
    $now = current_time('mysql', 1);
    $expires = date('Y-m-d H:i:s', strtotime("+{$days} days"));
    $wpdb->insert($table, array(
        'user_id' => $user_id,
        'product_id' => $product_id,
        'granted_at' => $now,
        'expires_at' => $expires,
        'note' => $note
    ));
    la_log_action($user_id, $product_id, 'grant', get_current_user_id(), $note);
    return $wpdb->insert_id;
}
function la_check_access($user_id, $product_id = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'la_access';
    $now = current_time('mysql', 1);
    if ($product_id) {
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE user_id=%d AND product_id=%d AND (expires_at > %s OR (manual_extension_until IS NOT NULL AND manual_extension_until > %s)) ORDER BY expires_at DESC LIMIT 1", $user_id, $product_id, $now, $now));
    } else {
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE user_id=%d AND (expires_at > %s OR (manual_extension_until IS NOT NULL AND manual_extension_until > %s)) ORDER BY expires_at DESC LIMIT 1", $user_id, $now, $now));
    }
    return $row ? $row : false;
}
function la_extend_access($user_id, $product_id = null, $days = 30) {
    global $wpdb;
    $table = $wpdb->prefix . 'la_access';
    $now = current_time('mysql', 1);
    $row = la_check_access($user_id, $product_id);
    if ($row) {
        $base = (strtotime($row->expires_at) > time()) ? $row->expires_at : current_time('mysql',1);
        $new_until = date('Y-m-d H:i:s', strtotime($base . " + {$days} days"));
        $wpdb->update($table, array('manual_extension_until' => $new_until, 'note' => ($row->note . ' | extended')), array('id' => $row->id));
        la_log_action($user_id, $product_id, 'extend', get_current_user_id(), 'extend +'.$days.'d');
        return true;
    } else {
        la_grant_access($user_id, $product_id, $days, 'manual grant');
        la_log_action($user_id, $product_id, 'grant', get_current_user_id(), 'manual grant '.$days.'d');
        return true;
    }
}

function la_revoke_access($user_id, $product_id = null) {
    global $wpdb;
    $table = $wpdb->prefix . 'la_access';
    if ($product_id) {
        $res = $wpdb->delete($table, array('user_id'=>$user_id, 'product_id'=>$product_id));
    } else {
        $res = $wpdb->delete($table, array('user_id'=>$user_id));
    }
    la_log_action($user_id, $product_id, 'revoke', get_current_user_id(), 'revoke');
    return $res;
}

function la_generate_token($user_id, $lesson_id, $ttl_seconds = 3600) {
    $secret = LA_TOKEN_SECRET;
    $expires = time() + (int)$ttl_seconds;
    $payload = $user_id . '|' . $lesson_id . '|' . $expires;
    $hmac = hash_hmac('sha256', $payload, $secret);
    return rtrim(strtr(base64_encode($payload . '|' . $hmac), '+/', '-_'), '=');
}
function la_validate_token($token) {
    $secret = LA_TOKEN_SECRET;
    $raw = base64_decode(strtr($token, '-_', '+/'));
    if (! $raw) return false;
    $parts = explode('|', $raw);
    if (count($parts) !== 4) return false;
    list($user_id, $lesson_id, $expires, $hmac) = $parts;
    if (time() > intval($expires)) return false;
    $payload = $user_id . '|' . $lesson_id . '|' . $expires;
    $calc = hash_hmac('sha256', $payload, $secret);
    return hash_equals($calc, $hmac) ? array('user_id'=>intval($user_id),'lesson_id'=>intval($lesson_id)) : false;
}


// Logging actions (grant/extend/revoke)
function la_log_action($user_id, $action, $performed_by = null, $note = '') {
    global $wpdb;
    $table = $wpdb->prefix . 'la_access_logs';
    $wpdb->insert($table, array(
        'user_id' => $user_id,
        'action' => $action,
        'performed_by' => $performed_by,
        'note' => $note,
        'created_at' => current_time('mysql', 1)
    ));
}

