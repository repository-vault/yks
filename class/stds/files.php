<?
/*	"Exyks files functions" by Leurent F. (131)
    distributed under the terms of GNU General Public License - © 2007 
*/


define('FIND_SURFACE',1);    //non recursive
define('FIND_FOLLOWLINK',2); //follow sym links
define('FIND_DEFAULT',0);    //non recursive && no follow sym links

define("FILE_CACHE_PATH",isset($_ENV['TMP'])?$_ENV['TMP']:"/tmp");
define("CACHE_DELAY",3600);



function load_constants_ini($file) { 
  $data = parse_ini_file ($file);
  foreach($data as $key=>$value){
    if(is_numeric($value)) $value = (int)$value;
    $key =  strtoupper(strtr($key,array('.'=>'_')));
    define($key, $value);
  } 
}

function locate_file($file, $paths) {
    foreach($paths as $path)
        if(is_file($tmp = "$path/$file")) return $tmp;
    return false;
}


/*
    Check if a file the user upload is fine to be used
    Usage $file_infos = upload_check( upload_type, $_POST['myfile'] )
    returns compact('tmp_file', 'file_ext');
*/
function upload_check($upload_type, $upload_file){
    $tmp_path=users::get_tmp_path(sess::$sess['user_id']); //tmp upload dir
    $tmp_file = "$upload_type.$upload_file";

    if(!preg_match(FILE_MASK,$tmp_file)|| !is_file($tmp_file="$tmp_path/$tmp_file") )
        return false;

    $file_ext=file_ext($tmp_file);
    return compact('tmp_file','file_ext');
}

function rp($path) {
    $out=array();$last=count($from=explode('/', $path))-1;
    foreach($from as $i=>$fold){
        if ($fold==''&& $i!=$last || $fold=='.') continue;
        if ($fold=='..' && $i>0 && end($out)!='..') array_pop($out);
    else $out[]= $fold;
    } return ($path{0}=='/'?'/':'').join('/', $out);
}

function file_get_cached($url,$use_include=true,$context=null,$force=false){
    $hash=md5("cache $url");
    $cached_file=FILE_CACHE_PATH."/$hash";
    if(!$force && file_exists($cached_file) && (_NOW-filemtime($cached_file)) <CACHE_DELAY)
        return file_get_contents($cached_file);
    $cache_contents=ltrim(file_get_contents($url,$use_include,$context),BOM);
    file_put_contents($cached_file,$cache_contents);
    return $cache_contents;
}

function find_file($dir,$pattern='.',$opts=FIND_DEFAULT){
    $files=array(); if(!is_dir($dir)) return array();
    foreach(array_slice(glob("$dir/{.?,}*",GLOB_BRACE),1) as $item){
        $base_file=substr(strrchr($item,'/'),1);
        if(is_dir($item) && !($opts&FIND_SURFACE) && (is_link($item)?($opts&FIND_FOLLOWLINK):true) )
            $files=array_merge($files, find_file($item,$pattern,$opts));
        if(preg_match("#$pattern#",$base_file))$files[]=$item;
    }return $files;	
}

function create_dir($dir){
    if($dir && !is_dir( $dir=rtrim($dir,'/') ) ) {
        create_dir(substr($dir,0,strrpos($dir,'/')));
        $res = mkdir($dir);
        if(!$res) throw new Exception("Unable to create directory");
    }return $dir;
}

function delete_dir($dir,$rm_root=true,$depth=0){
    if(!is_dir($dir)) return false;
    foreach(array_slice(glob("$dir/{.?,}*",GLOB_BRACE),1) as $item){
        if(is_link($item) || is_file($item)) unlink($item);
        else if(is_dir($item)) delete_dir($item,true,$depth++);
    } if(is_dir($dir) && $rm_root) rmdir($dir);
}

function copy_dir($dir,$dest){
    create_dir($dest);
    foreach(array_slice(glob("$dir/{.?,}*",GLOB_BRACE),1) as $item){
        $file=strrchr($item,'/');
        if(is_file($item)) copy($item,$dest.$file);
        else if(is_dir($item)) copy_dir($item,$dest.$file);
    }
}

function file_ext($file){ return substr($file,strrpos($file,".")+1); }

function file_update($from_file,$to_file,$expire_date){
    if(is_file($to_file) && filemtime($to_file)>$expire_date) return $to_file;
    copy($from_file,$to_file); return $from_file;
}
