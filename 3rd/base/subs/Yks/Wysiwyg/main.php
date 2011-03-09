<?php

$upload_flag = preg_clean("a-z0-9",$sub0);
if(!$upload_flag)
    return;

define('ENCTYPE_FILE',"multipart/form-data");
list($content_type)=preg_split('#[\s;]+#',$_SERVER['CONTENT_TYPE']);

define('FILE', $content_type==ENCTYPE_FILE);



$upload_src  = $sub1;

$upload_type = preg_clean("a-z0-9_",$sub2);
$upload_def  = $config->upload->search($upload_type);

$request_status = bool($sub3);


if($request_status)
    die(json_encode(apc_fetch("upload_$upload_flag")));




