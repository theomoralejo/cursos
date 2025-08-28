<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Admin UI: user profile metabox to grant/extend/revoke access and log view.
 */

add_action('show_user_profile', 'la_user_access_profile');
add_action('edit_user_profile', 'la_user_access_profile');

function la_user_access_profile($user){
    if ( ! current_user_can('manage_options') ) return;
    // show access list and form
    global $wpdb;
    $table = $wpdb->prefix . 'la_access';
    $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE user_id=%d ORDER BY granted_at DESC", $user->ID));
    echo '<h2>Controle de Acessos (Learning Atelier)</h2>';
    echo '<table class="form-table"><tr><th>Acessos atuais</th><td>';
    if ($rows) {
        echo '<ul>';
        foreach($rows as $r){
            echo '<li><strong>product:</strong> ' . esc_html($r->product_id) . ' — expira: ' . esc_html($r->expires_at) . ' — manual_extension_until: ' . esc_html($r->manual_extension_until) . ' <form method="post" style="display:inline">' . wp_nonce_field('la_user_access_action','la_user_access_nonce',true,false) . '<input type="hidden" name="la_user_action" value="revoke"><input type="hidden" name="la_user_target" value="' . intval($r->id) . '"><input type="submit" class="button" value="Revogar"></form></li>';
        }
        echo '</ul>';
    } else {
        echo '<p>Nenhum acesso registrado.</p>';
    }
    echo '</td></tr></table>';

    // form to grant +30 days
    echo '<h3>Conceder Acesso +30 dias</h3>';
    echo '<form method="post">' . wp_nonce_field('la_user_access_action','la_user_access_nonce',true,false) . '<input type="hidden" name="la_user_action" value="grant">Produto (ID): <input type="text" name="la_product_id" value="" style="width:80px"> <input type="submit" class="button button-primary" value="Conceder +30d"></form>';

    // show logs (last 10)
    $log_table = $wpdb->prefix . 'la_access_logs';
    $logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$log_table} WHERE user_id=%d ORDER BY created_at DESC LIMIT 10", $user->ID));
    echo '<h3>Últimos logs</h3>';
    if ($logs) {
        echo '<ul>';
        foreach($logs as $l){
            echo '<li>' . esc_html($l->created_at) . ' — <strong>' . esc_html($l->action) . '</strong> — by: ' . esc_html($l->performed_by) . ' — ' . esc_html($l->note) . '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p>Sem logs.</p>';
    }
}

add_action('edit_user_profile_update', 'la_user_access_profile_save');
add_action('personal_options_update', 'la_user_access_profile_save');

function la_user_access_profile_save($user_id){
    if ( ! current_user_can('manage_options') ) return;
    if ( ! isset($_POST['la_user_access_nonce']) && ! isset($_POST['la_user_access_nonce']) ) {
        // fallthrough
    }
    if ( ! isset($_POST['la_user_access_nonce']) || ! wp_verify_nonce($_POST['la_user_access_nonce'],'la_user_access_action') ) {
        return;
    }
    if ( isset($_POST['la_user_action']) && $_POST['la_user_action'] === 'grant' ) {
        $product = intval($_POST['la_product_id']);
        la_grant_access($user_id, $product, 30, 'admin grant');
        la_log_action($user_id,'grant_admin',get_current_user_id(),'product:'.$product);
    } elseif ( isset($_POST['la_user_action']) && $_POST['la_user_action'] === 'revoke' ) {
        $target_id = intval($_POST['la_user_target']);
        // delete by id from la_access table
        global $wpdb;
        $table = $wpdb->prefix . 'la_access';
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id=%d", $target_id));
        if ($row) {
            $wpdb->delete($table, array('id'=>$target_id));
            la_log_action($user_id,'revoke_admin',get_current_user_id(),'access_row:'.$target_id);
        }
    }
}
