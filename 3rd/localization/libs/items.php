<?

class locale_item extends _sql_base {
  protected $manager = "locale_items_manager"; // NOT PRIVATE
  const sql_table = 'ks_locale_items_list';
  const sql_key = 'item_key';
 
  protected $sql_table = "ks_locale_items_list";
  protected $sql_key = "item_key";
 

  function __construct($from){
    parent::__construct($from);
  }

  static function from_where($where){
    return parent::from_where(__CLASS__, self::sql_table, self::sql_key, $where);
  }

  function delete(){
    sql::delete("ks_locale_values", $this);
    return $this->sql_delete();
  }


}
