<?

class user extends _user {
  const sql_table = 'ks_users_list';
  const sql_key = 'user_id';

  protected $sql_table = "ks_users_list";
  protected $sql_key = "user_id";

  private $tables_registration = array();
  function __construct($user_id, $auth_tree){
    $this->users_tree = $auth_tree;
    parent::__construct($user_id);
    $this->update(array('user_connect'=>_NOW), "ks_users_profile");

    $this->user_acces = auth::renew($this->users_tree);
  }

  function register($key, $table_name){
    if(!($table_xml = yks::$get->tables_xml->$table_name)) return false;
    $table_keys = fields($table_xml,'primary'); unset($table_keys['user_id']);//!!
    $table_key = count($table_keys)==1?reset($table_keys):false;
         //on indexe les resultat sur la deuxieme clée(si unique, hors user_id de join)
    $this->tables_registration[$key] = compact('table_name', 'table_key');
    return $this;
  }
  private function get_extended_infos($key){
    if((!$tmp = $this->tables_registration[$key]) || !extract($tmp)) return false;
    sql::select($table_name, $this);
    return sql::brute_fetch($table_key, $table_key);
  }

  function __get($key){
    if(isset($this->tables_registration[$key])){
        return $this->get_extended_infos($key);
    }

    return parent::__get($key);
  }

}