<?

class locale_items_manager {

  public static function create($item_key, $value_us, $tags_list = array()){

    $lang_root = 'en-us_1'; // use configuration here !!

    $data=array(
      'item_key'      => $item_key,
    ); sql::insert("ks_locale_items_list", $data);

      // Liste des tags
    foreach($tags_list as $tag_id)
      sql::insert("ks_locale_tag_items",compact('tag_id', 'item_key'));

      // Traduction par défaut (en-us)
    $data = array(
      'item_key'  => $item_key,
      'value'     => $value_us,
      'lang_key'  => $lang_root,
    ); sql::insert("ks_locale_values", $data);

    return locale_item::instanciate($item_key);
  }

  function get_trad_value($locale_item, $lang_root) {
    $where = array (
      'item_key'  => $locale_item->item_key,
      'lang_key'  => $lang_root
    );
    $res = sql::row("ks_locale_values",$where);
    return $res['value'];
  }

  /**
  * TODO §!!
  * Doit récuperer le tag avec une image de l'item. 
  * En fait il peut y avoir plusieurs images... du coup il faudra revoir cette fonction
  * 
  *  (Quentin le 08/02/2012)
  */
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

    if(!is_file($tag->file_path)) {
        $img = imgs::imagecreatetruealpha(1,1);
        header(TYPE_JPEG);
        imagejpeg($img, null, 100);
        die;
    }


    $img_big = imgs::imagecreatefromfile($tag->file_path);
    if(!$img_big) return;

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