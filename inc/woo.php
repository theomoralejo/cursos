<?php
if ( ! defined('ABSPATH') ) exit;

// Add product settings metabox on WooCommerce product
add_action('add_meta_boxes', function(){
    if ( post_type_exists('product') ) {
        add_meta_box('la_wc_product_settings', 'Acesso ao Curso (Learning Atelier)', function($post){
            $access_days = get_post_meta($post->ID, '_product_access_days', true);
            $access_days = $access_days ? intval($access_days) : 30;
            $is_assessoria = get_post_meta($post->ID, '_product_is_assessoria', true) ? 'checked' : '';
            echo '<p><label>Dias de acesso padrão: </label><br/><input type="number" name="_product_access_days" value="' . esc_attr($access_days) . '" min="1" style="width:80px"/></p>';
            echo '<p><label><input type="checkbox" name="_product_is_assessoria" value="1" ' . $is_assessoria . ' /> Produto é <strong>Assessoria</strong></label></p>';
            wp_nonce_field('la_save_wc_product','la_wc_product_nonce');
        }, 'product', 'side', 'default');
    }
});

add_action('save_post_product', function($post_id){
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( ! isset($_POST['la_wc_product_nonce']) || ! wp_verify_nonce($_POST['la_wc_product_nonce'], 'la_save_wc_product') ) return;
    if ( isset($_POST['_product_access_days']) ) update_post_meta($post_id, '_product_access_days', intval($_POST['_product_access_days']));
    if ( isset($_POST['_product_is_assessoria']) ) update_post_meta($post_id, '_product_is_assessoria', 1);
    else delete_post_meta($post_id, '_product_is_assessoria');
}, 10, 1);

// WooCommerce: grant access on order completed + stamp item meta (curso/turma/tempo)
add_action('woocommerce_order_status_completed', function($order_id){
    if ( ! function_exists('wc_get_order') ) return;
    $order = wc_get_order($order_id);
    if ( ! $order ) return;
    $user_id = $order->get_user_id();
    if ( ! $user_id ) return;

    foreach( $order->get_items() as $item_id => $item ){
        $product_id = $item->get_product_id();
        $days = intval( get_post_meta($product_id, '_product_access_days', true) ) ?: 30;

        // Find courses linked to this product
        $courses = get_posts(array(
            'post_type' => 'course',
            'post_status' => 'any',
            'numberposts' => -1,
            'meta_key' => '_course_product_id',
            'meta_value' => $product_id,
        ));

        if ($courses){
            foreach($courses as $course){
                // Choose turma: first in course (ordered) if exists
                $turmas = get_post_meta($course->ID, '_course_turmas', true);
                $turma_id = (is_array($turmas) && !empty($turmas)) ? intval($turmas[0]) : 0;
                if ($turma_id) {
                    update_user_meta($user_id, 'la_turma_for_course_' . $course->ID, $turma_id);
                }

                // Grant access
                la_grant_access($user_id, $product_id, $days, 'Woo order #'.$order_id.' -> course '.$course->ID);

                // Fetch expiration
                $row = la_check_access($user_id, $product_id);
                $granted_at = $row ? $row->granted_at : current_time('mysql',1);
                $expires_at = $row ? ($row->manual_extension_until ?: $row->expires_at) : date('Y-m-d H:i:s', strtotime(current_time('mysql',1) . " + {$days} days"));

                // Format dates
                $date_format = get_option('date_format') . ' ' . get_option('time_format');
                $granted_fmt = mysql2date($date_format, $granted_at);
                $expires_fmt = mysql2date($date_format, $expires_at);

                // Avoid duplicates
                $already = false;
                $existing = $item->get_meta('Curso (Learning Atelier)', false);
                if ($existing){
                    foreach($existing as $ex){
                        $val = is_object($ex) && isset($ex->value) ? (string)$ex->value : (string)$ex;
                        if (strpos($val, '#'.$course->ID) !== false) { $already = true; break; }
                    }
                }
                if ($already) continue;

                // Stamp meta
                $item->add_meta_data('Curso (Learning Atelier)', $course->post_title . '  #' . $course->ID, true);
                if ($turma_id){
                    $turma = get_post($turma_id);
                    $item->add_meta_data('Turma', ($turma ? $turma->post_title : 'Turma').'  #' . $turma_id, true);
                } else {
                    $item->add_meta_data('Turma', 'Não definida', true);
                }
                $item->add_meta_data('Acesso (dias)', $days, true);
                $item->add_meta_data('Concedido em', $granted_fmt, true);
                $item->add_meta_data('Expira em', $expires_fmt, true);
                $item->save();
            }
        }
    }
}, 25, 1);
