<?

class products_list {
  private $products_infos;
  private $products_list;
  private $_specifications;
  function __construct($products_list){
    $this->products_list = $products_list;

        //choppe les parents
    $products_list = sql_func::get_parents($products_list, "ks_shop_products_list", "product_id");

        //et les enfants des parents
    $products_list = array_merge($products_list,
        sql_func::get_children($products_list, "ks_shop_products_list", "product_id"));

    //maintenant qu'on a tt le monde, on dresse la liste des produits

    sql::select("ks_shop_products_list", array('product_id'=>$products_list));
    $this->products_infos = sql::brute_fetch("product_id");

    $verif_products = array('product_id'=>array_keys($this->products_infos));
    sql::select("ks_shop_products_specifications", $verif_products);
    $this->_specifications  = array();
    while($l=sql::fetch())
        $this->_specifications[$l['product_id']]
            [$l['specification_key']] = $l['specification_value'];

    sql::select("ks_shop_products_categories", $verif_products);
    while($l=sql::fetch())
        $this->products_infos[$l['product_id']]['product_categs'][] = $l['category_id'];

    foreach($this->products_infos as $product_id=>&$product_infos){
        $img = "/imgs/".SITE_BASE."/products/$product_id";
        if(glob(PUBLIC_PATH."$img.*")) $product_infos['product_img'] = $img;
    }

    foreach(array_keys($this->products_infos) as $product_id)
        $this->products_infos[$product_id] = &$this->get_product_definition($product_id);

  }

  function get_products_definition(){
    return array_intersect_key($this->products_infos, array_flip($this->products_list));;
  }

  function get_products_list(){
    return $this->products_infos;
  }


  function &get_product_definition($product_id, $snap_child = true){
    extract($product_infos = &$this->products_infos[$product_id]);
    $product_infos['product_options'] = $this->_specifications[$product_id];

    $product_base_ids = array($product_id);


    if($parent_id!=$product_id) {
        if($product_relation_type == "derivation"){
            $tmp = $this->get_product_definition($parent_id, false);
            unset($product_infos['parent_id']); //useless
            //foreach($tmp as $k=>&$v) if(is_null($product_infos[$k])) $product_infos[$k] = &$v;

            $product_infos = array_merge($tmp, array_filter($product_infos, "is_not_null"));
        }
    }

    foreach($this->products_infos as $child_id=>&$child_infos){
        if(!($child_infos['parent_id']==$product_id
             && $child_id != $product_id
             && $child_infos['product_relation_type'] == "variation"
             )) continue;
        $child = &$this->get_product_definition($child_id);

        foreach($child['product_options'] as $specification_key=>$specification_value) {
            $product_infos['product_declinaisons']['product_options']
                [$specification_key][$specification_value] = &$child;
            $product_infos['products_childs'][$child_id] = &$child; //splat
        }
        unset($child); unset($child_infos);
    }


    if($snap_child && $product_infos['products_childs']) {
         $product_root = $product_infos;
         unset($product_root['products_childs']); unset($product_root['product_declinaisons']); 
         foreach($product_infos['products_childs'] as $child_id=>&$child_infos) {
            //foreach($product_root as $k=>&$v) if(is_null($child_infos[$k])) $child_infos[$k] = &$v;
            $child_infos = array_merge($product_root, array_filter($child_infos, "is_not_null"));
                //we dont want to allow recursion here
            //unset($child_infos['product_declinaisons']); unset($child_infos['products_childs']);
            unset($child_infos);
        }
    }


    return $product_infos;

    //if($product_infos['product_relation_type'] != "root"

  }


}