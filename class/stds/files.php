<?
/*	"Exyks files functions" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2007 
*/



class files {
  const CACHE_DELAY     = 3600;
  const FIND_SURFACE    = 1; //non recursive
  const FIND_FOLLOWLINK = 2; //follow sym links
  const FIND_DEFAULT    = 0; //non recursive && no follow sym links

  public static function get_cached($url, $use_include=true, $context=null, $force=false){
    $cached_file = sys_get_temp_dir()."/".md5("cache $url");
    if(!$force && file_exists($cached_file) && (_NOW-filemtime($cached_file)) < self::CACHE_DELAY)
        return file_get_contents($cached_file);
    $cache_contents = ltrim(file_get_contents($url, $use_include, $context), BOM);
    file_put_contents($cached_file, $cache_contents);
    return $cache_contents;
  }

  public static function save_as($file_path, $file_name, $content_type=false){
    if(!$content_type) $content_type = "Content-type:text/".self::ext($file_name).";charset=UTF-8";
    header($content_type);
    $file_mask  = 'Content-disposition:filename="%s"';
    header(sprintf($file_mask, $file_name));
    readfile($file_path);
    die;
  }

  public static function rp($path){
    $out=array();$last=count($from=explode('/', $path))-1;
    foreach($from as $i=>$fold){
        if ($fold==''&& $i!=$last || $fold=='.') continue;
        if ($fold=='..' && $i>0 && end($out)!='..') array_pop($out);
    else $out[]= $fold;
    } return ($path{0}=='/'?'/':'').join('/', $out);
  }

  public static function find($dir, $pattern = '.', $opts = self::FIND_DEFAULT){
    $files=array(); if(!is_dir($dir)) return array();
    foreach(array_slice(glob("$dir/{.?,}*", GLOB_BRACE), 1) as $item){
        $base_file = substr(strrchr($item,'/'), 1);
        if(is_dir($item)
          && !($opts&self::FIND_SURFACE)
          && (is_link($item)?($opts&self::FIND_FOLLOWLINK):true) )
            $files = array_merge($files, self::find($item, $pattern, $opts));
        if(preg_match("#$pattern#", $base_file)) $files[] = $item;
    } return $files;
  }

  public static function create_dir($dir){
    if($dir && !is_dir( $dir=rtrim($dir,'/') ) ) {
        self::create_dir(substr($dir,0,strrpos($dir,'/')));
        $res = mkdir($dir);
        if(!$res) throw new Exception("Unable to create directory $dir");
    }return $dir;
  }

  public static function delete_dir($dir, $rm_root=true, $depth=0){
    if(!is_dir($dir)) return false;
    foreach(array_slice(glob("$dir/{.?,}*", GLOB_BRACE), 1) as $item){
        if(is_link($item) || is_file($item)) unlink($item);
        else if(is_dir($item)) self::delete_dir($item, true, $depth++);
    } if(is_dir($dir) && $rm_root) rmdir($dir);
  }

  public static function copy_dir($dir,$dest){
    self::create_dir($dest);
    foreach(array_slice(glob("$dir/{.?,}*", GLOB_BRACE), 1) as $item){
        $file = strrchr($item, '/');
        if(is_file($item)) copy($item, $dest.$file);
        else if(is_dir($item)) self::copy_dir($item, $dest.$file);
    }
  }

  public static function ext($file){ return substr($file,strrpos($file,".")+1); }

  public static function update($from_file, $to_file, $expire_date){
    if(is_file($to_file) && filemtime($to_file) > $expire_date) return $to_file;
    copy($from_file, $to_file);
    return $from_file;
  }

  public static function locate($file, $paths) {
    foreach($paths as $path)
        if(is_file($tmp = "$path/$file")) return $tmp;
    return false;
  }
}


