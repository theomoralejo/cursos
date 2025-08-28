<?php
if ( ! defined('ABSPATH') ) exit;

add_action('restrict_manage_posts', function(){
    global $typenow;
    if ($typenow !== 'lesson') return;
    $courses = get_posts(array('post_type'=>'course','posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC'));
    $current = isset($_GET['filter_course']) ? intval($_GET['filter_course']) : 0;
    echo '<select name="filter_course" id="filter_course">';
    echo '<option value="">Todos os cursos</option>';
    foreach($courses as $c){
        $sel = $current === $c->ID ? 'selected' : '';
        echo '<option value="'.intval($c->ID).'" '.$sel.'>'.esc_html($c->post_title).' (ID: '.$c->ID.')</option>';
    }
    echo '</select>';
});

add_action('pre_get_posts', function($query){
    global $pagenow, $typenow;
    if (is_admin() && $pagenow == 'edit.php' && $typenow == 'lesson' && isset($_GET['filter_course']) && $_GET['filter_course'] != ''){
        $course = intval($_GET['filter_course']);
        $meta_query = array(
            array(
                'key' => '_lesson_course',
                'value' => $course,
                'compare' => '='
            )
        );
        $query->set('meta_query', $meta_query);
    }
});

// Filter modules by course in admin list
add_action('restrict_manage_posts', function(){
    global $typenow;
    if ($typenow !== 'module') return;
    $courses = get_posts(array('post_type'=>'course','posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC'));
    $current = isset($_GET['filter_module_course']) ? intval($_GET['filter_module_course']) : 0;
    echo '<select name="filter_module_course" id="filter_module_course">';
    echo '<option value="">Todos os cursos</option>';
    foreach($courses as $c){
        $sel = $current === $c->ID ? 'selected' : '';
        echo '<option value="'.intval($c->ID).'" '.$sel.'>'.esc_html($c->post_title).' (ID: '.$c->ID.')</option>';
    }
    echo '</select>';
});
add_action('pre_get_posts', function($query){
    global $pagenow, $typenow;
    if (is_admin() && $pagenow == 'edit.php' && $typenow == 'module' && isset($_GET['filter_module_course']) && $_GET['filter_module_course'] != ''){
        $course = intval($_GET['filter_module_course']);
        $meta_query = array(
            array(
                'key' => '_module_course',
                'value' => $course,
                'compare' => '='
            )
        );
        $query->set('meta_query', $meta_query);
    }
});
