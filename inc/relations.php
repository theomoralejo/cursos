<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Metaboxes e salvamento de relações (Curso, Módulo, Turma, Aula)
 */

/**
 * -------- Metabox: Curso -> Módulos e Turmas (disponíveis/selecionados) --------
 */
add_action('add_meta_boxes', function(){
    add_meta_box('la_course_relations', 'Módulos e Turmas vinculados', function($post){
        if ($post->post_type !== 'course') return;

        // Coleta seleção atual
        $selected_modules = get_post_meta($post->ID, '_course_modules', true);
        $selected_modules = is_array($selected_modules) ? array_map('intval', $selected_modules) : array();

        $selected_turmas = get_post_meta($post->ID, '_course_turmas', true);
        $selected_turmas = is_array($selected_turmas) ? array_map('intval', $selected_turmas) : array();

        // Listas disponíveis (exclui já selecionados)
        $all_modules = get_posts(array('post_type'=>'module','posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC'));
        $all_turmas  = get_posts(array('post_type'=>'turma','posts_per_page'=>-1,'orderby'=>'title','order'=>'ASC'));

        echo '<div class="la-flex" style="display:grid;grid-template-columns:1fr 1fr;gap:18px;">';

        // Módulos disponíveis
        echo '<div><h4>Módulos disponíveis</h4><ul class="la-available-modules">';
        foreach ($all_modules as $m){
            if (in_array($m->ID, $selected_modules)) continue;
            echo '<li class="la-item" data-id="'.intval($m->ID).'">'.esc_html($m->post_title).' <span class="small">#'.intval($m->ID).'</span></li>';
        }
        echo '</ul></div>';

        // Módulos selecionados
        echo '<div><h4>Módulos selecionados (arraste p/ ordenar)</h4><ul class="la-selected-modules">';
        foreach ($selected_modules as $mid){
            $m = get_post($mid); if(!$m) continue;
            $edit = admin_url('post.php?post='.$mid.'&action=edit');
            echo '<li class="la-selected" data-id="'.intval($mid).'">'.esc_html($m->post_title).' <button type="button" class="la-remove">×</button> <a class="la-jump" href="'.$edit.'" target="_blank">#'.intval($mid).' ↗︎</a></li>';
        }
        echo '</ul><input type="hidden" id="la_course_modules_order" name="la_course_modules_order" value="'.esc_attr(implode(',', $selected_modules)).'"/></div>';

        // Turmas disponíveis
        echo '<div><h4>Turmas disponíveis</h4><ul class="la-available-turmas">';
        foreach ($all_turmas as $t){
            if (in_array($t->ID, $selected_turmas)) continue;
            echo '<li class="la-item" data-id="'.intval($t->ID).'">'.esc_html($t->post_title).' <span class="small">#'.intval($t->ID).'</span></li>';
        }
        echo '</ul></div>';

        // Turmas selecionadas
        echo '<div><h4>Turmas selecionadas (arraste p/ ordenar)</h4><ul class="la-selected-turmas">';
        foreach ($selected_turmas as $tid){
            $t = get_post($tid); if(!$t) continue;
            $edit = admin_url('post.php?post='.$tid.'&action=edit');
            echo '<li class="la-selected" data-id="'.intval($tid).'">'.esc_html($t->post_title).' <button type="button" class="la-remove">×</button> <a class="la-jump" href="'.$edit.'" target="_blank">#'.intval($tid).' ↗︎</a></li>';
        }
        echo '</ul><input type="hidden" id="la_course_turmas_order" name="la_course_turmas_order" value="'.esc_attr(implode(',', $selected_turmas)).'"/></div>';

        echo '</div>'; // .la-flex

        wp_nonce_field('la_save_course_rel', 'la_course_rel_nonce');
        echo '<p class="description">Selecione módulos e turmas. Use clique para adicionar e arraste para ordenar.</p>';
    }, 'course', 'normal', 'default');
});

/**
 * -------- Metabox: Curso -> Gerenciar Aulas por Módulo (UI unificada) --------
 */
