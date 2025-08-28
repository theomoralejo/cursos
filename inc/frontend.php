<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Front-end shortcodes and admin forms:
 * - [la_product_edit product_id=]  -> form to edit product access fields (admins)
 * - [la_manage_user_access] -> admin form to grant/extend/revoke access for users
 */

add_action('wp_enqueue_scripts', function(){
    wp_enqueue_script('la-frontend-js', LA_URL . 'assets/js/frontend-admin.js', array('jquery'), '0.1', true);
    wp_localize_script('la-frontend-js', 'LA_ADMIN', array(
        'rest_nonce' => wp_create_nonce('wp_rest'),
        'rest_url' => rest_url('la/v1/'),
    ));
    wp_enqueue_style('la-frontend-css', LA_URL . 'assets/css/lesson.css', array(), '0.1');
});

// Shortcode: edit product settings (only for users who can edit the product)
add_shortcode('la_product_edit', function($atts){
    $atts = shortcode_atts(array('product_id'=>0), $atts, 'la_product_edit');
    $product_id = intval($atts['product_id']);
    if (!$product_id) return '<p>Produto não especificado.</p>';
    if (! current_user_can('edit_post', $product_id) ) return '<p>Sem permissão.</p>';
    $access_days = get_post_meta($product_id, '_product_access_days', true) ?: 30;
    $is_assessoria = get_post_meta($product_id, '_product_is_assessoria', true) ? 1 : 0;
    ob_start();
    ?>
    <form id="la-product-edit-form" data-product="<?php echo esc_attr($product_id); ?>">
      <p><label>Dias de acesso padrão: <input type="number" name="access_days" value="<?php echo esc_attr($access_days); ?>" min="1" /></label></p>
      <p><label><input type="checkbox" name="is_assessoria" value="1" <?php echo $is_assessoria ? 'checked' : ''; ?>/> Produto é <strong>Assessoria</strong></label></p>
      <p><button class="la-btn" id="la-product-save">Salvar</button></p>
      <div id="la-product-edit-result"></div>
    </form>
    <?php
    return ob_get_clean();
});

// Shortcode: manage user access (admins)
add_shortcode('la_manage_user_access', function($atts){
    if (! current_user_can('manage_options') ) return '<p>Sem permissão.</p>';
    ob_start();
    ?>
    <form id="la-manage-access-form">
      <p><label>ID do usuário: <input type="number" name="user_id" required /></label></p>
      <p><label>Produto (ID): <input type="number" name="product_id" /></label></p>
      <p><label>Dias (padrão 30): <input type="number" name="days" value="30" /></label></p>
      <p>
        <button class="la-btn" data-action="grant">Conceder Acesso</button>
        <button class="la-btn" data-action="extend">Estender +30d</button>
        <button class="la-btn" data-action="revoke">Revogar</button>
      </p>
      <div id="la-manage-access-result"></div>
    </form>
    <?php
    return ob_get_clean();
});

// Register REST endpoints for front-end actions (admin protected)
add_action('rest_api_init', function(){
    register_rest_route('la/v1', '/product-update', array(
        'methods' => 'POST',
        'callback' => 'la_rest_update_product',
        'permission_callback' => function(){ return current_user_can('edit_posts'); }
    ));
    register_rest_route('la/v1', '/access-action', array(
        'methods' => 'POST',
        'callback' => 'la_rest_access_action',
        'permission_callback' => function(){ return current_user_can('manage_options'); }
    ));
});

function la_rest_update_product($request){
    $product_id = intval($request->get_param('product_id'));
    $access_days = intval($request->get_param('access_days')) ?: 30;
    $is_assessoria = $request->get_param('is_assessoria') ? 1 : 0;
    if (! current_user_can('edit_post', $product_id)) return new WP_Error('forbidden','Sem permissão', array('status'=>403));
    update_post_meta($product_id, '_product_access_days', $access_days);
    if ($is_assessoria) update_post_meta($product_id, '_product_is_assessoria', 1);
    else delete_post_meta($product_id, '_product_is_assessoria');
    return rest_ensure_response(array('status'=>'ok'));
}

function la_rest_access_action($request){
    $user_id = intval($request->get_param('user_id'));
    $product_id = intval($request->get_param('product_id')) ?: null;
    $days = intval($request->get_param('days')) ?: 30;
    $action = sanitize_text_field($request->get_param('action'));
    if ($action === 'grant') {
        la_grant_access($user_id, $product_id, $days, 'granted via frontend');
        return rest_ensure_response(array('status'=>'granted'));
    } elseif ($action === 'extend') {
        la_extend_access($user_id, $product_id, $days);
        return rest_ensure_response(array('status'=>'extended'));
    } elseif ($action === 'revoke') {
        la_revoke_access($user_id, $product_id);
        return rest_ensure_response(array('status'=>'revoked'));
    } else {
        return new WP_Error('bad','Action invalid', array('status'=>400));
    }
}
