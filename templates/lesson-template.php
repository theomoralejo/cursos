<?php
global $post;
$lesson = get_post($post_id);
$module_id = get_post_meta($lesson->ID,'_lesson_module',true);
$module_id = $module_id ? $module_id : $lesson->post_parent;
$course_id = get_post_meta($module_id,'_module_course',true);
if (!$course_id) $course_id = get_post_meta($lesson->ID,'_lesson_course',true);
$lessons = get_posts(array('post_type'=>'lesson','meta_key'=>'_lesson_module','meta_value'=>$module_id,'orderby'=>'menu_order','order'=>'ASC','posts_per_page'=>-1));
$publish_ts = strtotime($lesson->post_date_gmt);
$deadline_ts = strtotime('+30 days', $publish_ts);
$user = wp_get_current_user();
$access = la_check_access($user->ID, null);
$manual_ok = $access && (!empty($access->manual_extension_until) && strtotime($access->manual_extension_until) > time());
$expired = (time() > $deadline_ts) && ! $manual_ok;
?>
<div class="la-lesson-page">
  <aside class="la-sidebar">
    <div class="la-progress-badge">20% Concluído</div>
    <ul class="la-lesson-list">
      <?php foreach($lessons as $l): ?>
        <li class="<?php echo $l->ID==$lesson->ID ? 'active' : ''; ?>">
          <a href="<?php echo get_permalink($l); ?>"><?php echo esc_html($l->post_title); ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
  </aside>
  <main class="la-main">
    <h1 class="la-lesson-title"><?php echo esc_html($lesson->post_title); ?></h1>
    <?php if ($expired && ! current_user_can('manage_options')): ?>
      <div class="la-locked"><p>Prazo expirado. Entre em contato para liberar acesso.</p></div>
    <?php else: ?>
    <div class="la-player">
      <?php $video = get_post_meta($lesson->ID,'_lesson_video',true); 
        if ($video) echo wp_oembed_get($video);
        else echo '<div class="la-player-placeholder">Player aqui</div>';
      ?>
      <?php if ($video_file = get_post_meta($lesson->ID,'_lesson_video_file',true)): ?>
        <video controls width="100%" id="la-native-player"><source src="<?php echo esc_url(rest_url('la/v1/materials-zip?lesson=' . intval($video_file))); ?>" type="video/mp4"></video>
      <?php endif; ?>
    </div>
    <div class="la-actions">
      <?php $mat = get_post_meta($lesson->ID,'_lesson_material',true); if ($mat): ?>
        <a class="la-btn la-download" href="<?php echo esc_url(rest_url('la/v1/materials-zip?lesson=' . intval($mat))); ?>">Baixar material</a>
      <?php endif; ?>
      <button class="la-btn la-complete" data-lesson="<?php echo $lesson->ID; ?>" data-course="<?php echo intval($course_id); ?>">Marcar como Concluído</button>
    </div>
    <div class="la-comments">
      <?php comments_template(); ?>
    </div>
    <?php endif; ?>
  </main>
</div>
