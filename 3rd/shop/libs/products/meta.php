<? 
 
class products_meta extends _sql_base {
  const sql_table = 'ks_shop_meta_products_list';
  const sql_key   = 'meta_product_id';
  protected $sql_table = self::sql_table;
  protected $sql_key   = self::sql_key;


  protected function __construct($from){
    return parent::__construct($from);
  }
  
  static function instanciate($meta_product_id) {
    $meta_products = self::from_where(array(self::sql_key => $meta_product_id));
    return reset($meta_products);
  } 
  
  static function from_where($where){
    return parent::from_where(__CLASS__, self::sql_table, self::sql_key, $where);
  }
  
  static function create($data) {
    $meta_product_id = sql::insert(self::sql_table,$data, true);
    return $meta_product_id;
  }
  
}