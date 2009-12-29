<?php
// bases functions, i'm naked without it

function crpt($msg,$flag,$len=40) {
    return substr($flag?sha1($msg.$flag.yks::$get->config->data['hash']):$msg,0,$len);
}

function paths_merge($path_root, $path, $default="."){
    if(!$path) $path = $default;
    if(substr($path,0,1)=="/") return $path;
    return realpath("$path_root/$path");
}

class storage { //on apc
  static function store($k, $v, $ttl=0) { return apc_store($k, $v, $ttl)?$v:false; }
  static function fetch($k)           { return apc_fetch($k); }
  static function delete($k)          { return apc_delete($k); }
}
