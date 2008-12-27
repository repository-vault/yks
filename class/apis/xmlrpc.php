<?
//working draft

class rpc {

  static function cmd($command,$params=array()){
	global $host; $ret=$res=array();
	if(is_array($command)){
		foreach($command as $cmd=>$call) {
			$ret[]=$cmd;
			$params[]=array("methodName"=>$call[0],"params"=>array($call[1]));
		} $command="system.multicall";$params=array($params);
	}

	$context =array(
		'http' => array(
			'method' => "POST",
			'header' => "Content-Type: text/xml",
			'content' => xmlrpc_encode_request($command,$params)
		)
	); $context = stream_context_create($context);
	$file = file_get_contents($host, false, $context);
	if(!$file)return array(); $tmp=xmlrpc_decode($file);
	if(!$ret)return $tmp;
	foreach($tmp as $k=>$tmp) $res[$ret[$k]]=$tmp[0]; return $res;
  }
}
?>