<?php

class urls {
  private static $tlds = false;
  static function parse($url){
    $infos = is_array($url)?$url:parse_url($url);

    $host = $infos['host'];
    if($host) {
        $infos = array_merge($infos, self::parse_host($host));
        if(!$infos['path']) $infos['path'] = '/';
    }
    return $infos;
  }

  private static function tlds(){
    if(!self::$tlds)
        self::$tlds = tlds::get_list();
    return self::$tlds;
  }

    //return compact('domain', 'tld', 'sub', 'host')
  static function parse_host($host){
    $tlds = self::tlds();
    $parts = explode('.', $host);
    $stack = false; $tld_level = 1; //unknown tld are 1st level
    foreach(array_reverse($parts) as $part) {
        $stack = $stack?"$part.$stack":$part;
        if(!isset($tlds[$stack])) break;
        $tld_level = $tlds[$stack];
    }

    if(count($parts)<=$tld_level)
        throw new Exception("Invalid tld");

    $tld     = join('.', array_slice($parts, -$tld_level));
    $domain  = join('.', array_slice($parts, (-$tld_level-1)));
    $sub     = join('.', array_slice($parts, 0, (-$tld_level-1)));

    return compact('domain', 'tld', 'sub', 'host');
  }

  public static function merge($url1, $url2){
    $url = new url($url1);
    return $url->merge(new url($url2));
  }


}