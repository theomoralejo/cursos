var LA_AJAX=(typeof LA_ADMIN_VARS!=='undefined'&&LA_ADMIN_VARS.ajaxurl)?LA_ADMIN_VARS.ajaxurl:(typeof ajaxurl!=='undefined'?ajaxurl:'');

jQuery(document).ready(function($){

  var ADMIN_URL = (typeof LA_ADMIN_VARS !== 'undefined' && LA_ADMIN_VARS.admin_url) ? LA_ADMIN_VARS.admin_url : (window.ajaxurl ? window.ajaxurl.replace('admin-ajax.php','') : '/wp-admin/');

  function updateCourseModulesOrder(){
      var ids = [];
      $('.la-selected-modules li').each(function(){ ids.push($(this).data('id')); });
      $('#la_course_modules_order').val(ids.join(','));
  }
  function updateCourseTurmasOrder(){
      var ids = [];
      $('.la-selected-turmas li').each(function(){ ids.push($(this).data('id')); });
      $('#la_course_turmas_order').val(ids.join(','));
  }
  function renderRemovers($scope){
      $scope.find('li').each(function(){
          var $li = $(this);
          if (!$li.find('.la-remove').length){
              $li.append('<button type="button" class="la-remove" title="Desvincular">×</button>');
          }
          if (!$li.find('.la-jump').length){
              var id = $li.data('id');
              var link = ADMIN_URL + 'post.php?post='+id+'&action=edit';
              $li.append(' <a class="la-jump" href="'+link+'" target="_blank" title="Abrir #'+id+'">#'+id+' ↗︎</a>');
          }
      }).fail(function(){ jQuery('#la_mod_available').html('<li><em>erro ao carregar</em></li>'); console.error('AJAX falhou: la_get_lessons_by_module'); });
  }

  // Available -> selected (modules)
  $(document).on('click', '.la-available-modules .la-item', function(){
      var $li = $(this), id = $li.data('id');
      if (!$('.la-selected-modules li[data-id="'+id+'"]').length){
          var title = $li.clone().children().remove().end().text().trim();
          $('.la-selected-modules').append('<li class="la-selected" data-id="'+id+'">'+title+'</li>');
          renderRemovers($('.la-selected-modules'));
          updateCourseModulesOrder();
          $li.css('opacity','.4');
      }
  });
  // Available -> selected (turmas)
  $(document).on('click', '.la-available-turmas .la-item', function(){
      var $li = $(this), id = $li.data('id');
      if (!$('.la-selected-turmas li[data-id="'+id+'"]').length){
          var title = $li.clone().children().remove().end().text().trim();
          $('.la-selected-turmas').append('<li class="la-selected" data-id="'+id+'">'+title+'</li>');
          renderRemovers($('.la-selected-turmas'));
          updateCourseTurmasOrder();
          $li.css('opacity','.4');
      }
  });

  // Sortables
  $('.la-selected-modules').sortable({update: updateCourseModulesOrder});
  $('.la-selected-turmas').sortable({update: updateCourseTurmasOrder});
  renderRemovers($('.la-selected-modules'));
  renderRemovers($('.la-selected-turmas'));
  updateCourseModulesOrder();
  updateCourseTurmasOrder();

  // Remove buttons
  $(document).on('click', '.la-selected-modules .la-remove', function(){
      var $li = $(this).closest('li'); var id = $li.data('id'); $li.remove(); updateCourseModulesOrder();
      $('.la-available-modules .la-item[data-id="'+id+'"]').css('opacity','1');
  });
  $(document).on('click', '.la-selected-turmas .la-remove', function(){
      var $li = $(this).closest('li'); var id = $li.data('id'); $li.remove(); updateCourseTurmasOrder();
      $('.la-available-turmas .la-item[data-id="'+id+'"]').css('opacity','1');
  });

  // Module lessons box
  $('.la-selected-lessons').sortable({update: updateModuleLessonsHidden});
  renderRemovers($('.la-selected-lessons'));
  function updateModuleLessonsHidden(){
      var ids = [];
      $('.la-selected-lessons li').each(function(){ ids.push($(this).data('id')); });
      $('.la-selected-lessons input[name="la_module_lessons[]"]').remove();
      for (var i=0;i<ids.length;i++){
          $('<input type="hidden" name="la_module_lessons[]"/>').val(ids[i]).appendTo('.la-selected-lessons');
      }
  }
  updateModuleLessonsHidden();

  $(document).on('change', '#la_add_lesson', function(){
      var val = $(this).val(); if (!val) return;
      var txt = $(this).find('option:selected').text();
      $('.la-selected-lessons').append('<li data-id="'+val+'">'+txt+'</li>');
      renderRemovers($('.la-selected-lessons'));
      $(this).val('');
      updateModuleLessonsHidden();
  });
  $(document).on('click', '.la-selected-lessons .la-remove', function(){
      $(this).closest('li').remove();
      updateModuleLessonsHidden();
  });

  // Course page: manage lessons per module inline
  $(document).on('click', '.la-add-lesson-btn', function(e){
      e.preventDefault();
      var $box = $(this).closest('.la-module-lessons-box');
      var mid = $box.data('module');
      var id = parseInt($box.find('.la-add-lesson-id').val(),10);
      if (!id) return;
      if (!$box.find('ul li[data-id="'+id+'"]').length){
          $box.find('ul').append('<li data-id="'+id+'">Lesson #'+id+' <button type="button" class="la-remove">×</button> <a class="la-jump" target="_blank" href="'+ADMIN_URL+'post.php?post='+id+'&action=edit">#'+id+' ↗︎</a></li>');
          updateCourseModuleLessonsHidden($box);
      }
      $box.find('.la-add-lesson-id').val('');
  });
  $(document).on('click', '.la-module-lessons-box .la-remove', function(){
      var $box = $(this).closest('.la-module-lessons-box');
      $(this).closest('li').remove();
      updateCourseModuleLessonsHidden($box);
  });
  function updateCourseModuleLessonsHidden($box){
      var ids = [];
      $box.find('ul li').each(function(){ ids.push($(this).data('id')); });
      $box.find('input.la-course-module-lessons').val(ids.join(','));
  }

  // Media uploader
  $(document).on('click', '.la-media-upload', function(e){
      e.preventDefault();
      var target = $(this).data('target');
      var frame = wp.media({ title:'Selecionar/Enviar arquivo', button:{text:'Usar este arquivo'}, multiple:false });
      frame.on('select', function(){
          var att = frame.state().get('selection').first().toJSON();
          $(target).val(att.id).trigger('change');
          var link = $(target).siblings('a.la-file-link');
          if (link.length){ link.attr('href', att.url).removeClass('hidden'); }
          else { $(target).after(' <a class="la-file-link" target="_blank" href="'+att.url+'">Ver</a>'); }
      });
      frame.open();
  });

});

