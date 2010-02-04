<?php

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


  public static function merge($in_files, $out_file){
    $out = fopen($out_file, "w");
    foreach($in_files as $k=>$file) {
        if(!is_file($file))
            throw new Exception("file::merge failed as part #$k($file) is not a valid file");

        $contents = file_get_contents($file);
        fwrite($out, $contents, strlen($contents));
    }
    fclose($out);
    return filesize($out);
  }

  public static function create_dir($dir){

    if($dir && !is_dir( $dir) ) {
        $old = umask(0);$res = mkdir($dir, octdec("777") ,true); umask($old); 
        if(!$res) throw new Exception("Unable to create directory $dir");
    } return $dir;
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

  public static function download($file, $filename = false, $mime_type = false ){

    if($mime_type)
        header($mime_type===true //auto-detection
            ? "Content-Type: text/".self::ext($filename).";charset=UTF-8"
            : "Content-Type: ".end(explode(':', $mime_type,2))); //last part

    $filename = $filename ? $filename : basename($file);

    $mask     = 'Content-Disposition: attachment; filename="%s"';
    $filename = utf8_decode($filename);
    // $filename = rfc_2047::header_encode($filename); ie crap
    header(sprintf($mask, $filename));

    readfile($file);
    die;

  }

  public static function paths_merge($path_root, $path){
    if(!$path) return $path_root;
    if(substr($path,0,1)=="/") return files::rp($path);
    $path_root = substr($path_root, 0, strrpos ( $path_root, "/"));
    return files::rp("$path_root/$path");
  }


    //stat() equivalent on distant (and resolved) or local paths
    //return false on error
  public static function dstat($path){
    $parsed_url = parse_url($path);
    $is_local_path  = !isset($parsed_url['host']);
    if($is_local_path)
        return stat($path);

    $http_sucess   = array(200);
    $http_redirect = array(302, 301);
    while(true) {
        $res = http::head($path);
        //redirect here

        if(in_array($res['code'], $http_sucess)) break;
        elseif(in_array($res['code'], $http_redirect)) {
            $path = die("hERE");
            continue;
        }
        return false;   
    }

    $size  = $res['headers']['Content-Length']->value;
    $mtime = $res['headers']['Last-Modified']->value;
    $mtime = $mtime?strtotime($mtime):0;

    $data = compact('size', 'mtime');

    return $data;
  }
  public static function tmpdir(){mkdir($tmp = self::tmppath()); return $tmp; }
  public static function tmppath($ext= 'tmp') {return tempnam(sys_get_temp_dir(), "$ext-").".$ext"; }


  public static function extract($archive_file, $extract_path = "."){
    if(!extension_loaded("zip")) dl("zip.so");
    $zip = new ZipArchive();
    $zip->open($archive_file);
    $zip->extractTo($extract_path);
    $zip->close();
  }


    //creer une archive et en retourne le path
  public static function archive($files_list, $options =  array() ){

    if(!extension_loaded("zip")) dl("zip.so");

    $zip = new ZipArchive();


    $tmp_file = self::tmppath("zip");
    $zip->open($tmp_file, ZIPARCHIVE::CREATE);

    $base_dir   = $options['base_dir'];
    $extra_path = $options['extra_path'];
    foreach($files_list as $file_path) {
        if($base_dir) $file_path = files::paths_merge("$base_dir/", $file_path);
        $file_archive_path = basename($file_path);
        if($extra_path) $file_archive_path = $extra_path."/".$file_archive_path;

        $file_archive_path = charset_map::Utf8StringDecode($file_archive_path, charset_map::$_toUtfMap);
        $zip->addFile($file_path, $file_archive_path);

    }

    $zip->close();

    //self::file_edit_bit($tmp_file, true, 4+2, 11);

    return $tmp_file;
  }

  public static function file_edit_bit($file, $value, $byte_start = 0, $bit = 0){
    $str = file_get_contents($file);
    $str = self::edit_bit($str, $value, $byte_start, $bit);
    file_put_contents($file, $str);
  }
    //edite le bit (pour 1 ou 0)
  public static function edit_bit($str, $value, $byte_start =0, $bit=0) {
    $bytes_before = $byte_start + floor($bit/8); $bit = $bit%8; //8-
    $mask = 1<<$bit;

    $before = substr($str, 0, $bytes_before);
    $after  = substr($str, $bytes_before + 1);

    $chr = ord(substr($str, $bytes_before, 1));
    if(!$value) $chr &= (255 - $mask);
    else $chr |= $mask;

    $chr = chr($chr);
    return $before.$chr.$after;

  }

}

