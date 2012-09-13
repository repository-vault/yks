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
    $cache_contents = ltrim(file_get_contents($url, $use_include, $context), UTF8_BOM);
    file_put_contents($cached_file, $cache_contents);
    return $cache_contents;
  }

  public static function rp($path, $ds = "/"){
    $out=array();$last=count($from=explode($ds, $path))-1;
    foreach($from as $i=>$fold){
        if ($fold==''&& $i!=$last || $fold=='.') continue;
        if ($fold=='..' && $i>0 && end($out)!='..') array_pop($out);
        else $out[]= $fold;
    } return ($path{0}==$ds?$ds:'').join($ds, $out);
  }

  public static function compress($file_path, $file_out = false){
    if(!$file_out) $file_out = "$file_path.gz";
    $contents = file_get_contents($file_path);

    $bz = gzopen($file_out, "w9"); gzwrite($bz, $contents); gzclose($bz);
    return $file_out;
  }


  public static function localize($file_path, $entities, $file_dest = false) {
    if(!$file_dest) $file_dest = $file_path;
    $str = file_get_contents($file_path); $tmp = null;
    while($tmp!=$str){ $tmp=$str; $str=strtr($str,$entities);}
    $str=$tmp;
    file_put_contents($file_dest, $str);
    return true;
  }


  public static function find($dir, $pattern = '#.#', $opts = self::FIND_DEFAULT){
    $files=array(); if(!is_dir($dir)) return array();
    foreach(array_slice(glob("$dir/{.?,}*", GLOB_BRACE), 1) as $item){
        $base_file = substr(strrchr($item,'/'), 1);
        if(is_dir($item)
          && !($opts&self::FIND_SURFACE)
          && (is_link($item)?($opts&self::FIND_FOLLOWLINK):true) )
            $files = array_merge($files, self::find($item, $pattern, $opts));
        if(preg_match($pattern, $base_file)) $files[] = $item;
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

  public static function encrypt($file_path, $key, $file_out = false){
    if(!$file_out) $file_out = $file_path;
    $contents = file_get_contents($file_path);
    $str      = crypt::encrypt($contents, $key);
    file_put_contents($file_out, $str);        
  }

  public static function decrypt($file_path, $key, $file_out = false){
    if(!$file_out) $file_out = $file_path;
    $contents = file_get_contents($file_path);
    $str      = crypt::decrypt($contents, $key);
    file_put_contents($file_out, $str);        
  }

  public static function empty_dir($dir, $recursive = true) {
    if($recursive)
        self::delete_dir($dir, false, $depth);
    else 
        foreach(glob("$dir/*") as $file_path)
            if(!is_dir($file_path)) unlink($file_path);

    self::create_dir($dir);
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

  public static function ext($file){ return strtolower(substr($file,strrpos($file,".")+1)); }

  public static function update($from_file, $to_file, $expire_date){
    if(is_file($to_file) && filemtime($to_file) > $expire_date) return $to_file;
    copy($from_file, $to_file);
    return $from_file;
  }


    //fix windows DST issue
  function touch($file_path, $mtime){
    touch($file_path, $mtime); clearstatcache();
    $mtime_touched = filemtime($file_path); //clearstatcache();
    if($mtime != $mtime_touched) {
      $diff = ($mtime - $mtime_touched);
      touch($file_path, $mtime + $diff); //clearstatcache();
    }
  }

  public static function locate($file, $paths) {
    foreach($paths as $path)
        if(is_file($tmp = "$path/$file")) return $tmp;
    return false;
  }

//he'll live forever
  public static function highlander(){
    header("Cache-control: public");
    header("Expires: Thu, 12 Apr 2036 19:31:20 GMT");
    header(HTTP_CACHED_FILE);
  }

  public static function delivers($file, $send_content_type = false){
    self::highlander();
    if($send_content_type) {
        $content_type = self::content_type($file);
        header("Content-Type: $content_type");
    }

    readfile($file);
    die;
  }


  private static function download_forge_headers($filename, $mime_type, $metas = array()){
        //http://support.microsoft.com/kb/812935
    header_remove("Set-Cookie");  header_remove("Pragma"); header_remove("Cache-Control");
    
    //header("Accept-Ranges: bytes"); //no need
    //header("Content-Transfer-Encoding: binary"); //no need    
    
    if($mime_type)
        header($mime_type===true //auto-detection
            ? "Content-Type: text/".self::ext($filename).";charset=UTF-8"
            : "Content-Type: ".end(explode(':', $mime_type,2))); //last part

    $filename = $filename ? $filename : basename($file);

    if($metas['filesize']) 
      header(sprintf("Content-Length:%d", $metas['filesize'] )); //force !chunked
            
    $mask     = 'Content-Disposition: attachment; filename="%s"';
    $filename = utf8_decode($filename);
    // $filename = rfc_-2047::header_encode($filename); ie crap
    header(sprintf($mask, $filename));
  }
  
  public static function download_str($contents, $filename, $mime_type = false ){
    while(@ob_end_clean());
    $metas = array('filesize' => strlen($contents));
    self::download_forge_headers($filename, $mime_type, $metas);
    echo $contents;
    die;
    
  }
  public static function download($file_path, $filename = false, $mime_type = false ){
    while(@ob_end_clean());
    $metas = array('filesize' => filesize($file_path));
    self::download_forge_headers($filename ? $filename : basename($file_path), $mime_type, $metas);
    readfile($file_path);
    die;
  }

  public static function content_type($file_path){
    $ext = self::ext($file_path);
    return mime_types::get_content_type($ext);
  }

  public static function paths_merge($path_root, $path){
    if(!$path) return $path_root;
    $root = '#^/|^[A-Z]\:#';
    if(preg_match($root , $path)) return files::rp($path);
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
  public static function tmppath($ext= 'tmp') {
      $abc = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
      $rand = substr(str_shuffle($abc.$abc.$abc), 0,8);
      $file_path = rtrim(sys_get_temp_dir(),DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR."$ext-$rand.$ext";
      if(file_exists($file_path))
        return self::tmppath($ext);
      return $file_path;
  }


  public static function csv_split($str, $sep = ';'){
    $str = str_replace('""', "&quot;", $str); //inline quote

    $mask = "#(?:\"([^\"]*)\"|'([^']*)'|([^$sep]*))(?:$sep|$)#";
    if(!preg_match_all($mask, $str, $out, PREG_SET_ORDER))
    return array();
    array_pop($out); //drop last empty

    $line = array();
    foreach($out as $cel)
      $line[] = pick(str_replace("&quot;", '"', $cel[1]), $cel[2], $cel[3]);
    return $line;
  }


  public static function csv_parse_file($file_path, $has_headers = true){
    $str   = file_get_contents($file_path);
    return files::csv_parse_string($str, $has_headers);
  }

  public static function csv_parse_string($contents, $has_headers = true){
    
    $lines = preg_split("#\r?\n#", rtrim($contents));
    $data = array_map(array(__CLASS__, 'csv_split'), $lines);
    $cols = max(array_map('count', $data));

    if(!$has_headers)
        return $data;

    $headers = array_shift($data);
    for($a=0 ; $a<$cols ; $a++)
      if(!isset($headers[$a]) || $headers[$a] == "")
        $headers[$a] = "col_{$a}";

    foreach($data as &$line)
      $line = array_combine($headers, array_pad($line, $cols, ""));

    return $data;
  }


  public static function csv_parse($file_path,  $has_headers = true) {
    $handle   = fopen($file_path, "r");
    $data     = array();
    $maxlines = 0;

    while (($line = fgetcsv($handle, 0, ";")) !== FALSE) {
        $data[] = $line;
        $maxlines = max($maxlines, count($line));
    }
    if(!$has_headers)
        return $data;

    $headers = array_shift($data);
    for($a=0;$a<$maxlines;$a++)
        if(!isset($headers[$a])||false) $headers[$a] = "col_{$a}";

    foreach($data as &$line)
      $line = array_combine($headers, array_pad ($line, $maxlines, ""));

    return $data;
  }

  public static function extract($archive_file, $extract_path = "."){
    $zip = new ZipArchive();
    $zip->open($archive_file);
    $zip->extractTo($extract_path);
    $zip->close();
  }


    //creer une archive et en retourne le path
  public static function archive($files_list, $options =  array() ){

    $base_dir   = $options['base_dir'];
    $extra_path = $options['extra_path'];
    
    $zip = new ZipArchive();

    $dest_path = pick($options['dest_path'], self::tmppath("zip"));
    $zip->open($dest_path, ZIPARCHIVE::CREATE);

    foreach($files_list as $file_path) {
        if($base_dir) $file_path = files::paths_merge("$base_dir/", $file_path);
        $file_archive_path = basename($file_path);
        if($extra_path) $file_archive_path = $extra_path."/".$file_archive_path;

        $file_archive_path = txt::utf8_to_cp950($file_archive_path);
        $zip->addFile($file_path, $file_archive_path);
    }

    $zip->close();


    return $dest_path;
  }

  public static function search_bytes($file_handle, $needle, $offset = false, $buffer_size = 2048) {
    if(strlen($needle) >= $buffer_size)
      throw new Exception('Needle length must be inferior to buffer_size size.');
    if($offset !==false) fseek($file_handle, $offset);
    while($line = fread($file_handle, $buffer_size)){
      $needle_pos = strpos($line, $needle);
      if($needle_pos !== FALSE)
        return ftell($file_handle) + $needle_pos - strlen($line);
      if(feof($file_handle))
        return false;
      fseek($file_handle, - strlen($needle), SEEK_CUR);
    }
    return false;
  }

  public static function read_bytes($file_handle, $bytes, $offset = false){
      if($offset !==false) fseek($file_handle, $offset);
      $body = ""; $buffer = 8192;
      do {
        $left  = min(8192, $bytes - strlen($body));
        $body .= fread($file_handle, $left);
      } while(strlen($body) < $bytes && !feof($file_handle));
      return $body;
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

  public static function extract_phar($phar_path, $out_dir = null){
    if(!$out_dir)
      $out_dir = strip_end(basename($phar_path), files::ext($phar_path));

    $tmp_file = self::tmppath('phar');
    copy($phar_path, $tmp_file);
    $p = new Phar($tmp_file);
    $root_path = str_replace("\\", '/', "phar://".realpath($tmp_file).'/');
    files::empty_dir($out_dir);
    foreach($p as $data) {
       $rel_path  = strip_start($data, $root_path);
       $dest_path = $out_dir."/".$rel_path;
       files::create_dir(dirname($dest_path));
       copy($data, $dest_path);
    }
    $p = null;
    unlink($tmp_file);
  }

}