// ---- Lessons dual-list inside Course (per module) ----
$(document).on('click', '.la-available-lessons .la-item', function(){
    var $li = $(this);
    var id = $li.data('id');
    var $box = $li.closest('.la-module-lessons-box');
    var $selected = $box.find('.la-selected-lessons-module');
    if (!$selected.find('li[data-id="'+id+'"]').length){
        var text = $li.clone().children('.la-jump').remove().end().text().trim();
        $selected.append('<li class="la-selected" data-id="'+id+'">'+text+' <button type="button" class="la-remove">×</button> <a class="la-jump" target="_blank" href="'+$li.find('.la-jump').attr('href')+'">#'+id+' ↗︎</a></li>');
        $li.remove();
        updateCourseModuleLessonsHidden($box);
    }
});
$(document).on('click', '.la-selected-lessons-module .la-remove', function(){
    var $item = $(this).closest('li'); var id = $item.data('id');
    var $box = $item.closest('.la-module-lessons-box');
    // move back to available
    var href = $item.find('.la-jump').attr('href');
    var text = $item.clone().children('.la-jump,.la-remove').remove().end().text().trim();
    $box.find('.la-available-lessons').append('<li class="la-item" data-id="'+id+'">'+text+' <a class="la-jump" target="_blank" href="'+href+'">#'+id+' ↗︎</a></li>');
    $item.remove();
    updateCourseModuleLessonsHidden($box);
});
$(document).on('keyup', '.la-lesson-search', function(){
    var q = $(this).val().toLowerCase();
    var $ul = $(this).closest('.la-dual-col').find('.la-available-lessons');
    $ul.find('li').each(function(){
        var t = $(this).text().toLowerCase();
        $(this).toggle(t.indexOf(q) !== -1);
    });
});
$('.la-selected-lessons-module').sortable({
    update: function(e, ui){
        var $box = $(this).closest('.la-module-lessons-box');
        updateCourseModuleLessonsHidden($box);
    }
});
function updateCourseModuleLessonsHidden($box){
    var ids = [];
    $box.find('.la-selected-lessons-module li').each(function(){ ids.push($(this).data('id')); });
    $box.find('input.la-course-module-lessons').val(ids.join(','));
}

