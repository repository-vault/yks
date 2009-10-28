<?php


if(!function_exists('mime_content_type')) {

    function  mime_content_type($file_path){
        $file_ext = files::ext($file_path);

        $content_types = array(
            'png'=>'image/png',
            'gif'=>'image/gif',
            'txt'=>'text/plain',
            'pdf'=>'application/pdf',
        );

        $fallback = $content_types['txt'];

        $content_type = $content_types[$file_ext];
        return $content_type?$content_type:$fallback;
    }

}




function mime_decode($headers,$body){


    $encoding = $headers['Content-Transfer-Encoding'];
    $charset = $headers['Content-Type-Details']['charset'];

    unset($type_primary);unset($type_extension);
    list($type_primary,$type_extension)=explode('/',$headers['Content-Type'],2);

    $data=array();
    print_r($headers);

    if($type_primary=="multipart"){
        $boundary = $headers['Content-Type-Details']['boundary'];
        $parts = explode('--' . $boundary, $body);

        echo "this is multipart : $type_extension\n";
        foreach($parts as $part) {

            unset($part_headers);unset($part_body);
            list($part_headers,$part_body) = explode(CRLF.pop3::BLANK_LINE,trim($part),2);
            
            if(!$part_body) continue; //skipp pad
            $part_headers = pop3::parse_headers($part_headers);
         
            $data['children'][]= mime_decode($part_headers,$part_body);
        }
    } else {

        if(false) $body=rfc822::decode($body,$encoding);

        if($type_primary == "text") {
            if($charset=='iso-8859-1' && preg_match("#[\x85\x91-\x97\xc9\xd0-\xd5]#",$body) ) $charset = 'cp1252';
            if(EXT_MBSTRING) $body = mb_convert_encoding($body,"UTF-8",$charset);
        }

        $data['size']=strlen($body);

        //$data['body']=$body;

    }

    return $data;

}
