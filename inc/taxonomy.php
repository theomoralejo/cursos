<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Taxonomia hierárquica para organizar Aulas por Curso (pai) e Módulo (filho)
 * Slug: la_course_tree
 * Aplica-se ao post_type 'lesson'.
 */
add_action('init', function(){
    $labels = array(
        'name'              => 'Estrutura (Curso/Módulo)',
        'singular_name'     => 'Estrutura',
        'search_items'      => 'Buscar termos',
        'all_items'         => 'Todos os termos',
        'edit_item'         => 'Editar termo',
        'update_item'       => 'Atualizar termo',
        'add_new_item'      => 'Adicionar novo termo',
        'new_item_name'     => 'Novo termo',
        'menu_name'         => 'Estrutura Curso/Módulo',
    );
    register_taxonomy('la_course_tree', array('lesson'), array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'estrutura'),
        'show_in_rest'      => true,
    ));
});
