<?

class locale_item extends _sql_base {
  protected $manager = "locale_items_manager"; // NOT PRIVATE
  const sql_table = 'ks_locale_items_list';
  const sql_key = 'item_key';
 
  protected $sql_table = "ks_locale_items_list";
  protected $sql_key = "item_key";
 
  static function instanciate($item_key) {
    $locale_items = self::from_where(array(self::sql_key=>$item_key));
    return $locale_items[$item_key];
  }

  static function from_where($where){
    $res = parent::from_where(__CLASS__, self::sql_table, self::sql_key, $where);
    $res = self::get_items_tags($res);
    return $res;
  }

  function delete(){
    sql::delete("ks_locale_values", $this);
    return $this->sql_delete();
  }

  static function get_items_tags($locale_items_list) {
    $verif_item = array('item_key' => array_extract($locale_items_list, 'item_key'));
    //$verif_item []= "item_x IS NOT NULL "; //? (uniquement ceux avec des images)

    sql::select("ks_locale_tag_items", $verif_item);
    $tags_items_list = sql::brute_fetch();
    $tags_list = locale_tag::from_where(array('tag_id' =>array_extract($tags_items_list, 'tag_id')));

    foreach($tags_items_list as $tag_item) {
      $item_key = $tag_item['item_key'];
      $tag_id = $tag_item['tag_id'];
      if(!$locale_items_list[$item_key]->tags)
        $locale_items_list[$item_key]->tags = array();
      $locale_items_list[$item_key]->tags[$tag_id] = $tags_list[$tag_id];
    }

    return $locale_items_list;
  }

}
