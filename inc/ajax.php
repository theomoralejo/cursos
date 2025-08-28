<?php
// Helper: detect lesson post type (lesson | capitulo)
function la_detect_lesson_pt(){
    if ( post_type_exists('lesson') ) return 'lesson';
    if ( post_type_exists('capitulo') ) return 'capitulo';
    return 'lesson';
}

if ( ! defined('ABSPATH') ) exit;

// Get modules for a course
add_action('wp_ajax_la_get_modules_by_course', function(){
    if ( ! current_user_can('edit_posts') ) wp_send_json_error('no_cap');
    $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
    $mods = array();
    if ($course_id){
        $mods = get_posts(array(
            'post_type'=>'module',
            'posts_per_page'=>-1,
            'orderby'=>'menu_order',
            'order'=>'ASC',
            'meta_key'=>'_module_course',
            'meta_value'=>$course_id,
        ));
    }
    $out = array();
    foreach($mods as $m){
        $out[] = array('id'=>$m->ID, 'title'=>$m->post_title);
    }
    wp_send_json_success($out);
});

// Get lessons for a module (not yet linked OR all)
add_action('wp_ajax_la_get_lessons_by_module', function(){
    if ( ! current_user_can('edit_posts') ) wp_send_json_error('no_cap');
    $module_id = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
    $less = array();
    if ($module_id){
        $current = get_post_meta($module_id, '_module_lessons', true) ?: array();
        // If filter by course_tree is passed, restrict by that term
        $term_id = isset($_GET['tree_term']) ? intval($_GET['tree_term']) : 0;
        $args = array('post_type'=> la_detect_lesson_pt(),'posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC');
        if ($term_id){
            $args['tax_query'] = array(array('taxonomy'=>'la_course_tree','field'=>'term_id','terms'=>array($term_id)));
        }
        $lessons = get_posts($args);
        foreach($lessons as $l){
            if (in_array($l->ID, (array)$current)) continue;
            $less[] = array('id'=>$l->ID, 'title'=>$l->post_title, 'edit'=>admin_url('post.php?post='.$l->ID.'&action=edit'));
        }
    }
    wp_send_json_success($less);
});

// Add selected lessons to module (merge + set lesson meta)
add_action('wp_ajax_la_add_lessons_to_module', function(){
    if ( ! current_user_can('edit_posts') ) wp_send_json_error('no_cap');
    check_admin_referer('la_module_add_lessons','nonce');
    $module_id = isset($_POST['module_id']) ? intval($_POST['module_id']) : 0;
    $ids = isset($_POST['lesson_ids']) ? array_map('intval', (array) $_POST['lesson_ids']) : array();
    if (!$module_id || empty($ids)) wp_send_json_error('bad_params');

    $current = get_post_meta($module_id, '_module_lessons', true) ?: array();
    $new = array_unique(array_merge((array)$current, $ids));
    update_post_meta($module_id, '_module_lessons', $new);

    // Set lesson meta (module & course) for each added lesson
    $course_for_module = get_post_meta($module_id, '_module_course', true);
    foreach($ids as $lid){
        update_post_meta($lid, '_lesson_module', $module_id);
        if ($course_for_module) update_post_meta($lid, '_lesson_course', intval($course_for_module));
    }
    wp_send_json_success(array('count'=>count($ids),'total'=>count($new)));
});

add_action('wp_ajax_la_get_child_terms', function(){
    if ( ! current_user_can('edit_posts') ) wp_send_json_error('no_cap');
    $parent = isset($_GET['parent']) ? intval($_GET['parent']) : 0;
    $children = get_terms(array('taxonomy'=>'la_course_tree','parent'=>$parent,'hide_empty'=>false));
    $out = array();
    foreach($children as $t){ $out[] = array('id'=>$t->term_id,'name'=>$t->name); }
    wp_send_json_success($out);
});

add_action('wp_ajax_la_get_current_lessons_by_module', function(){
    if ( ! current_user_can('edit_posts') ) wp_send_json_error('no_cap');
    $module_id = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
    $out = array();
    if ($module_id){
        $list = get_post_meta($module_id, '_module_lessons', true) ?: array();
        foreach((array)$list as $lid){
            $p = get_post($lid); if(!$p) continue;
            $out[] = array('id'=>intval($lid), 'title'=>$p->post_title, 'edit'=>admin_url('post.php?post='.$lid.'&action=edit'));
        }
    }
    wp_send_json_success($out);
});
