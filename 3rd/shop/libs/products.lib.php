<?

include "taxes_desactivate.lib.php";

class products extends _sql_base {

  const sql_table = 'ks_shop_products_list';
  const sql_key   = 'product_id';
  protected $sql_table = self::sql_table;
  protected $sql_key   = self::sql_key;

  static function instanciate($product_id) {
    $products = self::from_ids(array($product_id), false);
    return first($products);
  }

  static function from_ids($ids, $related_products=true) {
    $where = array (self::sql_key => $ids);
    $products =  self::from_where($where, $related_products);
    return array_sort_deep($products, 'product_id');
  }

  static function from_where($where, $related_products=true){

    $products  =  parent::from_where(__CLASS__, self::sql_table, self::sql_key, $where);
    if(!$products)
        return array();

    $head_products_list = array_keys($products);

    $full_list = self::get_product_tree(array_keys($products));
    $missing   = array_diff($full_list, array_keys($products) );
    if($missing)
      $products  =  parent::from_where(__CLASS__, self::sql_table, self::sql_key, array(self::sql_key => $full_list));

    $verif =  array('product_id' => array_extract($products, 'product_id'));
    sql::select('ivs_shop_products_owners', $verif);
    $owners_list = sql::brute_fetch();
    foreach($owners_list as $owner) {
      $product = $products[$owner['product_id']];
      if(!$product->owners_list)
        $product->owners_list = array();
      $product->owners_list[$owner['user_id']] = $owner;
    }

    sql::select('ivs_shop_meta_products_products', $verif);
    $meta_products_list = sql::brute_fetch();
    foreach($meta_products_list as $meta_product) {
      $product = $products[$meta_product['product_id']];
      if(!$product->meta_products_list)
        $product->meta_products_list = array();
      $product->meta_products_list[$meta_product['meta_product_id']] = $meta_product;
    }

    sql::select("ks_shop_products_specifications", $verif);
    $products_specifications = sql::brute_fetch();
    foreach($products_specifications as $product_spec) {
      $product = $products[$product_spec['product_id']];
      if(!$product->products_specifications)
        $product->products_specifications = array();
      $product->products_specifications[$product_spec['specification_key']] = $product_spec;
    }

    sql::select("ks_shop_products_categories", $verif);
    $products_categories = sql::brute_fetch();
    foreach($products_categories as $product_categ) {
      $product = $products[$product_categ['product_id']];
      if(!$product->products_categories)
        $product->products_categories = array();
      $product->products_categories[$product_categ['category_id']] = $product_categ['category_id'];
      if($product['product_id']) {

      }
    }

    // Vignettes
    $imgs_path = yks::$get->config->shop['imgs_url_mask']?yks::$get->config->shop['imgs_url_mask']:"/imgs/".SITE_BASE."/products/%s";
    foreach($products as $product)
      $product->product_img = sprintf($imgs_path, $product['product_id']);

    // Heritage
    self::product_variations($products);

    if(!$related_products)
      $products = array_intersect_key($products, array_flip($head_products_list));

    return $products;
  }


  private static function product_variations($products) {

    foreach($products as $product_id=>$product) {

      $parent = $products[$product['parent_id']];
      if($parent) {
        if(!$parent->product_declinaisons)
          $parent->product_declinaisons = array();
        $parent->product_declinaisons[$product_id] = $product;

      // On complète les données des enfants avec celle de son papa au besoin
      if(!glob(ROOT_PATH.$product->product_img))
        $product->product_img = $parent->product_img;
      if(!$product->product_price )
        $product->product_price = $parent->product_price;
      }
    }
  }

  public function clone_product_and_variations() {

    $variations_products_id = self::get_children($this->product_id);
    $variations_products = self::from_where(array('product_id' => $variations_products_id), false);

    $parent_clone = $this->clone_product();
    if(!$parent_clone) throw rbx::error("Erreur durant le clonage d'un produit et ses variations");

    $overdata = array (
      'parent_id'     => $parent_clone->product_id,
    );

    foreach($variations_products as $product)
      $id = $product->clone_product($overdata);

    return $parent_clone;
  }

  public function clone_product($overide_data=false) {
    $data = (array)$this->data;

    if($overide_data)
      foreach($overide_data as $data_key=>$overdata)
        if(isset($data[$data_key]))
          $data[$data_key] = $overdata;

    unset($data[self::sql_key]);

    $product_id = sql::insert(self::sql_table, $data, true);
    if(!$product_id) throw rbx::error("erreur durant le clonage du produit");

    $res = true;
    $meta_products = array_extract($this->meta_products_list, 'meta_product_id');
    foreach($meta_products as $meta_product)
      $res &= sql::insert('ivs_shop_meta_products_products',array('meta_product_id'=>$meta_product, 'product_id'=>$product_id));

    $owners = array_extract($this->owners_list,'user_id');
    foreach($owners as $owner)
      $res &= sql::insert('ivs_shop_products_owners',array('user_id'=>$owner, 'product_id'=>$product_id));

    foreach($this->products_categories  as $categorie)
      $res &= sql::insert('ivs_shop_products_categories',array('category_id'=>$categorie, 'product_id'=>$product_id));

    $products_specifications = $this->products_specifications;
    foreach($products_specifications as $product_spec_key=>$product_spec) {
      $data = array(
        'product_id'          => $product_id,
        'specification_key'   => $product_spec['specification_key'],
        'specification_value' => $product_spec['specification_value'],
      );
      $res &= sql::insert('ks_shop_products_specifications',$data);
    }

    if(!$res) throw rbx::error("Erreur durant le clonage des propriétés du produit $product_id.");

    return self::instanciate($product_id);
  }

