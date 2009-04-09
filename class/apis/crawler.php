<?

$svn_url="http://svn.mootools.net/trunk/Source/";

include "$class_path/stds/files.php";

$host="http://svn.mootools.net";
$base_path="/trunk/Source/";

$dir_toscan=array(
	$base_path=>1	
); $file_generated=array();


$dest_path="test_trunk";


while($toscan=array_keys($dir_toscan,1)){
	foreach($toscan as $fold){
		$str=file_get_cached("{$host}{$fold}");
		preg_match_all("#<a href=(['\"])(.*?)\\1#",$str,$out);

		foreach((array)$out[2] as $href){
			$data=parse_url($href);$path=$data['path'];
			if($data['host'] && $data['host']!=$host) continue;

			preg_match('#^((/?).*?/?)([^/]*)?$#',$href,$path);
			$_fold= rp( ($path[2]=='/')?$path[1]:$fold.$path[1] ); $file=$path[3];

			if(substr($_fold,0,strlen($base_path))!=$base_path) continue;

			if(!isset($dir_toscan[$_fold])) $dir_toscan[$_fold]= 1;

			if($file){
				$local_path=$dest_path.$_fold; $_file=$_fold.$file;
				$local_file=$local_path.$file;
				if(!$file_generated[$_fold]){
					$file_generated[$_fold]=true;
					create_dir($local_path);
				}

				if(!$file_generated[$_file]){
					$file_generated[$_file]=true;
					if(!is_file($local_file)) copy($host.$_file,$local_file);
				}
			}
		} $dir_toscan[$fold]=0;
	}
}