// Multi material uploader (lesson)
$(document).on('click', '.la-media-upload-multi', function(e){
    e.preventDefault();
    var target = $(this).data('target');
    var $hidden = $(target);
    var current = $hidden.val() ? $hidden.val().split(',').filter(Boolean) : [];
    var frame = wp.media({ title: 'Selecionar arquivos', button:{ text:'Adicionar' }, multiple:true });
    frame.on('select', function(){
        var selection = frame.state().get('selection').toJSON();
        selection.forEach(function(att){
            if (current.indexOf(String(att.id)) === -1){
                current.push(String(att.id));
                // add to UI
                var title = att.filename || att.title || ('Arquivo #' + att.id);
                var link = att.url;
                var li = '<li data-id="'+att.id+'">'+title+' <button type="button" class="la-remove">×</button> <a class="la-jump" target="_blank" href="'+link+'">#'+att.id+' ↗︎</a></li>';
                $('.la-material-list').append(li);
            }
        });
        $hidden.val(current.join(','));
    });
    frame.open();
});

// Remove from materials UI
$(document).on('click', '.la-material-list .la-remove', function(){
    var $li = $(this).closest('li');
    var id = String($li.data('id'));
    var $hidden = $('#_lesson_materials');
    var arr = $hidden.val() ? $hidden.val().split(',').filter(Boolean) : [];
    arr = arr.filter(function(x){ return x !== id; });
    $hidden.val(arr.join(','));
    $li.remove();
});

