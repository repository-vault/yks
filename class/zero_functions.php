<?

function crpt($msg,$flag,$len=40) {
    return substr($flag?sha1($msg.$flag.yks::$get->config->data['hash']):$msg,0,$len);
}

function paths_merge($path_root, $path, $default="."){
    if(!$path) $path = $default;
    if(substr($path,0,1)=="/") return $path;
    return realpath("$path_root/$path");
}