  /**
  *   retourne les informations sur une liste de produit
  *   get_infos ($product_id || $products_id || $product_infos || $products_infos )
  */
  static function get_infos($product_infos){
    if(!$product_infos) return array();

    $verif_status=array('product_status'=>'active');

    if($product_start_id=(int)$product_infos['product_id'])
    $products_infos=array($product_start_id=>$product_infos);
    $first = first($product_infos);

    if(!$item['product_id']){ // si $product_infos['product_id'] existe deja.. rien à faire
      $products_infos = self::get_tree_definition($product_infos);
      //if(!$force) $verif_products+=$verif_status;
    }
    $verif_products = array('product_id'=>array_keys($products_infos));
    sql::select("ks_shop_products_specifications", $verif_products);
    $products_specifications  = array();
    while($l=sql::fetch())
        $products_specifications[$l['product_id']]
        [$l['specification_key']] = $l['specification_value'];

    $types=array();$products_children=array();

    foreach($products_infos as $product_id=>&$product_infos){
      $product_infos = &$products_infos[$product_id]; //php bug (5.2.1)
      $product_specifications = $products_specifications[$product_id];
      $product_infos['product_options'] = $product_specifications;
      $parent_id = $product_infos['parent_id'];

      //..$types[]=$type=$product_infos['product_type'];
      //recursive load of pack product_type

      $product_infos['product_img'] = "/imgs/".SITE_CODE."/products/$product_id";
      if($parent_id && $parent_id != $product_id) {
        $parent_infos = &$products_infos[$parent_id];
        if(!glob(ROOT_PATH.$product_infos['product_img']))
          $product_infos['product_img']=&$parent_infos['product_img'];
        if(!$product_infos['product_price'] )
          $product_infos['product_price']=&$parent_infos['product_price'];
        if($product_specifications)
          foreach($product_specifications as $specification_key=>$specification_value)
            $parent_infos['product_declinaisons']['product_options']
            [$specification_key][$specification_value] = $product_id;

        unset($parent_infos);
      }

      continue; //!

    } unset($product_infos);
    $products_infos=array_merge_numeric($products_infos,$products_children);

    $verif_products=array('product_id'=>array_keys($products_infos));
    sql::select("ks_shop_products_categories",$verif_products);
    while(extract(sql::fetch()))
    $products_infos[$product_id]['product_categs'][]=$category_id;

    //suivant ce qui avait été demandé initialement, on retourne un, ou plusieurs trucs
    return $product_start_id?$products_infos[$product_start_id]:$products_infos;
  }


  /*
  retourne les information d'un produit, ou d'une liste d'id de produits
  */
  /*static function get_infos_splat($product_id){
    $products_list=(array)$product_id;
    $infos = self::get_infos($products_list);
    return (array)(is_array($product_id) ? $infos : $infos[$product_id]);
  }*/



  static function get_ci($infos,$addr,$unit=false){
    $qty=$unit?1:$infos['product_qty'];
    return round($infos['product_price']*$qty*(1+taxes::get($infos['tax_type'],$addr)),2);
  }

//recurse 1 level up, 1 level down
  private static function get_product_tree($products_ids){

    sql::$queries = array();
    $verif_products = sql::where( array('product_id'=>$products_ids, 'parent_id' => $products_ids), false, " OR ");
    sql::select("ks_shop_products_list", $verif_products, "*", "ORDER BY IF( product_id = parent_id  OR parent_id IS NULL , 1 ,0) DESC");
    $product_ids_extended = sql::brute_fetch("product_id");
    $product_ids_extended  = array_unique(
        array_merge(array_keys($product_ids_extended),
        array_filter(array_extract($product_ids_extended , "parent_id"))));
    if(array_diff($product_ids_extended, $products_ids))
      return self::get_product_tree($product_ids_extended);
    return $product_ids_extended;
  }


  static function get_children($product_id,$depth=-1){
    return sql_func::get_children($product_id,'ks_shop_products_list','product_id',$depth);
  }

  static function get($tables, $type, $addr){

    $table_priority=array(
      ''=>'all',
      'geo_zone'=>'',
      'country_code'=>$addr['country_code'],
      'region_id'=>'',
      'addr_code'=>$addr['addr_code'],
      'addr_city'=>$addr['addr_city'],
    );$price=0;
    foreach ($table_priority as $zone_type=>$zone_geo){
      if (isset($tables[$type][$zone_type][$zone_geo]))
        $price=$tables[$type][$zone_type][$zone_geo];
    } return $price;
  }
}


