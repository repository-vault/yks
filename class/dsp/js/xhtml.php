<?

define('DTD_XHTML',RSRCS_DIR."/dtds/xhtml1-strict.dtd");

function errors_format($errors){
	$str="";foreach($errors as $error)
		$str.="Error on {$error->line}:{$error->column} <b>".trim($error->message)."</b>. ";
	return $str;
}

function xhtml_check_syntax($str,&$err=null,$doctype="XHTML"){

	$doc=new DOMDocument('1.0','utf-8');
	libxml_use_internal_errors(true);$old_error=error_reporting(0);
	$str=XML_VERSION."<!DOCTYPE html SYSTEM '".DTD_XHTML."'>\n"
		."<html xmlns='".XHTML."'><head><title>Test</title></head>
		<body><div>$str</div></body>
	</html>";
	
	if($doctype=="HTML") $doc->loadHTML($str);
	else $doc->loadXML($str,LIBXML_NOWARNING|LIBXML_DTDLOAD|LIBXML_DTDATTR);

	$ret=$doc->validate(); 
	$errors=libxml_get_errors();$err=errors_format($errors);
	libxml_use_internal_errors(false);error_reporting($old_error);
	return $ret;
}

function js_check_syntax($content,$err){
  global $tmp_path;
  $tmp_file=$tmp_path."/".md5(uniqid(rand(), true));
  file_put_contents($tmp_file,$content);

  $cmd = "java -jar ".YUI_COMPRESSOR_PATH." --charset UTF-8  --type js $tmp_file 2>&1";
  exec($cmd, $out, $err);unlink($tmp_file);

  if($err) {
	$err=join("\n",$out);preg_match("#\s*(.*?)\n\n#",$err,$out);
	$err=$out[1]?$out[1]:"Unexpected syntax";
	return false;
  } return true;

}
