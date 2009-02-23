<?

class myks_gen {
  static $mykse_xml;
  static $cols=array('Field','Type','Extra','Null','Default','Extra');

  static function init($mykse_xml){
	self::$mykse_xml=$mykse_xml;
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

  static function view_check($view_xml){
	try {
		$view=new view($view_xml);
		return $view->check();
	} catch(rbx $r){}
  }


  static function sql_clean_def($def, $trim=";"){
    //specialchars_decode ne devrait pas être necessaire ( CDATA) ??
    return trim(self::newline(specialchars_decode( (string)$def) ), $trim).$trim;
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


