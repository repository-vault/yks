<?php

$valid_exts = preg_split( VAL_SPLITTER, $upload_def['exts'],  -1,  PREG_SPLIT_NO_EMPTY);

if(!sess::$connected && !DEBUG){
    jsx::$rbx = true;
    return rbx::error("Upload denied to anonymous user.");
}

if(!$upload_def){
    jsx::$rbx = true;
    return rbx::error("Unable to load '$upload_type' config, please check config.xml ");
}


if($action=="upload_tmp")try {

    $file       = $_FILES['user_file'];
    $file_ext   = files::ext($file['name']);

    if( !is_file($file['tmp_name']) )
        throw rbx::error("Le fichier est invalide");
    if( ($file['size']/1024) > $upload_def['size'] )
        throw rbx::error("Votre fichier est trop lourd, il ne peut excéder {$upload_def['size']} Ko");
    if(!in_array($file_ext, $valid_exts) && !in_array('*',$valid_exts)) // Si on a pas l'ext et si on a pas autorisé le whilecard
        throw rbx::error("Le format de votre fichier n'est pas valide.<br />".
            "Les extensions acceptées sont : ".join(', ', $valid_exts));

    $dir = users::get_tmp_path(sess::$sess['user_id'], true);
    move_uploaded_file($file['tmp_name'], "$dir/$upload_type.$upload_flag.$file_ext");

    rbx::$rbx['upload'] = array(
        'src'  => $upload_src,
        'name' => $file['name'],
        'ext'  => $file_ext,
        'size' => $file['size'],
    );

    storage::delete("upload_$upload_flag");
    rbx::ok("Le fichier «{$file['name']}» a été attaché");
} catch(rbx $e) {
} catch(Exception $e){ 
  error_log($e);
  rbx::error("Upload failure");
}


if($action == "upload_tmp" && rbx::$rbx &&!JSX)
  die("<script>with(window.parent){Uploader.end_upload_static('$upload_flag',"
        .json_encode(rbx::$rbx).", Screen.getBox('upload_file').anchor.getElement('form'))}</script>");




