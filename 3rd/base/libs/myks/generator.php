<?php

class myks_gen {
  static public $mykse_xml;
  static public $tables_xml;

  static $cols = array('Field', 'Type', 'Extra', 'Null', 'Default', 'Extra');

  static function init($mykse_xml, $tables_xml){
    if(!classes::init_need(__CLASS__)) return;

    self::$mykse_xml  = $mykse_xml;
    self::$tables_xml = $tables_xml;
  }


  static function table_check($table_xml){
    try {
        $table=new table($table_xml);
        return $table->check();
    } catch(rbx $r){}
  }
  static function procedure_check($proc_xml){
    try {
        $infos     = sql::resolve( (string)$proc_xml['name'] );
        $procedure = new procedure($infos, $proc_xml);
        return $procedure->check();
    } catch(rbx $r){}
  }

  static function view_check($view_xml, $force= false){
    try {
        $view = new view($view_xml);
        return $view->check($force);
    } catch(rbx $r){}
  }


  static function sql_clean_def($def, $trim=";"){
    //specialchars_decode ne devrait pas être necessaire ( CDATA) ??
    
    return sql::unfix(trim(self::newline(specialchars_decode( (string)$def) ), $trim).$trim);
  }

  static function newline($str){
    return preg_replace("#\s*\r?\n\s*#","\n",trim($str));
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



