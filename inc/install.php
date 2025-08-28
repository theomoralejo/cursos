<?php
if ( ! defined('ABSPATH') ) exit;
function la_install(){
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    $table1 = $wpdb->prefix . 'course_progress';
    $sql1 = "CREATE TABLE IF NOT EXISTS {$table1} (
      id BIGINT(20) NOT NULL AUTO_INCREMENT,
      user_id BIGINT(20) NOT NULL,
      lesson_id BIGINT(20) NOT NULL,
      course_id BIGINT(20) DEFAULT NULL,
      completed_at DATETIME NOT NULL,
      PRIMARY KEY (id),
      KEY user_lesson (user_id,lesson_id)
    ) {$charset_collate};";
    $table2 = $wpdb->prefix . 'la_access';
    $sql2 = "CREATE TABLE IF NOT EXISTS {$table2} (
      id BIGINT(20) NOT NULL AUTO_INCREMENT,
      user_id BIGINT(20) NOT NULL,
      product_id BIGINT(20) DEFAULT NULL,
      granted_at DATETIME NOT NULL,
      expires_at DATETIME NOT NULL,
      manual_extension_until DATETIME DEFAULT NULL,
      note VARCHAR(255) DEFAULT NULL,
      PRIMARY KEY (id),
      KEY user_product (user_id,product_id)
    ) {$charset_collate};";
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta($sql1);
    dbDelta($sql2);
    
    $table3 = $wpdb->prefix . 'la_access_logs';
    $sql3 = "CREATE TABLE IF NOT EXISTS {$table3} (
      id BIGINT(20) NOT NULL AUTO_INCREMENT,
      user_id BIGINT(20) NOT NULL,
      action VARCHAR(60) NOT NULL,
      performed_by BIGINT(20) DEFAULT NULL,
      note TEXT DEFAULT NULL,
      created_at DATETIME NOT NULL,
      PRIMARY KEY (id),
      KEY user_idx (user_id)
    ) {$charset_collate};";
    dbDelta($sql3);
}