// --- Cascading Course -> Module -> Lessons (Module screen) ---
function laRenderLessonsList(modId, list){
    var html = '<div class="la-listing"><label><input type="checkbox" class="la-select-all"> Selecionar Todos</label><ul class="la-lessons-check">';
    for (var i=0;i<list.length;i++){
        var it = list[i];
        html += '<li><label><input type="checkbox" name="la_lesson_pick[]" value="'+it.id+'"> '+it.title+' <a class="la-jump" target="_blank" href="'+it.edit+'">#'+it.id+' ↗︎</a></label></li>';
    }
    html += '</ul><p><button type="button" class="button button-primary la-add-picked" data-module="'+modId+'">Adicionar selecionadas</button></p></div>';
    jQuery('#la_lessons_listing').html(html);
}
jQuery(document).on('change', '#la_filter_course', function(){
    var course = jQuery(this).val();
    jQuery('#la_lessons_listing').html('<em>Carregando módulos…</em>');
    jQuery.get(LA_AJAX, {action:'la_get_modules_by_course', course_id:course}, function(resp){
        var $mod = jQuery('#la_filter_module'); $mod.empty().append('<option value="">— Selecionar módulo —</option>');
        if (resp && resp.success){
            resp.data.forEach(function(m){ $mod.append('<option value="'+m.id+'">'+m.title+'</option>'); });
            jQuery('#la_lessons_listing').html('<em>Selecione um módulo.</em>');
        } else {
            jQuery('#la_lessons_listing').html('<em>Nenhum módulo encontrado para este curso.</em>');
        }
    });
});
jQuery(document).on('change', '#la_filter_module', function(){
    var mid = jQuery(this).val();
    if (!mid){ jQuery('#la_lessons_listing').html('<em>Selecione um módulo.</em>'); return; }
    jQuery('#la_lessons_listing').html('<em>Carregando aulas…</em>');
    jQuery.get(LA_AJAX, {action:'la_get_lessons_by_module', module_id:mid}, function(resp){
        if (resp && resp.success){
            laRenderLessonsList(mid, resp.data);
        } else {
            jQuery('#la_lessons_listing').html('<em>Nenhuma aula disponível.</em>');
        }
    });
});
jQuery(document).on('change', '.la-select-all', function(){
    var checked = jQuery(this).prop('checked');
    jQuery('.la-lessons-check input[type="checkbox"]').prop('checked', checked);
});
jQuery(document).on('click', '.la-add-picked', function(){
    var mid = jQuery(this).data('module');
    var ids = [];
    jQuery('.la-lessons-check input[type="checkbox"]:checked').each(function(){ ids.push(jQuery(this).val()); });
    if (!ids.length) { alert('Selecione pelo menos uma aula'); return; }
    var nonce = jQuery('input[name="la_module_add_lessons_nonce"]').val();
    jQuery.post(LA_AJAX, {action:'la_add_lessons_to_module', module_id:mid, lesson_ids:ids, nonce:nonce}, function(resp){
        if (resp && resp.success){
            // reload the list
            jQuery('#la_filter_module').trigger('change');
        } else {
            alert('Erro ao adicionar');
        }
    });
});

// Taxonomy cascade (Course->Module) for filtering lessons listing
jQuery(document).on('change', '#la_tree_course', function(){
    var parent = jQuery(this).val();
    var $child = jQuery('#la_tree_module');
    $child.empty().append('<option value="">— Selecionar —</option>');
    if (!parent) return;
    // Fetch child terms via REST taxonomy endpoints
    jQuery.get(LA_AJAX, { action: 'la_get_child_terms', parent: parent }, function(resp){
        if (resp && resp.success){
            resp.data.forEach(function(t){ $child.append('<option value="'+t.id+'">'+t.name+'</option>'); });
        }
    });
});
jQuery(document).on('change', '#la_tree_module', function(){
    var term = jQuery(this).val();
    // trigger reload of lessons list in cascade UI if present
    var mid = jQuery('#la_filter_module').val();
    if (mid){
        jQuery('#la_lessons_listing').html('<em>Carregando aulas…</em>');
        jQuery.get(LA_AJAX, {action:'la_get_lessons_by_module', module_id:mid, tree_term:term}, function(resp){
            if (resp && resp.success){
                laRenderLessonsList(mid, resp.data);
            } else {
                jQuery('#la_lessons_listing').html('<em>Nenhuma aula disponível.</em>');
            }
        });
    }
});

