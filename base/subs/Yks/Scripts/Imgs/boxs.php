<?

include_once "$class_path/stds/files.php";

$out_path="css/".SITE_BASE."/boxs";

foreach($themes_config->themes->children() as $theme_name=>$theme_data) try {
	$theme_src="$out_path/$theme_name.png";
	if(!is_file($theme_src)) throw rbx::error("$theme_name is incomplete for $theme_src");
	$theme_path="$out_path/$theme_name"; files::create_dir($theme_path);
	$base_img=imagecreatefromfile($theme_src);

	$box_w=imagesx($base_img);$box_h=imagesy($base_img);
	$tmp=explode(";",$theme_data['grid']);
	list($box_xl,$box_xm,$box_xr)=explode(',',$tmp[0]);
	list($box_yu,$box_ym,$box_yd)=explode(',',$tmp[1]);


	$todo=array(
		'lu'=>array(0,0,$box_xl,$box_yu),
		'ld'=>array(0,$box_h-$box_yd,$box_xl,$box_yd),
		'lm'=>array(0,$box_yu,$box_xr,$box_ym),

		'ru'=>array($box_w-$box_xr,0,$box_xr,$box_yu),
		'rm'=>array($box_w-$box_xr,$box_yu,$box_xr,$box_ym),
		'rd'=>array($box_w-$box_xr,$box_h-$box_yd,$box_xr,$box_yd),

		'mu'=>array($box_xl,0,$box_xm,$box_yu),
		'md'=>array($box_xl,$box_h-$box_yd,$box_xm,$box_yd),
		'mm'=>array($box_xl,$box_yu,$box_xm,$box_ym),
	);

	$theme_css="";
	foreach($todo as $key=>$data){
		$theme_css.=".{$theme_name}_$key {background-image:url($key);}\n";
		$tmp=imagetrunk($base_img,$data[0],$data[1],$data[2],$data[3]);
		$file="$theme_path/$key.png";
		imagepng($tmp,$file);
		imagedestroy($tmp);
	} 
	$theme_css.=".{$theme_name}_lm {width:{$box_xl}px;} .{$theme_name}_rm {width:{$box_xr}px;}\n";
	$theme_css.=".{$theme_name}_mu {height:{$box_yu}px;} .{$theme_name}_md {height:{$box_yd}px;}\n ";

	$css_file="$theme_path/box.css"; touch($css_file);
	$css_contents_old=file_get_contents($css_file);
	$css_contents_update="/** $theme_name **/\n$theme_css\n/** -- **/\n";

	$css_contents=$css_contents_update
		.preg_replace("#/\*\* $theme_name \*\*/(.*?)/\*\* -- \*\*/\n#s",'',$css_contents_old);
	file_put_contents($css_file,$css_contents);
	rbx::ok("Génération $theme_name terminée");

}catch(rbx $e){}






die("Themes done");