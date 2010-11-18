<?

class order extends _mykse {
  protected $manager = "orders_manager";

  const sql_table = 'ks_shop_orders';
  const sql_key   = 'order_id';
  protected $sql_key   = self::sql_key;
  protected $sql_table = self::sql_table;


  public $deposit_infos;//struct {deposit_down_limit/deposit_rate}
  public $addrs;

  static $products_list = array(); //shortcut like "available products"

  function __construct($from){
    parent::__construct($from);
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
