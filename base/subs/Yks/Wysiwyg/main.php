<?php

define('ENCTYPE_FILE',"multipart/form-data");
list($content_type)=preg_split('#[\s;]+#',$_SERVER['CONTENT_TYPE']);

define('FILE',$content_type==ENCTYPE_FILE);

include_once "$class_path/stds/files.php";
include_once "$class_path/users/users.php";
include_once "$class_path/auth/auth.php";


$upload_flag=preg_clean("a-z0-9",$sub0);
$upload_src=$sub1;

$upload_type=preg_clean("a-z0-9_",$sub2);
$upload_def=$config->upload->$upload_type;

if(!FILE && JSX && $upload_flag) die(json_encode(apc_fetch("upload_$upload_flag")));
elseif(!$upload_flag) $upload_flag=crpt(sess::$sess['user_id']._NOW,FLAG_UPLOAD,10);




