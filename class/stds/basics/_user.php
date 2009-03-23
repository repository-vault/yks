<?

class _user extends _sql_base {
  const sql_table = 'ks_users_list';
  const sql_key = 'user_id';

  protected $sql_table = "ks_users_list";
  protected $sql_key = "user_id";

  function __toString(){ return $this->user_name; }

  function get_parents(){ return $this->parents = users::get_parents($this->user_id); }

  function __construct($user_id){
    if(!is_int($user_id)) return;
    $this->parents = users::get_parents($user_id);
    if(!$this->parents) return;
    $this->computed = array(); $this->types = array();
    foreach(users::get_infos($this->parents ,"*") as $line){
        $this->types[$line['user_type']] = $user_id;
        $this->computed = array_merge($this->computed, array_filter($line,'is_not_null'));
    } $data = array_intersect_key($line, array_flip(array('auth_type', 'user_type', 'user_name')));
    $data["parent_id"] = $this->parents[max(count($this->parents)-2,0)];
    parent::__construct($data);
    $user_type = ltrim($this->user_type,'ks_').'_id';
    $this->$user_type = $user_id;
  }

}