// ---- Unified manager on Course page ----
function laLoadAvailableLessons(mod, term){
    jQuery('#la_available_lessons').html('<li><em>carregando…</em></li>');
    var data = {action:'la_get_lessons_by_module', module_id:mod};
    if (term){ data.tree_term = term; }
    jQuery.get(LA_AJAX, data, function(resp){
        if (!resp || !resp.success){ jQuery('#la_available_lessons').html('<li><em>Nenhuma aula</em></li>'); return; }
        var html = '';
        resp.data.forEach(function(it){
            html += '<li data-id="'+it.id+'"><label><input type="checkbox" class="la-av-pick" value="'+it.id+'"> '+it.title+' <a class="la-jump" target="_blank" href="'+it.edit+'">#'+it.id+' ↗︎</a></label></li>';
        });
        jQuery('#la_available_lessons').html(html);
    });
}
function laLoadCurrentLessons(mod){
    jQuery('#la_current_lessons').html('<li><em>carregando…</em></li>');
    jQuery.get(LA_AJAX, {action:'la_get_current_lessons_by_module', module_id:mod}, function(resp){
        if (!resp || !resp.success){ jQuery('#la_current_lessons').html(''); return; }
        var html = '';
        resp.data.forEach(function(it){
            html += '<li class="la-selected" data-id="'+it.id+'">'+it.title+' <button type="button" class="la-remove">×</button> <a class="la-jump" target="_blank" href="'+it.edit+'">#'+it.id+' ↗︎</a></li>';
        });
        jQuery('#la_current_lessons').html(html);
        laUpdateHidden();
    });
}
function laUpdateHidden(){
    var ids = [];
    jQuery('#la_current_lessons li').each(function(){ ids.push(jQuery(this).data('id')); });
    jQuery('#la_course_module_lessons_hidden').val(ids.join(','));
}

jQuery(document).on('change', '#la_manage_module', function(){
    var mod = jQuery(this).val();
    jQuery('.la-course-unified-wrap').attr('data-current-module', mod);
    // change hidden field name to match selected module
    jQuery('#la_course_module_lessons_hidden').attr('name', 'la_course_module_lessons['+mod+']');
    laLoadCurrentLessons(mod);
    laLoadAvailableLessons(mod);
});

// select all/ search
jQuery(document).on('change', '#la_av_select_all', function(){
    jQuery('#la_available_lessons .la-av-pick').prop('checked', jQuery(this).prop('checked'));
});
jQuery(document).on('keyup', '#la_av_search', function(){
    var q = jQuery(this).val().toLowerCase();
    jQuery('#la_available_lessons li').each(function(){
        var t = jQuery(this).text().toLowerCase();
        jQuery(this).toggle(t.indexOf(q) !== -1);
    });
});

// add selected via AJAX (merge in module meta)
jQuery(document).on('click', '#la_add_selected_lessons', function(){
    var mod = jQuery('#la_manage_module').val();
    var ids = [];
    jQuery('#la_available_lessons .la-av-pick:checked').each(function(){ ids.push(jQuery(this).val()); });
    if (!mod || !ids.length){ alert('Selecione o módulo e pelo menos uma aula.'); return; }
    var nonce = jQuery('input[name="la_course_rel_nonce"]').val();
    jQuery.post(LA_AJAX, {action:'la_add_lessons_to_module', module_id:mod, lesson_ids:ids, nonce:nonce}, function(resp){
        if (resp && resp.success){
            laLoadCurrentLessons(mod);
            laLoadAvailableLessons(mod);
            jQuery('#la_av_select_all').prop('checked', false);
        } else { alert('Erro ao adicionar.'); }
    });
});

// remove from current list (only UI; save on submit)
jQuery(document).on('click', '#la_current_lessons .la-remove', function(){
    jQuery(this).closest('li').remove();
    laUpdateHidden();
});

// sortable current
jQuery(function(){ jQuery('#la_current_lessons').sortable({update: laUpdateHidden}); });

// On page load, if unified exists, load available list for first module
jQuery(function(){
    if (jQuery('.la-course-unified-wrap').length){
        var mod = jQuery('.la-course-unified-wrap').data('current-module');
        laLoadAvailableLessons(mod);
    }
});


