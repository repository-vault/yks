<?

/** Create a new image, respecting alpha channels */
function imagecreatetruealpha($w,$h){
	$img=imagecreatetruecolor($w,$h);
	imagealphablending($img,false);
	imagesavealpha($img,true);
	imagefill($img,0,0,coloralpha(255,255,255,127));
	return $img;
}
/** Create a new image from a file, regarless its extension */
function imagecreatefromfile($file){
	$img_supported_ext=array('png'=>'png','jpg'=>'jpeg','gif'=>'gif','jpeg'=>'jpeg');
	if(!$ext=$img_supported_ext[trim(strtolower(strrchr($file,'.')),'.')]) return false;
	$func="imagecreatefrom$ext";
	if(!($img=@$func($file))) return false;
	imagealphablending($img,false);
	imagesavealpha($img,true);
	return $img;
}


function imagetrunk($from,$x,$y,$w,$h){
	$img=imagecreatetruealpha($w,$h);
	imagecopy($img,$from,0,0,$x,$y,$w,$h);
	return $img;
}

/** Resize an image based on width and optional height */
function imageresize($img,$w,$h=false,$bigger=false){
	$old_w=imagesx($img);$old_h=imagesy($img);
	$ratio=min($w?$w/$old_w:$h/$old_h,$h?$h/$old_h:$w/$old_w);
	if($ratio>1 && !$bigger) $ratio=1;
	$new_w=$ratio*$old_w;$new_h=$ratio*$old_h;
	$out=imagecreatetruealpha($new_w,$new_h);
	imagecopyresampled($out,$img,0,0,0,0,$new_w,$new_h,$old_w,$old_h);
	return $out;
}

	//useless
function imagecolorhide($img,$color){
	$pic_w=imagesx($img);$pic_h=imagesy($img);
	$trans=coloralpha(255,255,255,127);
	for($x=0;$x<$pic_w;$x++){
		for($y=0;$y<$pic_h;$y++){
			$to=imagecolorat($img,$x,$y);
			if($to==$color || $to==-1) imagesetpixel($img,$x,$y,$trans);
		}
	}
}


/** Merge two images, respecting alpha channels */
function imagefusion($dest,$mask,$decx=0,$decy=0){
	$pic_w=imagesx($mask);$pic_h=imagesy($mask);
	for($x=0;$x<$pic_w;$x++){
		for($y=0;$y<$pic_h;$y++){
			$to=colordec(imagecolorat($mask,$x,$y));
			if($to['alpha']=='127')continue;
			$from=colordec(imagecolorat($dest,$x+$decx,$y+$decy));
			$to=colorget( colorfusion($from,$to));
			imagesetpixel($dest,$x+$decx,$y+$decy,$to);
		}
	}
}

/** duplicate an image, return an empty one of the same size */
function imageempty($img){
	$pic_w=imagesx($img);$pic_h=imagesy($img);
	$tmp=imagecreatetruealpha($pic_w,$pic_h);
	return $tmp;
}

/** duplicate an image/ have to check if alpha is well supported */
function imageduplicate($img){
	$pic_w=imagesx($img);$pic_h=imagesy($img);
	$tmp=imagecreatetruealpha($pic_w,$pic_h);
	imagecopyresampled($tmp,$img,0,0,0,0,$pic_w,$pic_h,$pic_w,$pic_h);
	return $tmp;
}


/** correct use of imagettftext supporting alpha chan.*/
function imagetext($img,$font_size,$angle,$x,$y,$color,$font,$str){
	$img_text=imageempty($img);
	imagettftext($img_text, $font_size, $angle, $x, $y, $color, $font, $str);
	imagefusion($img,$img_text);
	imagedestroy($img_text);
}

	//useless
function imagerotation(&$img,$angle){
	$img= imagerotate($img, $angle, imagecolortransparent($img));
	imagealphablending($img,false);
	imagesavealpha($img,true);
}



/** function for w000ting purposes **/
function image_bg_scale($img_back,$bx_img,$bx_x,$bx_y){
	$img_w=imagesx($img_back); $img_h=imagesy($img_back);
	$bx_w=imagesx($bx_img);$bx_h=imagesy($bx_img);

	list($bx_xl,$bx_xm,$bx_xr)=$bx_x; list($bx_yu,$bx_ym,$bx_yd)=$bx_y;

	//filling BG
	$map=array();
	if($bx_xl&&$bx_yu) $map[]=array(0,0,0,0,$bx_xl,$bx_yu,$bx_xl,$bx_yu);
	if($bx_xl&&$bx_yd) $map[]=array(0,$img_h-$bx_yd,0,$bx_h-$bx_yd,$bx_xl,$bx_yd,$bx_xl,$bx_yd);

	if($bx_xm && $bx_ym) for($x=$bx_xl;$x<$img_w-$bx_xr;$x+=$bx_xm)
		for($y=$bx_yu;$y<$img_h-$bx_yd;$y+=$bx_ym)
		  $map[]=array($x,$y,$bx_xl,$bx_yu,$bx_xm,$bx_ym,$bx_xm,$bx_ym);

	if($bx_xm) for($x=$bx_xl;$x<$img_w-$bx_xr;$x+=$bx_xm){
	  if($bx_yu) $map[]=array($x,0,$bx_xl,0,$bx_xm,$bx_yu,$bx_xm,$bx_yu);
	  if($bx_yd) $map[]=array($x,$img_h-$bx_yd,$bx_xl,$bx_h-$bx_yd,$bx_xm,$bx_yd,$bx_xm,$bx_yd);
	}

	if($bx_ym)for($y=$bx_yu;$y<$img_h-$bx_yd;$y+=$bx_ym){
	  if($bx_xl) $map[]=array(0,$y,0,$bx_yu,$bx_xl,$bx_ym,$bx_xl,$bx_ym);
	  if($bx_xr) $map[]=array($img_w-$bx_xr,$y,$bx_w-$bx_xr,$bx_yu,$bx_xr,$bx_ym,$bx_xr,$bx_ym);
	}

	//__FATALITY
	if($bx_xr&&$bx_yu) $map[]=array($img_w-$bx_xr,0,$bx_w-$bx_xr,0,$bx_xr,$bx_yu,$bx_xr,$bx_yu);
	if($bx_xr&&$bx_yd) $map[]=array($img_w-$bx_xr,$img_h-$bx_yd,$bx_w-$bx_xr,
		$bx_h-$bx_yd,$bx_xr,$bx_yd,$bx_xr,$bx_yd); // __FINISH HIM \o/

	foreach($map as $i)
		imagecopyresampled($img_back,$bx_img,$i[0],$i[1],$i[2],$i[3],$i[4],$i[5],$i[6],$i[7]);
}