add_action('add_meta_boxes', function(){
    add_meta_box('la_course_lessons_manage', 'Aulas vinculadas (gerenciar por módulo)', function($post){
        if ($post->post_type !== 'course') return;
        $selected_modules = get_post_meta($post->ID, '_course_modules', true);
        $selected_modules = is_array($selected_modules) ? array_map('intval', $selected_modules) : array();
        if (empty($selected_modules)){
            echo '<p>Adicione módulos ao curso para gerenciar aulas.</p>';
            return;
        }

        echo '<div class="la-course-unified">';
        echo '<p><label>Módulo: </label><select id="la_manage_module" style="min-width:280px">';
        foreach ($selected_modules as $mid){
            $m = get_post($mid); if(!$m) continue;
            echo '<option value="'.intval($mid).'">'.esc_html($m->post_title).' (#'.intval($mid).')</option>';
        }
        echo '</select></p>';

        $first = intval($selected_modules[0]);
        $current = get_post_meta($first, '_module_lessons', true);
        $current = is_array($current) ? array_map('intval', $current) : array();

        echo '<div class="la-course-unified-wrap" data-current-module="'.$first.'">';

        // Coluna: atuais
        echo '<div class="la-col">';
        echo '<h4>Aulas do módulo (arraste para ordenar)</h4>';
        echo '<ul id="la_current_lessons" class="la-selected-lessons-module">';
        foreach ($current as $lid){
            $l = get_post($lid); if(!$l) continue;
            $edit = admin_url('post.php?post='.$lid.'&action=edit');
            echo '<li class="la-selected" data-id="'.intval($lid).'">'.esc_html($l->post_title).' <button type="button" class="la-remove">×</button> <a class="la-jump" href="'.$edit.'" target="_blank">#'.intval($lid).' ↗︎</a></li>';
        }
        echo '</ul>';
        echo '<input type="hidden" name="la_course_module_lessons['.$first.']" id="la_course_module_lessons_hidden" value="'.esc_attr(implode(',', $current)).'"/>';
        echo '</div>';

        // Coluna: disponíveis (carrega via AJAX)
        echo '<div class="la-col">';
        echo '<h4>Aulas disponíveis</h4>';
        echo '<div class="la-toolbar"><label><input type="checkbox" id="la_av_select_all"> Selecionar todos</label> <input type="text" id="la_av_search" placeholder="Buscar..." style="margin-left:10px;min-width:200px;"></div>';
        echo '<ul id="la_available_lessons" class="la-list-checkbox"><li><em>carregando…</em></li></ul>';
        echo '<p><button type="button" class="button button-primary" id="la_add_selected_lessons">Adicionar selecionadas</button></p>';
        echo '</div>';

        echo '</div>'; // .la-course-unified-wrap

        wp_nonce_field('la_save_course_rel', 'la_course_rel_nonce');
        echo '<p class="description">A ordem é salva no módulo selecionado.</p>';
    }, 'course', 'normal', 'default');
});

/**
 * -------- Metabox: Módulo -> Aulas selecionadas (para conferência/ordenar) --------
 */
add_action('add_meta_boxes', function(){
    add_meta_box('la_module_lessons', 'Aulas do Módulo (ordenar)', function($post){
        if ($post->post_type !== 'module') return;
        $lessons = get_post_meta($post->ID, '_module_lessons', true);
        $lessons = is_array($lessons) ? array_map('intval', $lessons) : array();
        echo '<ul class="la-selected-lessons">';
        foreach ($lessons as $lid){
            $l = get_post($lid); if(!$l) continue;
            $edit = admin_url('post.php?post='.$lid.'&action=edit');
            echo '<li data-id="'.intval($lid).'">'.esc_html($l->post_title).' <button type="button" class="la-remove">×</button> <a class="la-jump" href="'.$edit.'" target="_blank">#'.intval($lid).' ↗︎</a></li>';
        }
        echo '</ul>';
        // inputs hidden para salvamento
        foreach ($lessons as $lid){
            echo '<input type="hidden" name="la_module_lessons[]" value="'.intval($lid).'"/>';
        }
        wp_nonce_field('la_save_module_lessons', 'la_module_lessons_nonce');
        echo '<p class="description">Arraste para ordenar. Use a tela do Curso para adicionar/remover aulas deste módulo.</p>';
    }, 'module', 'normal', 'default');
});

/**
 * -------- Salvamento: bilateral sync --------
 */
