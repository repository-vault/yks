<?php



header(TYPE_JPEG);

$sshot_id=(int)$sub0;
$w=(int)$sub1;
$h=(int)$sub2;

$verif_sshot=compact('sshot_id');
sql::select("trad_sshot_items",$verif_sshot);
$items_list=sql::brute_fetch('item_key');


$sshot_file="$sshots_bigs_path/$sshot_id.jpg";

if(!is_file($sshot_file)) return rbx::error("Impossible de trouver l'image");

$img = imgs::imagecreatefromfile($sshot_file);

$border = imgs::coloralpha(255,0,0);

foreach($items_list as $item_key=>$item_data){
    extract($item_data);

    for($a=0;$a<3;$a++)
      imagerectangle($img,$box_x-$a,$box_y-$a,$box_x+$box_w+$a,$box_y+$box_h+$a,$border );
}

imagejpeg(imgs::imageresize($img,$w,$h));

die;