<?php
if ( ! defined('ABSPATH') ) exit;

function la_render_user_access_meta($user){
    if ( ! current_user_can('manage_options') ) return;
    global $wpdb;
    $table = $wpdb->prefix . 'la_access';
    $table_log = $wpdb->prefix . 'la_access_log';
    $accesses = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE user_id=%d ORDER BY expires_at DESC", $user->ID));
    $logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_log} WHERE user_id=%d ORDER BY created_at DESC LIMIT 50", $user->ID));
    ?>
    <h2>Learning Atelier — Acessos e Logs</h2>
    <table class="form-table">
      <tr><th>Acessos</th><td>
        <table class="widefat striped">
          <thead><tr><th>Produto ID</th><th>Concedido em</th><th>Expira em</th><th>Extensão manual</th><th>Ações</th></tr></thead>
          <tbody>
          <?php if ($accesses): foreach($accesses as $a): ?>
            <tr>
              <td><?php echo esc_html($a->product_id); ?></td>
              <td><?php echo esc_html($a->granted_at); ?></td>
              <td><?php echo esc_html($a->expires_at); ?></td>
              <td><?php echo esc_html($a->manual_extension_until); ?></td>
              <td>
                <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" style="display:inline">
                  <input type="hidden" name="action" value="la_user_extend_access" />
                  <input type="hidden" name="user_id" value="<?php echo intval($user->ID); ?>" />
                  <input type="hidden" name="product_id" value="<?php echo intval($a->product_id); ?>" />
                  <input type="number" name="days" value="30" style="width:70px" />
                  <?php wp_nonce_field('la_user_extend_access','la_user_extend_nonce'); ?>
                  <button class="button">+ Dias</button>
                </form>
                <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" style="display:inline;margin-left:6px;">
                  <input type="hidden" name="action" value="la_user_revoke_access" />
                  <input type="hidden" name="user_id" value="<?php echo intval($user->ID); ?>" />
                  <input type="hidden" name="product_id" value="<?php echo intval($a->product_id); ?>" />
                  <?php wp_nonce_field('la_user_revoke_access','la_user_revoke_nonce'); ?>
                  <button class="button button-link-delete">Revogar</button>
                </form>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="5">Nenhum acesso registrado.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </td></tr>

      <tr><th>Logs recentes</th><td>
        <table class="widefat striped">
          <thead><tr><th>Quando</th><th>Ação</th><th>Produto</th><th>Por</th><th>Nota</th></tr></thead>
          <tbody>
          <?php if ($logs): foreach($logs as $l): ?>
            <tr>
              <td><?php echo esc_html($l->created_at); ?></td>
              <td><?php echo esc_html($l->action); ?></td>
              <td><?php echo esc_html($l->product_id); ?></td>
              <td><?php echo esc_html($l->by_user_id); ?></td>
              <td><?php echo esc_html($l->note); ?></td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="5">Sem logs.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </td></tr>
    </table>
    <?php
}
add_action('show_user_profile', 'la_render_user_access_meta');
add_action('edit_user_profile', 'la_render_user_access_meta');

// Handlers for extend/revoke from profile
add_action('admin_post_la_user_extend_access', function(){
    if ( ! current_user_can('manage_options') ) wp_die('Sem permissão');
    check_admin_referer('la_user_extend_access','la_user_extend_nonce');
    $user_id = intval($_POST['user_id']);
    $product_id = intval($_POST['product_id']) ?: null;
    $days = intval($_POST['days']) ?: 30;
    la_extend_access($user_id, $product_id, $days);
    wp_redirect( wp_get_referer() );
    exit;
});
add_action('admin_post_la_user_revoke_access', function(){
    if ( ! current_user_can('manage_options') ) wp_die('Sem permissão');
    check_admin_referer('la_user_revoke_access','la_user_revoke_nonce');
    $user_id = intval($_POST['user_id']);
    $product_id = intval($_POST['product_id']) ?: null;
    la_revoke_access($user_id, $product_id);
    wp_redirect( wp_get_referer() );
    exit;
});