add_action('save_post', function($post_id){
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    $pt = get_post_type($post_id);

    // Salvar Curso
    if ($pt === 'course'){
        if ( ! isset($_POST['la_course_rel_nonce']) || ! wp_verify_nonce($_POST['la_course_rel_nonce'], 'la_save_course_rel') ){
            return;
        }

        // 1) Módulos selecionados (ordem)
        $mods = array();
        if (!empty($_POST['la_course_modules_order'])){
            $mods = array_filter(array_map('intval', explode(',', sanitize_text_field($_POST['la_course_modules_order']))));
        }
        update_post_meta($post_id, '_course_modules', $mods);

        // 2) Turmas selecionadas (ordem)
        $turmas = array();
        if (!empty($_POST['la_course_turmas_order'])){
            $turmas = array_filter(array_map('intval', explode(',', sanitize_text_field($_POST['la_course_turmas_order']))));
        }
        update_post_meta($post_id, '_course_turmas', $turmas);

        // 3) Sincroniza relação nos módulos (bilateral)
        $prev_mods = get_posts(array('post_type'=>'module','numberposts'=>-1,'fields'=>'ids','meta_key'=>'_module_course','meta_value'=>$post_id));
        foreach ($prev_mods as $mid){
            if (!in_array($mid, $mods)) delete_post_meta($mid, '_module_course');
        }
        foreach ($mods as $mid){
            update_post_meta($mid, '_module_course', $post_id);
        }

        // 4) Sincroniza relação nas turmas (bilateral)
        $prev_turmas = get_posts(array('post_type'=>'turma','numberposts'=>-1,'fields'=>'ids','meta_key'=>'_turma_course','meta_value'=>$post_id));
        foreach ($prev_turmas as $tid){
            if (!in_array($tid, $turmas)) delete_post_meta($tid, '_turma_course');
        }
        foreach ($turmas as $tid){
            update_post_meta($tid, '_turma_course', $post_id);
        }

        // 5) Aulas por módulo (se vieram no POST trocadas pelo seletor unificado)
        if (!empty($_POST['la_course_module_lessons']) && is_array($_POST['la_course_module_lessons'])){
            foreach ($_POST['la_course_module_lessons'] as $mid => $csv){
                $mid = intval($mid);
                $ids = array_filter(array_map('intval', explode(',', sanitize_text_field($csv))));
                update_post_meta($mid, '_module_lessons', $ids);
                // Set meta nas lessons (_lesson_module e _lesson_course)
                foreach ($ids as $lid){
                    update_post_meta($lid, '_lesson_module', $mid);
                    update_post_meta($lid, '_lesson_course', $post_id);
                }
            }
        }
    }

    // Salvar Módulo: apenas ordem das aulas (a adição/remoção é feita na tela do Curso)
    if ($pt === 'module'){
        if ( isset($_POST['la_module_lessons_nonce']) && wp_verify_nonce($_POST['la_module_lessons_nonce'], 'la_save_module_lessons') ){
            $ids = array();
            if (!empty($_POST['la_module_lessons']) && is_array($_POST['la_module_lessons'])){
                $ids = array_map('intval', $_POST['la_module_lessons']);
            }
            update_post_meta($post_id, '_module_lessons', $ids);
            // também garante metadados nas lessons
            $course = get_post_meta($post_id, '_module_course', true);
            foreach ($ids as $lid){
                update_post_meta($lid, '_lesson_module', $post_id);
                if ($course) update_post_meta($lid, '_lesson_course', intval($course));
            }
        }
    }
}); // end save_post

add_action('add_meta_boxes', function(){
    add_meta_box('la_module_add_lessons', 'Adicionar aulas ao Módulo', function($post){
        if ($post->post_type !== 'module') return;
        echo '<div class="la-mod-add">';
        echo '<div class="la-cols">';
        // Coluna esquerda: atuais
        $list = get_post_meta($post->ID, '_module_lessons', true) ?: array();
        echo '<div class="la-col">';
        echo '<h4>Aulas do módulo (arraste para ordenar)</h4>';
        echo '<ul id="la_mod_current" class="la-selected-lessons-module">';
        foreach((array)$list as $lid){
            $l = get_post($lid); if(!$l) continue;
            $edit = admin_url('post.php?post='.$lid.'&action=edit');
            echo '<li class="la-selected" data-id="'.intval($lid).'">'.esc_html($l->post_title).' <button type="button" class="la-remove">×</button> <a class="la-jump" href="'.$edit.'" target="_blank">#'.$lid.' ↗︎</a></li>';
        }
        echo '</ul>';
        // hidden to persist on save (order/removal)
        echo '<input type="hidden" id="la_mod_hidden" name="la_module_lessons_csv" value="'.esc_attr(implode(',', (array)$list)).'"/>';
        echo '</div>';

        // Coluna direita: disponíveis via AJAX
        echo '<div class="la-col">';
        echo '<h4>Aulas disponíveis</h4>';
        echo '<div class="la-toolbar"><label><input type="checkbox" id="la_mod_select_all"> Selecionar todos</label> <input type="text" id="la_mod_search" placeholder="Buscar..." style="margin-left:10px;min-width:200px;"></div>';
        echo '<ul id="la_mod_available" class="la-list-checkbox"><li><em>carregando…</em></li></ul>';
        echo '<p><button type="button" class="button button-primary" id="la_mod_add_selected" data-module="'.$post->ID.'">Adicionar selecionadas</button></p>';
        echo '</div>';

        echo '</div>'; // cols
        wp_nonce_field('la_module_add_lessons','la_module_add_lessons_nonce');
        echo '</div>';
    }, 'module', 'normal', 'default');
});


add_action('save_post_module', function($post_id){
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (isset($_POST['la_module_lessons_csv'])){
        $ids = array_filter(array_map('intval', explode(',', sanitize_text_field($_POST['la_module_lessons_csv']))));
        update_post_meta($post_id, '_module_lessons', $ids);
        // maintain meta on lessons
        $course = get_post_meta($post_id, '_module_course', true);
        foreach($ids as $lid){
            update_post_meta($lid, '_lesson_module', $post_id);
            if ($course) update_post_meta($lid, '_lesson_course', intval($course));
        }
    }
}, 10, 1);
