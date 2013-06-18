<?

class locale_item extends _sql_base {
  protected $manager = "locale_items_manager"; // NOT PRIVATE
  const sql_table = 'ks_locale_items_list';
  const sql_key = 'item_key';
 
  protected $sql_table = "ks_locale_items_list";
  protected $sql_key = "item_key";
 
  static function instanciate($item_key) {
    return reset(self::from_ids($item_key));
  }

  static function from_ids($ids){
    return self::from_where(array(self::sql_key=>$ids));
  }

  static function from_where($where){
    $res = parent::from_where(__CLASS__, self::sql_table, self::sql_key, $where);
    self::load_tags($res);
    return $res;
  }

  function delete(){
    sql::delete("ks_locale_values", $this);
    return $this->sql_delete();
  }

  static protected function load_tags($items_list) {
    $verif_item = array('item_key' => array_extract($items_list, 'item_key', true));
    //$verif_item []= "item_x IS NOT NULL "; //? (uniquement ceux avec des images)

    sql::select("ks_locale_tag_items", $verif_item);
    $tags_items_list = sql::brute_fetch();

    $tags_list = array_extract($tags_items_list, 'tag_id', true);
    $tags_list = locale_tag::from_where(array('tag_id' => $tags_list ));

    foreach($items_list as $item)
        $item->tags = array();

    foreach($tags_items_list as $tag_item) {
      $item_key = $tag_item['item_key'];
      $tag_id = $tag_item['tag_id'];
      $items_list[$item_key]->tags[$tag_id] = $tags_list[$tag_id];
    }

  }

}
