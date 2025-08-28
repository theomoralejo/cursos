<?php
if ( ! defined('ABSPATH') ) exit;
add_action('init', function(){
    $common = array(
        'public' => true,
        'show_in_rest' => true,
        'supports' => array('title','editor','thumbnail','excerpt','author','page-attributes'),
    );
    register_post_type('course', array_merge($common, array('label'=>'Cursos','rewrite'=>array('slug'=>'cursos'),'menu_icon'=>'dashicons-welcome-learn-more')));
    register_post_type('turma', array_merge($common, array('label'=>'Turmas','rewrite'=>array('slug'=>'turmas'),'menu_icon'=>'dashicons-groups')));
    register_post_type('module', array_merge($common, array('label'=>'Módulos','rewrite'=>array('slug'=>'modulos'),'menu_icon'=>'dashicons-clipboard')));
    register_post_type('lesson', array_merge($common, array('label'=>'Aulas','rewrite'=>array('slug'=>'aulas'),'menu_icon'=>'dashicons-video-alt3')));
});
