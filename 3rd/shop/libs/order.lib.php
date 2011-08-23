<?

class order extends _sql_base {

  const sql_table = 'ks_shop_orders';
  const sql_key   = 'order_id';
  protected $sql_key   = self::sql_key;
  protected $sql_table = self::sql_table;

  protected $manager = "orders_manager";  
  static $products_list = array(); //shortcut like "available products"  

  public $deposit_infos;//struct {deposit_down_limit/deposit_rate}
  public $addrs;

  static function from_where($where){
    return parent::from_where(__CLASS__, self::sql_table, self::sql_key, $where);
  } 
  
  static function from_ids($ids){
    return self::from_where(array(self::sql_key => $ids));
  }


  public static function instanciate($order_id) {
    $order = self::from_ids($order_id);
    return $order[$order_id];
  }

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

  function addr_set($addr_type, $addr_infos){
    $this->addrs[$addr_type] = $addr_infos;
  }
  
  
}
