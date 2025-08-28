<?php
if ( ! defined('ABSPATH') ) exit;
add_action('woocommerce_order_status_completed', 'la_woocommerce_order_completed', 10, 1);
function la_woocommerce_order_completed($order_id){
    if (!class_exists('WC_Order')) return;
    $order = wc_get_order($order_id);
    if (! $order ) return;
    $user_id = $order->get_user_id();
    foreach($order->get_items() as $item){
        $product_id = $item->get_product_id();
        // Grant access for the purchased product_id for 30 days
        la_grant_access($user_id, $product_id, 30, 'woo order #' . $order_id);
        la_log_action($user_id,'grant_woo',get_current_user_id(),'order:'.$order_id . ' product:'.$product_id);
    }
}
