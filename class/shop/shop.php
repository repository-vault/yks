<?
class shop {
  static $shop_infos;

  static function init($infos){

    if(!$infos['currency_format'])
        $infos['currency_format'] = '%s';

    self::$shop_infos = $infos;
  }

  static function currency_format($value){
    return sprintf(self::$shop_infos['currency_format'], $value);
  }
}

class categories {
  static function get_products($categories_list){
    $categories_list = (array) $categories_list;

   $verif_shop = array( "category_id" => $categories_list);
   sql::select("ks_shop_products_categories",$verif_shop, "product_id");
   $products_list = sql::brute_fetch(false,"product_id"); 


    return products::get_children($products_list);
  }

}

