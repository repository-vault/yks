<?

class order extends _mykse {
  protected $manager = "orders";

  const sql_table = 'ks_shop_orders';
  const sql_key = 'order_id';

  protected $sql_table = "ks_shop_orders";
  protected $sql_key = "order_id";

  function update($data, $update_profile = true){
    $ret = true;

    $final_update = true 
                && $this->order_status != "done" 
                &&  $data['order_status'] == "done";

    sql::begin();
 
    if($final_update){
        if($update_profile){
            $this->close_products();
            $ret = array('infos' => "Closing order, applying products specifications.");
        } else 
            $ret = array('infos' => "Explicitly skipping products specifications attributions.");
    }

    if(!parent::sql_update($data))
        throw sql::rollback("Error while processing order.");
    sql::commit();
    return $ret;

  }

 
  function close_products(){
    foreach($this->products_list as $product_id=>$product_infos){
        $profile_key = $product_infos['product_options']['profile_key'];
        if(!$profile_key) continue; 
        list($table_name, $field_name, $profile_key)
            = preg_list('#^(.*?)\[(.*?)\]\[(.*?)\]$#', $profile_key);
        $key_name = reset(fields(yks::$get->tables_xml->$table_name,"primary"));
        $verif_criteria  = array($key_name =>$this->$field_name);
        $data = array($profile_key=>array('sql'=>"`$profile_key` + {$product_infos['product_qty']}"));
        sql::update($table_name, $data, $verif_criteria);
    }
  }

  function get_products_list(){
    sql::select("ks_shop_orders_parts", $this);
    $products_list = sql::brute_fetch('product_id');
    $basket = new products_list(array_keys($products_list));
    $products_infos  = $basket->get_products_definition();

    return $this->products_list = array_merge_numeric($products_list, $products_infos);
  }
    
}
