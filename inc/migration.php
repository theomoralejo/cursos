<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Ferramenta de migração: Wicked Folders ('wf_capitulo_folders') -> 'la_course_tree'
 */
add_action('admin_menu', function(){
    add_submenu_page('edit.php?post_type=lesson', 'Migrar Folders p/ Taxonomia', 'Migrar Folders', 'manage_options', 'la_migrate_folders', 'la_render_migration_page');
});

function la_render_migration_page(){
    if ( ! current_user_can('manage_options') ) return;
    $ran = false; $log = array();
    if ( isset($_POST['la_do_migration']) && check_admin_referer('la_do_migration') ){
        $ran = true;
        $map = array(); // old term id -> new term id
        $old_tax = 'wf_capitulo_folders';
        $parents = get_terms(array('taxonomy'=>$old_tax, 'parent'=>0, 'hide_empty'=>false));
        foreach($parents as $p){
            $new_parent = term_exists($p->name, 'la_course_tree');
            if (!$new_parent){
                $new_parent = wp_insert_term($p->name, 'la_course_tree');
            }
            $map[$p->term_id] = is_array($new_parent) ? intval($new_parent['term_id']) : intval($new_parent['term_id']);
            $children = get_terms(array('taxonomy'=>$old_tax, 'parent'=>$p->term_id, 'hide_empty'=>false));
            foreach($children as $c){
                $new_child = term_exists($c->name, 'la_course_tree', $map[$p->term_id]);
                if (!$new_child){
                    $new_child = wp_insert_term($c->name, 'la_course_tree', array('parent'=>$map[$p->term_id]));
                }
                $child_id = is_array($new_child) ? intval($new_child['term_id']) : intval($new_child['term_id']);
                $map[$c->term_id] = $child_id;
            }
        }
        // Reatribuir aulas (lessons)
        $lessons = get_posts(array('post_type'=>'lesson', 'posts_per_page'=>-1));
        foreach($lessons as $l){
            $terms = wp_get_post_terms($l->ID, $old_tax, array('fields'=>'ids'));
            $new_terms = array();
            foreach($terms as $tid){ if(isset($map[$tid])) $new_terms[] = $map[$tid]; }
            if ($new_terms){
                wp_set_post_terms($l->ID, $new_terms, 'la_course_tree', false);
                $log[] = "Lesson #{$l->ID} migrada: " . implode(',', $new_terms);
            }
        }
    }
    echo '<div class="wrap"><h1>Migrar Wicked Folders → Taxonomia</h1>';
    echo '<p>Esta ferramenta cria a taxonomia <code>la_course_tree</code> com a mesma estrutura (Curso/Módulo) e reatribui as aulas.</p>';
    echo '<form method="post">'; wp_nonce_field('la_do_migration');
    echo '<p><button type="submit" name="la_do_migration" class="button button-primary">Executar migração</button></p>';
    echo '</form>';
    if ($ran){
        echo '<h3>Resultado</h3><pre style="max-height:320px;overflow:auto;background:#fff;padding:12px;border:1px solid #eee">';
        echo esc_html(implode("\n", $log));
        echo '</pre>';
    }
    echo '</div>';
}
