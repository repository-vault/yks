<?

class _user extends _sql_base {
  const sql_table = 'ks_users_list';
  const sql_key = 'user_id';

  protected $sql_table = "ks_users_list";
  protected $sql_key = "user_id";
  private $storage = array();

  function __toString(){ return $this->user_name; }

  function get_users_tree(){ return $this->users_tree = users::get_parents($this->user_id); }

  function __construct($user_id){
    $this->user_id = (int)$user_id;
    if(!$this->user_id || !$this->users_tree) throw new Exception("Unable to load $user_id");
    $this->computed = array(); $this->users_types = array();
    foreach(users::get_infos($this->users_tree,"*") as $line){
        $this->users_types[$line['user_type']] = $line['user_id'];
        $this->computed = array_merge($this->computed, array_filter($line,'is_not_null'));
    } $data = array_intersect_key($line, array_flip(array('auth_type', 'user_type', 'user_name')));
    $data["parent_id"] = $this->users_tree[max(count($this->users_tree)-2,0)];
    parent::__construct($data);
    $user_type = ltrim($this->user_type,'ks_').'_id';
    if($this->user_type!="ks_users") $this->$user_type = $user_id;
    $this->sql_key = $user_type;
  }

  function _set($key, $value){
    if(isset($this->computed[$key])){
        $this->computed[$key] = $value;
        return $this;
    } return parent::_set($key, $value);
  }

  function store($key, $value){ $this->storage[$key] = $value; return $this; }
  function delete($key){ unset($this->storage[$key]); return $this; }
  function &retrieve($key){ return $this->storage[$key]; }

  function __sql_where($sql_table = false){
    $key = $this->sql_key;
    if($table_xml  = yks::$get->tables_xml->$sql_table)
        $key = reset(array_keys(fields($table_xml), _user::sql_key));
    return array($key => $this->$key);
  }

  function __get($key){
    $get = parent::__get($key);
    if(!is_null($get)) return $get;
    if(isset($this->computed[$key]))
        return $this->computed[$key];
    if(isset($this->storage[$key]))
        return $this->storage[$key];
  }
}
