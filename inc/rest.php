<?php
if ( ! defined('ABSPATH') ) exit;
add_action('rest_api_init', function(){
    register_rest_route('la/v1', '/complete-lesson', array('methods' => 'POST','callback' => 'la_rest_complete_lesson','permission_callback' => function(){ return is_user_logged_in(); }));
    register_rest_route('la/v1', '/extend-access', array('methods' => 'POST','callback' => 'la_rest_extend_access','permission_callback' => function(){ return current_user_can('manage_options'); }));
    register_rest_route('la/v1', '/video-token', array('methods' => 'GET','callback' => 'la_rest_video_token','permission_callback' => function(){ return is_user_logged_in(); }));
    register_rest_route( 'la/v1', '/material', array( 'methods' => 'GET', 'callback' => 'la_rest_material', 'permission_callback' => '__return_true' ) );
    register_rest_route( 'la/v1', '/materials-zip', array( 'methods' => 'GET', 'callback' => 'la_rest_materials_zip', 'permission_callback' => '__return_true' ) );
});
function la_rest_complete_lesson($request){
    $user = wp_get_current_user();
    $lesson_id = absint($request->get_param('lesson_id'));
    if ( ! $lesson_id ) return new WP_Error('invalid','lesson_id required', array('status'=>400));
    global $wpdb;
    $table = $wpdb->prefix . 'course_progress';
    $exists = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE user_id=%d AND lesson_id=%d", $user->ID, $lesson_id));
    if ($exists) return rest_ensure_response(array('status'=>'already'));
    $wpdb->insert($table, array('user_id'=>$user->ID,'lesson_id'=>$lesson_id,'course_id'=>intval($request->get_param('course_id')) ?: null,'completed_at'=>current_time('mysql',1)));
    return rest_ensure_response(array('status'=>'ok'));
}
function la_rest_extend_access($request){
    $user_id = intval($request->get_param('user_id'));
    $product_id = intval($request->get_param('product_id')) ?: null;
    $days = intval($request->get_param('days')) ?: 30;
    la_extend_access($user_id, $product_id, $days);
    return rest_ensure_response(array('status'=>'ok'));
}
function la_rest_video_token($request){
    $user = wp_get_current_user();
    $lesson_id = absint($request->get_param('lesson_id'));
    if (! $lesson_id) return new WP_Error('invalid','lesson_id required', array('status'=>400));
    $lesson = get_post($lesson_id);
    if (!$lesson) return new WP_Error('notfound','Lesson not found', array('status'=>404));
    $publish_ts = strtotime($lesson->post_date_gmt);
    $deadline_ts = strtotime('+30 days', $publish_ts);
    $access = la_check_access($user->ID, null);
    $manual_ok = $access && (!empty($access->manual_extension_until) && strtotime($access->manual_extension_until) > time());
    if (time() > $deadline_ts && ! $manual_ok) {
        return new WP_Error('expired','Acesso expirado. Entre em contato.', array('status'=>403));
    }
    if (! la_check_access($user->ID, null) && ! current_user_can('manage_options')) {
        return new WP_Error('noaccess','Sem acesso', array('status'=>403));
    }
    $token = la_generate_token($user->ID, $lesson_id, 3600);
    return rest_ensure_response(array('token'=>$token, 'ttl'=>3600));
}

function la_rest_material($request){
    $token = $request->get_param('token');
    $att = intval($request->get_param('att')) ?: 0;

    // helper to stream/redirect attachment
    $serve_attachment = function($attachment_id){
        $file = get_attached_file($attachment_id);
        $mime = get_post_mime_type($attachment_id) ?: 'application/octet-stream';
        if ($file && file_exists($file) && is_readable($file)){
            nocache_headers();
            header('Content-Type: ' . $mime);
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        } else {
            // Fallback: redirect to the public URL (handles S3/offload and resized paths)
            $url = wp_get_attachment_url($attachment_id);
            if ($url){
                nocache_headers();
                wp_redirect($url, 302);
                exit;
            }
            return new WP_Error('nofile','Arquivo não encontrado', array('status'=>404));
        }
    };

    if ($token){
        $info = la_validate_token($token);
        if (! $info) return new WP_Error('invalid','Token inválido', array('status'=>403));
        $lesson_id = $info['lesson_id'];
        $material_id = get_post_meta($lesson_id, '_lesson_material', true);
        if (! $material_id) return new WP_Error('nomaterial','Material não definido', array('status'=>404));
        return $serve_attachment(intval($material_id));
    } elseif ($att) {
        $user = wp_get_current_user();
        if (! $user->ID) return new WP_Error('noauth','Login required', array('status'=>401));
        if (! la_check_access($user->ID, null) && ! current_user_can('manage_options')) {
            return new WP_Error('noaccess','Sem acesso', array('status'=>403));
        }
        return $serve_attachment(intval($att));
    } else {
        return new WP_Error('bad','token or att required', array('status'=>400));
    }
}



function la_rest_materials_zip($request){
    $lesson_id = intval($request->get_param('lesson'));
    $token = $request->get_param('token');
    $user = wp_get_current_user();

    if ($token){
        $info = la_validate_token($token);
        if (! $info) return new WP_Error('invalid','Token inválido', array('status'=>403));
        $lesson_id = intval($info['lesson_id']);
    } else {
        if (! $lesson_id ) return new WP_Error('invalid','lesson required', array('status'=>400));
        if (! $user->ID ) return new WP_Error('noauth','Login required', array('status'=>401));
        if (! la_check_access($user->ID, null) && ! current_user_can('manage_options')) {
            return new WP_Error('noaccess','Sem acesso', array('status'=>403));
        }
    }

    // Get materials (array or fallback single)
    $materials = get_post_meta($lesson_id, '_lesson_materials', true);
    if (!is_array($materials) || empty($materials)) {
        $single = get_post_meta($lesson_id, '_lesson_material', true);
        if ($single) $materials = array(intval($single));
    }
    if (empty($materials)) return new WP_Error('nomaterial','Nenhum material definido', array('status'=>404));

    // Prepare temp zip
    $upload_dir = wp_upload_dir();
    $tmp = trailingslashit($upload_dir['basedir']) . 'la_mat_' . $lesson_id . '_' . time() . '.zip';

    if (!class_exists('ZipArchive')) {
        return new WP_Error('nozip','ZipArchive não disponível no servidor', array('status'=>500));
    }

    include_once ABSPATH . 'wp-admin/includes/file.php'; // download_url
    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE) !== TRUE) {
        return new WP_Error('zipfail','Falha ao criar zip', array('status'=>500));
    }

    foreach((array)$materials as $att_id){
        $att_id = intval($att_id);
        if (! $att_id) continue;
        $file = get_attached_file($att_id);
        $name = get_the_title($att_id);
        if (!$name) $name = 'arquivo-'.$att_id;
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if ($ext) $name .= '.' . $ext;

        if ($file && file_exists($file) && is_readable($file)){
            $zip->addFile($file, $name);
        } else {
            // try remote download
            $url = wp_get_attachment_url($att_id);
            if ($url){
                $tmpfile = download_url($url, 60);
                if (!is_wp_error($tmpfile) && file_exists($tmpfile)){
                    $zip->addFile($tmpfile, $name);
                }
            }
        }
    }

    $zip->close();

    if (!file_exists($tmp)){
        return new WP_Error('zipnotfound','Não foi possível gerar o zip', array('status'=>500));
    }

    // Serve zip
    nocache_headers();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="materiais-lesson-'.$lesson_id.'.zip"');
    header('Content-Length: ' . filesize($tmp));
    readfile($tmp);
    @unlink($tmp);
    exit;
}
