<?php

class locale_tag_manager {

  public static function create($data, $sshot_file = false){

    if(!$data['tag_name'])
        throw rbx::error("Veuillez donner un nom à votre tag");

    $transaction_token = sql::begin();

    try {
        $data['sshot_width']  = 0;
        $data['sshot_height'] = 0;
        $tag_id = sql::insert(locale_tag::sql_table, $data, true);
        $tag    = new locale_tag($tag_id);
        if($sshot_file) $tag->attach_file($sshot_file);
    } catch( Exception $e) {
      sql::rollback($transaction_token);
      throw rbx::error("Unable to create tag");
    }

    sql::commit($transaction_token);
    return $tag;

  }

  public static function create_item($data){
    if(sql::value('ks_locale_items_list', $data ))
      return;
    return sql::insert("ks_locale_items_list", $data);
  }


    //return void
  public static function attach_file($tag, $sshot_file){

    $file_infos = users::upload_check(locale_tag::upload_type, $sshot_file);
    if(!$file_infos)
        throw rbx::error("Le fichier semble être corrompu, veuillez le re-transferer");

    $file_path =  $file_infos['tmp_file'];

    ini_set("memory_limit", "30M");

    $img = imgs::imagecreatefromfile($file_path);

    $big   = imgs::imageresize($img, 800, 600);
    $small = imgs::imageresize($img, 200);

    $data = array(
        'sshot_width'  => imagesx($big),
        'sshot_height' => imagesy($big),
    ); $tag->update($data);

    if(!is_writable(dirname($tag->file_path)))
      throw rbx::error("Le dossier \"big\" n'existe pas ou n'est pas accessible en écriture");
    if(!is_writable(dirname($tag->tn_path)))
      throw rbx::error("Le dossier \"tn\" n'existe pas ou n'est pas accessible en écriture");

    imagejpeg($big,  $tag->file_path, 90); imagedestroy($big);
    imagejpeg($small, $tag->tn_path, 90); imagedestroy($small);
  }

  public static function trash_files($tag){
    //Les tags ne sont pas systématiquement dotés de screenshots
    if(is_writable($tag->file_path))
      unlink($tag->file_path);
    if(is_writable($tag->file_path))
      unlink($tag->file_path);
  }

  public static function get_file_path($tag, $big=true){
    $base = $big ? locale_tag::$sshot_big_path : locale_tag::$sshot_tn_path;
    return "$base/{$tag->tag_id}.jpg";
  }

  public static function get_tn_path($tag){
    return $tag->get_file_path(false);
  }

  public static function add_item($tag, $data, $create = false){

    $item_key  = $data['item_key'];
    if(!$item_key) return false;
    $data['tag_id'] = $tag->tag_id;
    $tag->trash_item($item_key);

    $item_data = array(
      'item_key'=>$item_key,
    );
    if(!$create && !sql::value('ks_locale_items_list', $item_data ))
      throw rbx::error("No item $item_key in items list");

    if($create)
      self::create_item($item_data);

    $res = sql::insert("ks_locale_tag_items", $data);
    return $res;

  }

  public static function trash_item($tag, $item_key){
    $verif_delete = array(
        'item_key'=>$item_key,
        'tag_id'=>$tag->tag_id,
    ); return sql::delete("ks_locale_tag_items", $verif_delete);
  }

  public static function get_paths_str($tag){
    $paths = $tag->paths;
    if(!$paths) return '';
    return mask_join(' &gt; ', $paths, '&locale_tag.%s;');
  }

}
