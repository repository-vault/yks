<?php

if(!sess::$connected){
    jsx::$rbx = true;
    return rbx::error("Upload denied to anonymous user.");
}

if(!$upload_def){
    jsx::$rbx=true;
    return rbx::error("Unable to load '$upload_type' config, please check config.xml ");
}
if($action=="upload_tmp")try {
  do {
    $file=$_FILES['user_file'];
    if(!is_file($file['tmp_name']))
        break rbx::error("Le fichier est invalide");
    if(($file['size']/1024)>$upload_def['size'])
        break rbx::error("Votre fichier est trop lourd, il ne peut excéder {$upload_def['size']} Ko");
    $file_ext=strtolower(preg_clean("a-z0-9",strrchr($file['name'],'.')));
    if(!strpos(",,{$upload_def['exts']},",",$file_ext,"))
        break rbx::error("Le format de votre fichier n'est pas valide.<br /> Les extensions acceptées sont : {$upload_def['exts']}");


    $dir=files::create_dir(users::get_tmp_path(sess::$sess['user_id']));
    $dest="$upload_type.$upload_flag.$file_ext";
    move_uploaded_file($file['tmp_name'],"$dir/$dest");
    $data=array(
        'src'=>$upload_src,
        'name'=>$file['name'],
        'ext'=>$file_ext,
        'upload_flag'=>$upload_flag,
        'size'=>$file['size'],
    );rbx::$rbx['upload']=$data;
    apc_delete("upload_$upload_flag");
    rbx::ok("Le fichier a correctement été envoyé");
  } while(false);
}catch(Exception $e){ 
  error_log($e);
  rbx::error("Upload failure");
}


if($action == "upload_tmp" && rbx::$rbx)
  die("<script>window.parent.Doms.wake('Uploader').end('$upload_flag',".json_encode(rbx::$rbx).")</script>");



