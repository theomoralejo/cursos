<?php
if ( ! defined('ABSPATH') ) exit;
function la_user_module_completed($user_id, $module_id) {
    global $wpdb;
    $lessons = get_posts(array('post_type'=>'lesson','meta_key'=>'_lesson_module','meta_value'=>$module_id,'posts_per_page'=>-1));
    if (empty($lessons)) return false;
    $lesson_ids = wp_list_pluck($lessons,'ID');
    $placeholders = implode(',', array_fill(0, count($lesson_ids), '%d'));
    $table = $wpdb->prefix . 'course_progress';
    $params = array_merge(array($user_id), $lesson_ids);
    $sql = $wpdb->prepare("SELECT COUNT(DISTINCT lesson_id) FROM {$table} WHERE user_id=%d AND lesson_id IN ({$placeholders})", $params);
    $count = $wpdb->get_var($sql);
    return intval($count) === count($lesson_ids);
}
