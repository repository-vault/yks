<?

function crpt($msg,$flag,$len=40) {
    return substr($flag?sha1($msg.$flag.yks::$get->config->data['hash']):$msg,0,$len);
}
