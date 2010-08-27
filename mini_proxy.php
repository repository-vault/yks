<?php

//cf doc in the manual
function mini_proxy($src_url,
    $forwarded_headers = array('content-length', 'content-type') ) {

 $src = fopen($src_url, 'r');

 if(!$src) return false;
 $out = fopen('php://output', 'w');
 $metas = stream_get_meta_data($src);

 $headers = $metas['wrapper_data'];

 foreach($headers as $header_str){
    list($header_name, $header_value) = explode(':',$header_str);
    $header_name = strtolower($header_name);
    if(in_array($header_name, $forwarded_headers))
        header($header_str);
 }
 stream_copy_to_stream($src, $out);
 return true;
}

