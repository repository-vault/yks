<?

class locale_items_manager {

  function get_item_tag($locale_item){
    $verif_item = array($locale_item);
    $verif_item []= "item_x IS NOT NULL ";
    $res = sql::row("ks_locale_tag_items", $verif_item);
    if(!$res) throw new Exception("There is no tag for this item");
    $tag = new locale_tag($res['tag_id']);
    $tag->item_infos = $res;
    return $locale_item->item_tag = $tag;
  }

  function get_pict($locale_item, $full = false){

    $tag = $locale_item->item_tag;
    extract($tag->item_infos);

    $img_big = imgs::imagecreatefromfile($tag->file_path);
    $border  = imgs::coloralpha(255,0,0);
    for($a=0;$a<3;$a++) //triple border
      imagerectangle($img_big, $item_x-$a, $item_y-$a, $item_x+$item_w+$a, $item_y+$item_h+$a, $border );

    if($full) {
        header(TYPE_JPEG);
        imagejpeg($img_big,null,100);
        imagedestroy($img_big);
        die;

    } else {
        $max_w = 200; $max_h = 100;
        $img_out = imgs::imagecreatetruealpha($max_w, $max_h);

        $start_x = floor(min($tag['sshot_width']-$max_w,max(0,
            $item_x-(($max_w-$item_w)/2)
        )));
        $start_y=floor(min($tag['sshot_height']-$max_h,max(0,
            $item_y-(($max_h-$item_h)/2)
        ))); 


        imagecopy ( $img_out,$img_big, 0,0, $start_x, $start_y,$max_w,$max_h );
        header(TYPE_JPEG);
        imagejpeg($img_out,null,100);
        imagedestroy($img_out);
        imagedestroy($img_big);
        die;
    }

  }




}