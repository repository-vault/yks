<?

class urls {
  private static $tlds = false;
  static function parse($url){
    $infos = is_array($url)?$url:parse_url($url);

    $host = $infos['host'];
    if($host) {
        $infos = array_merge($infos, self::resolve_tld($host, self::$tlds));
        if(!$infos['path']) $infos['path'] = '/';
    }
    return $infos;
  }

  static function init(){
    if(!self::$tlds)
        self::$tlds = include("tlds.php");
  }

    //return compact('domain', 'tld', 'sub', 'host')
  private static function resolve_tld($host, $tlds){

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

  public static function paths_merge($path_root, $path){
    if(!$path) return $path_root;
    if(substr($path,0,1)=="/") return files::rp($path);
    $path_root = substr($path_root, 0, strrpos ( $path_root, "/"));
    return files::rp("$path_root/$path");
}

}