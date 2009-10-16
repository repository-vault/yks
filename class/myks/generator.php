<?

class myks_gen {
  static $mykse_xml;
  static $type_resolver;
  static public $tables_ghosts_views; // (tables can be ghosts of views)

  static $cols=array('Field','Type','Extra','Null','Default','Extra');

  static function init($mykse_xml){
    if(!classes::init_need(__CLASS__)) return;

    self::$mykse_xml=$mykse_xml;
  }
    //between each type of analyse, the type resolver have to be reset
  static function reset_types(){
     self::$type_resolver = new resolver();
  }

  static function table_check($table_xml){
    try {
        $table=new table($table_xml);
        return $table->check();
    } catch(rbx $r){}
  }
  static function procedure_check($procedure_xml){
    try {
        $procedure=new procedure($procedure_xml);
        return $procedure->check();
    } catch(rbx $r){}
  }

  static function view_check($view_xml, $force= false){
    try {
        $view=new view($view_xml);
        return $view->check($force);
    } catch(rbx $r){}
  }


  static function sql_clean_def($def, $trim=";"){
    //specialchars_decode ne devrait pas être necessaire ( CDATA) ??
    return sql::unfix(trim(self::newline(specialchars_decode( (string)$def) ), $trim).$trim);
  }

  static function newline($str){
    return preg_replace("#\r?\n#","\n",trim($str));
  }
}

class field extends SimpleXMLElement {
  function get_name(){ return (string)($this['name']?$this['name']:$this['type']);}
}

/** Go into all 'dir' subdirectories and create a new link to 'dest' */

function directory_recursive_sublink($dir,$dest){
    $folds=array($dir); $base=basename($dest);
    while($path=array_shift($folds)){
        $folds=array_merge($folds,glob("$path/*",GLOB_ONLYDIR));
        $current=array_keys($folds,$tmp="$path/$base");
        if(is_file($tmp) || is_link($tmp) && !is_dir($tmp)) unlink($tmp);
        if($current) unset($folds[$current[0]]);
        else { symlink($dest,$tmp); }
    }
}

function array_show_diff($a1, $a2, $a1n="obj 1", $a2n="obj 2"){
    $diff = array();
    foreach(array($a1n=>array($a1,$a2), $a2n=>array($a2,$a1)) as $i=>$tmp)
        foreach($tmp[0] as $k=>$v){
            if($tmp[1][$k]!=$v) $diff["From $i :$k"] = $v;
        }
    return $diff;
}



