<?php
if ( ! defined('ABSPATH') ) exit;
// Bulk assign module action handler
add_action('admin_post_la_bulk_assign_module', function(){
    if ( ! current_user_can('edit_posts') ) wp_die('Permissão negada');
    $module_id = intval($_POST['la_bulk_module_id']);
    $post_ids = array_map('intval', (array) $_POST['post_ids']);
    if ($module_id && $post_ids){
        foreach($post_ids as $pid){
            update_post_meta($pid, '_lesson_module', $module_id);
            // also set lesson course if module has _module_course
            $course = get_post_meta($module_id, '_module_course', true);
            if ($course) update_post_meta($pid, '_lesson_course', $course);
        }
    }
    $redirect = wp_get_referer();
    wp_redirect($redirect);
    exit;
});

// Bulk Action: Atribuir Módulo (lessons)
add_filter('bulk_actions-edit-lesson', function($actions){
    $actions['la_assign_module'] = 'Atribuir Módulo…';
    return $actions;
});

add_filter('handle_bulk_actions-edit-lesson', function($redirect, $doaction, $post_ids){
    if ($doaction !== 'la_assign_module') return $redirect;
    if ( ! current_user_can('edit_posts') ) return $redirect;
    // show a screen to pick module id
    $module_id = isset($_REQUEST['la_bulk_module_id']) ? intval($_REQUEST['la_bulk_module_id']) : 0;
    if (!$module_id){
        // build minimal form to choose module then resubmit
        $modules = get_posts(array('post_type'=>'module','posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC'));
        echo '<div class="wrap"><h1>Atribuir Módulo às Aulas</h1>';
        echo '<form method="post">';
        echo '<input type="hidden" name="action" value="la_assign_module" />';
        foreach($post_ids as $pid){ echo '<input type="hidden" name="post[]" value="'.intval($pid).'" />'; }
        echo '<p><label>Módulo: <select name="la_bulk_module_id">';
        foreach($modules as $m){ echo '<option value="'.$m->ID.'">'.esc_html($m->post_title).' ('.$m->ID.')</option>'; }
        echo '</select></label></p>';
        submit_button('Aplicar');
        echo '</form></div>';
        exit;
    } else {
        $course = get_post_meta($module_id, '_module_course', true);
        foreach($post_ids as $pid){
            update_post_meta($pid, '_lesson_module', $module_id);
            if ($course) update_post_meta($pid, '_lesson_course', $course);
        }
        $redirect = add_query_arg('la_bulk_assigned', count($post_ids), $redirect);
        return $redirect;
    }
}, 10, 3);

add_action('admin_notices', function(){
    if ( isset($_GET['la_bulk_assigned']) ){
        $n = intval($_GET['la_bulk_assigned']);
        echo '<div class="notice notice-success is-dismissible"><p>Módulo atribuído a '+str('n')+' aulas.</p></div>';
    }
});
