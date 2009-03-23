<?
    //extending ArrayObject should have worked, but it break session storage
abstract class _sql_base  implements ArrayAccess {

   protected $sql_table=false;
   protected $sql_key=false;
   private $data;

  function __construct($from){
    if(!($this->sql_table && $this->sql_key))
        throw "Invalid definition for _sql_base";

    if(is_array($from))
        $this->feed($from);
    else $this->from_id((int)$from);
  }

  function feed($data){
    $this->data = $data;
  }
  function __get($key){
    if(isset($this->data[$key]))
        return $this->data[$key];
    if(method_exists($this, $getter = "get_$key"))
        return $this->$getter();
  }
  function __sql_where(){
    $key = $this->sql_key;
    return array($key=> $this->$key);
  }

  function from_id($key_id){
    $verif_base = array($this->sql_key => $key_id);
    $data = sql::row($this->sql_table, $verif_base);
    $this->feed($data);
  }

    //proxies to manager's static functions
  function __call($method, $args){
    if(!($this->manager && method_exists($this->manager, $method))) return;
    array_unshift($args, $this);
    return call_user_func_array(array($this->manager, $method), $args);
  }


  static function from_where($class, $sql_table, $sql_key, $where) {
    sql::select($sql_table, $where); $tmp = array();
    foreach(sql::brute_fetch($sql_key) as $key_id=>$key_infos)
        $tmp[$key_id] = new $class($key_infos);
    return $tmp;
  }

  static function from_ids($class, $sql_table, $sql_key, $ids) {
    return self::from_where($class,  $sql_table, $sql_key, array($sql_key=>$ids));
  }

  function _set($key, $value){
        //fonction temporaire, à refactoriser par update une fois _user correctement integré
    $this->data[$key] = $value;
    return $this;
  }

  function offsetExists ($offset){}
  function offsetGet($key){ return $this->$key;}
  function offsetSet($offset,$value){}
  function offsetUnset($offset){}

}