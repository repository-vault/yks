<?php


$backtrace_errors=array();

function error_out($str=''){
	global $backtrace_errors;
	$tmp="DEBUG STOP\n";
	if($backtrace_errors) foreach($backtrace_errors as $error){
		$tmp.="<table class='error' cellspacing='0'>";
		$tmp.="<tr><th colspan='4' class='title'>".truncate($error['code'],1024)."</th></tr>";
		$tmp.="<tr><th class='id'>#</th><th class='call'>Call</th><th class='call'>Args</th><th class='file'>File</th></tr>";
		foreach($error['stack'] as $step){
			$bg=(int)!$bg;
			$tmp.="<tr class='error_line_$bg'>
				<td class='center'>{$step['depth']}</td>
				<td>{$step['function']}</td>
				<td>{$step['args']}</td>
				<td>{$step['file']}</td>
				</tr>";
		}

		if(class_exists('sql')){
			$tmp.="<tr><th colspan='4'>SQL status</th></tr>";
			foreach(sql::$queries as $k=>$query)
				$tmp.="<tr class='error_line_".($k%2)."'><td colspan='4'>".truncate($query,512)."</td></tr>";
		}

		$tmp.="</table>";
	} else header(TYPE_TEXT);
	return $backtrace_errors?DEBUG_STYLE.$tmp."<pre>$str</pre>":$tmp.$str;
}

/** return the list of variables used in source */
function scope_get_vars($contents){
	$vars_mask='#\$([a-z0-9_]+)#i';
	preg_match_all($vars_mask,$contents,$out);
	return $out[1]?$out[1]:array();
}
/** return the source in a specific scope */
function file_get_scope($file,$start,$end=0){
	$contents=file_get_contents($file);
	$height=$end-$start;
	$mask="#(?:.*?\n){{$start}}((?:.*?\n){{$height}})#is";
	preg_match($mask,$contents,$out);return trim($out[1]);
}

function error_handler($err_no,$err_str,$err_file,$err_line,$context){
	static $done_errors=array();
	global $backtrace_errors,$error_types;
	
	$hash=md5("$err_no,$err_str,$err_file,$err_line");
	if(isset($done_errors[$hash]))return;
	$done_errors[$hash]=true;

	if($err_no == E_NOTICE || $err_no==E_STRICT) return;
	$res=debug_backtrace();unset($res[0]);

	$scope=file_get_scope($err_file,$err_line-3,$err_line+1);
	$vars_name=scope_get_vars($scope); $vars=array();
	foreach($vars_name as $var_name)
		$vars[$var_name]=isset($context[$var_name])
			?substr(print_r($context[$var_name],1),0,200)
			:'undefined';

	
	$blabla="<pre>".trim(highlight_string('<?'."\n".trim($scope,'<?>'."\n")."\n".'?>',1)).'</pre>'.print_r($vars,1);

	$lvl=set_export($error_types,$err_no);
	$code="<b>$lvl : $err_str</b> in $err_file:$err_line";
	$stack=array();$depth=0;
	
	foreach($res as $step){
		$args=array();
		if($step['args'])
			foreach($step['args'] as $arg)$args[]=htmlspecialchars(substr(print_r($arg,1),0,200));

		$function=$step['function'];
		if(function_exists($function))
			$function=function_describe(new ReflectionFunction($function));

		$stack[]=array(
			'depth'=>++$depth,
			'file'=> input_format("{$step['file']}:{$step['line']}"),
			'function'=> $function,
			'args'=> input_format(join(', ',$args)),
		);
	}


	$backtrace_errors[]=array(
		'code'=>input_format($code).$blabla,
		'stack'=>$stack
	);
}

set_error_handler('error_handler');

function function_describe($func){
	$tmp='';
	$name=$func->getName();
	if($func->isInternal())return $name;
	if($doc=$func->getDocComment())$tmp.=trim($doc,'/*');
	if($func->returnsReference())$tmp.="&";
	$tmp.=$name;

	$params=$func->getParameters();$p=array();
	foreach($params as $param){
		$pmp='$'.$param->getName();
		if($param->isPassedByReference())$pmp="&$pmp";
		if($param->isOptional())$pmp="$pmp = ".var_export($param->getDefaultValue(),1);
		$p[]=$pmp;
	}$tmp.='('.join(', ',$p).' )';

	return $tmp;

}

$error_types = array (
                E_ERROR              => ' Error ',
                E_WARNING            => ' Warning',
                E_PARSE              => ' Parse error ',
                E_NOTICE             => ' Note ',
                E_CORE_ERROR         => ' Core Error ',
                E_CORE_WARNING       => ' Core Warning ',
                E_COMPILE_ERROR      => ' Compile Error ',
                E_COMPILE_WARNING    => ' Compile Warning ',
                E_USER_ERROR         => ' Erreur spécifique ',
                E_USER_WARNING       => ' Alerte utilisateur',
                E_USER_NOTICE        => ' Note spécifique ',
                E_STRICT             => ' Runtime Notice ',
                E_RECOVERABLE_ERRROR => ' Catchable Fatal Error '
);



define("START_FOLD",dirname($_SERVER['SCRIPT_FILENAME']));
define("KSPLORER","http://admin.cloudyks.org/ksplorer/?edit");
define("POPUP_PARAMS","width=840,height=560,resizable=yes,menubar=no,statusbar=no");

define("DEBUG_STYLE","<style>
.error { width :90%;margin:5px auto 15px auto;border:1px solid #7F7F7F; font-family:Arial; font-size:11px; }
.error th,.error tfoot{ background-color:#FF9900; border-bottom:1px solid #ffd089; font-variant: small-caps; font-size:12px; font-family:Trebuchet MS; }
.error .title { font-weight:bold; text-align:left; background:#ffb974 url(/css/buttons/16_exclamation.gif) no-repeat 4px center; padding: 2px 0px 2px 24px; font-variant:normal; }
.time { width:60px; }
.file { width:200px;}
.center { text-align:center; }

span[onclick] {color:blue; }
.error_line_0 { background-color:#f7f7f7; }
.error_line_1 { background-color:#e1e1e1; }

</style>");

/** given a BIT indexed array and an INT, return the corresponding value*/
function set_export($array,$val){
	foreach(array_keys($array) as $k)if(($k&$val)!=$val)unset($array[$k]);
	return join(',',$array);
}


function input_format($str){
	$str=strip_tags($str);
	$path="[\w\d\./_-]+";$file="[\w\d\._-]+";
	if(!preg_match("#($path)/($file)#is",$str,$out))return $str;
	list($replace,$fold,$file)=$out;$fold_link=$fold{0}=="/"?$fold:($fold==".."?START_FOLD:START_FOLD."/$fold");
	$dest="<span style='font-weight:bold' onclick=\"window.open('"
		.KSPLORER."&fold=$fold_link&file=$file','_blank','".POPUP_PARAMS."'"
		.")\">$fold/$file</span>";

	$str=str_replace($replace,$dest,$str);

	return $str;

}