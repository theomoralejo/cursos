<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Retrieve lesson IDs for a module.
 */
function la_get_module_lesson_ids( $module_id ) {
    $lessons = get_posts( array(
        'post_type'      => 'lesson',
        'meta_key'       => '_lesson_module',
        'meta_value'     => $module_id,
        'posts_per_page' => -1,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'fields'         => 'ids',
    ) );
    return array_map( 'intval', $lessons );
}

/**
 * Return an array of lesson IDs the user has completed within the provided set.
 */
function la_get_user_completed_lessons( $user_id, $lesson_ids ) {
    global $wpdb;
    $lesson_ids = array_map( 'intval', (array) $lesson_ids );
    if ( empty( $lesson_ids ) ) return array();

    $placeholders = implode( ',', array_fill( 0, count( $lesson_ids ), '%d' ) );
    $table        = $wpdb->prefix . 'course_progress';
    $params       = array_merge( array( $user_id ), $lesson_ids );
    $sql          = $wpdb->prepare( "SELECT lesson_id FROM {$table} WHERE user_id=%d AND lesson_id IN ({$placeholders})", $params );
    return array_map( 'intval', $wpdb->get_col( $sql ) );
}

/**
 * Calculate completion percentage for a module.
 */
function la_user_module_progress( $user_id, $module_id ) {
    $lesson_ids = la_get_module_lesson_ids( $module_id );
    if ( empty( $lesson_ids ) ) return 0;

    $completed = la_get_user_completed_lessons( $user_id, $lesson_ids );
    $total     = count( $lesson_ids );
    return $total ? ( count( $completed ) / $total * 100 ) : 0;
}

/**
 * Check if a specific lesson is completed by the user.
 */
function la_user_lesson_completed( $user_id, $lesson_id ) {
    $done = la_get_user_completed_lessons( $user_id, array( $lesson_id ) );
    return ! empty( $done );
}

/**
 * Whether the user has finished all lessons in the module.
 */
function la_user_module_completed( $user_id, $module_id ) {
    return la_user_module_progress( $user_id, $module_id ) >= 100;
}
