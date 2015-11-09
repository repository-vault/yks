<?

  class category extends _sql_base {
    const sql_table = 'ks_shop_categories_list';
    const sql_table_product = 'ks_shop_products_categories';
    const sql_key = 'category_id';
    protected $sql_table = self::sql_table;
    protected $sql_key = self::sql_key;

    protected function __construct($from) {
      return parent::__construct($from);
    }

    static function instanciate($category_id) {
      $category = self::from_where(array(self::sql_key => $category_id));

      return first($category);
    }

    static function from_where($where) {
      return parent::from_where(__CLASS__, self::sql_table, self::sql_key, $where);
    }

    static function create($data) {
      $category_id = sql::insert(self::sql_table, $data, true);

      return $category_id;
    }

    static function search_product_by_category($category_ids) {
      sql::select(self::sql_table_product, array(self::sql_key => $category_ids), products::sql_key);
      return sql::fetch_all();
    }
  }