function laInitSortables(){
    if (jQuery('.la-selected-modules').data('uiSortable')){} else { jQuery('.la-selected-modules').sortable({update: updateCourseModulesOrder}); }
    if (jQuery('.la-selected-turmas').data('uiSortable')){} else { jQuery('.la-selected-turmas').sortable({update: updateCourseTurmasOrder}); }
    if (jQuery('#la_current_lessons').length && !jQuery('#la_current_lessons').data('uiSortable')){
        jQuery('#la_current_lessons').sortable({update: function(){ if (typeof laUpdateHidden === 'function') laUpdateHidden(); }});
    }
}
laInitSortables();
// Re-init when metaboxes are toggled or sorted
jQuery(document).on('postbox-toggled sortstop', function(){ laInitSortables(); });

// ------- Module screen: add lessons -------
function laModLoadAvailable(){
    var mod = jQuery('#la_mod_add_selected').data('module');
    jQuery('#la_mod_available').html('<li><em>carregando…</em></li>');
    jQuery.get(LA_AJAX, {action:'la_get_lessons_by_module', module_id:mod}, function(resp){
        if (!resp || !resp.success){ jQuery('#la_mod_available').html('<li><em>Nenhuma aula</em></li>'); return; }
        var html='';
        resp.data.forEach(function(it){
            html += '<li data-id="'+it.id+'"><label><input type="checkbox" class="la-mod-pick" value="'+it.id+'"> '+it.title+' <a class="la-jump" target="_blank" href="'+it.edit+'">#'+it.id+' ↗︎</a></label></li>';
        });
        jQuery('#la_mod_available').html(html);
    });
}
function laModUpdateHidden(){
    var ids=[];
    jQuery('#la_mod_current li').each(function(){ ids.push(jQuery(this).data('id')); });
    jQuery('#la_mod_hidden').val(ids.join(','));
}
jQuery(function(){
    if (jQuery('#la_mod_available').length){ laModLoadAvailable(); }
    if (jQuery('#la_mod_current').length && !jQuery('#la_mod_current').data('uiSortable')){
        jQuery('#la_mod_current').sortable({update: laModUpdateHidden});
    }
});
jQuery(document).on('change', '#la_mod_select_all', function(){
    jQuery('#la_mod_available .la-mod-pick').prop('checked', jQuery(this).prop('checked'));
});
jQuery(document).on('keyup', '#la_mod_search', function(){
    var q=jQuery(this).val().toLowerCase();
    jQuery('#la_mod_available li').each(function(){
        var t=jQuery(this).text().toLowerCase();
        jQuery(this).toggle(t.indexOf(q)!==-1);
    });
});
jQuery(document).on('click', '#la_mod_add_selected', function(){
    var ids=[]; jQuery('#la_mod_available .la-mod-pick:checked').each(function(){ ids.push(jQuery(this).val()); });
    if (!ids.length){ alert('Selecione pelo menos uma aula.'); return; }
    var mod = jQuery(this).data('module');
    var nonce = jQuery('input[name="la_module_add_lessons_nonce"]').val();
    jQuery.post(LA_AJAX, {action:'la_add_lessons_to_module', module_id:mod, lesson_ids:ids, nonce:nonce}, function(resp){
        if (resp && resp.success){
            // reload both lists
            laModLoadAvailable();
            jQuery.get(LA_AJAX, {action:'la_get_current_lessons_by_module', module_id:mod}, function(r2){
                if (r2 && r2.success){
                    var html='';
                    r2.data.forEach(function(it){
                        html += '<li class="la-selected" data-id="'+it.id+'">'+it.title+' <button type="button" class="la-remove">×</button> <a class="la-jump" target="_blank" href="'+it.edit+'">#'+it.id+' ↗︎</a></li>';
                    });
                    jQuery('#la_mod_current').html(html);
                    laModUpdateHidden();
                }
            });
            jQuery('#la_mod_select_all').prop('checked', false);
        } else { alert('Erro ao adicionar.'); }
    });
});
jQuery(document).on('click', '#la_mod_current .la-remove', function(e){
    e.preventDefault(); e.stopPropagation();
    jQuery(this).closest('li').remove(); laModUpdateHidden();
});
