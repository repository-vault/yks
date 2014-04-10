<?php

if(!$upload_def) {
    $msg = "Invalid upload flag";
    rbx::error($msg);

    if(JSX) jsx::end();
    die($msg);
}

$valid_exts = preg_split( VAL_SPLITTER, $upload_def['exts'],  -1,  PREG_SPLIT_NO_EMPTY);

if(!sess::$connected && !yks::$get->config->is_debug()){
    jsx::$rbx = true;
    return rbx::error("Upload denied to anonymous user.");
}

if(!$upload_def){
    jsx::$rbx = true;
    return rbx::error("Unable to load '$upload_type' config, please check config.xml ");
}

$max_size = dsp::file_size($upload_def['size']*1024);

if($action=="upload_tmp")try {

    $file       = $_FILES['user_file'];
    $file_ext   = files::ext($file['name']);

    if($file['error'])
      throw rbx::error("Erreur interne du serveur durant le chargement du fichier (#{$file['error']})");

    if( !is_file($file['tmp_name']) )
        throw rbx::error("Le fichier est invalide");
    if( ($file['size']/1024) > $upload_def['size'] )
        throw rbx::error("File is too large, it should not exceed $max_size");
    if(!in_array($file_ext, $valid_exts) && !in_array('*',$valid_exts)) // Si on a pas l'ext et si on a pas autorisé le whilecard
        throw rbx::error("Le format de votre fichier n'est pas valide.<br />".
            "Les extensions acceptées sont : ".join(', ', $valid_exts));

    $dest = exyks_paths::resolve("path://tmp/$upload_type.$upload_flag.$file_ext");

     if($file['post_data_reading'])
        rename($file['tmp_name'], $dest);
      else
        move_uploaded_file($file['tmp_name'], $dest);

    if(!is_file($dest))
      throw rbx::error("Unknown transfert error, sorry");

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




