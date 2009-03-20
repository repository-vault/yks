<?

function coalesce($cols,$alias=false){
 return count($cols)==1?$cols[0]:("COALESCE(".join(',',$cols).")".($alias?" AS $alias":''));}


class users 
{
    // user_id || users_id

  static $cols_def=false;
  static $users_profiles =array();

  static function get_addrs($user_id,$addr_type=array('sql'=>"!=''")){
    sql::select('ks_users_addrs',compact('user_id','addr_type'));
    return sql::brute_fetch('addr_type');
  }

  static function get_infos_unique($user_id, $cols=array('user_name'),$where=array()){
     if(!$user_id) return array();
     $tmp=self::get_infos(array($user_id),$cols,false,false,false,$where);
     return $tmp?$tmp[$user_id]:array();
  }

  static function get_infos($users, $cols=array('user_name'), $sort=false, $start=false, $by=false, $where=array()) {

    if(!self::$cols_def) self::init();

    if(!is_array($users) || !$users) return array();
    $selected=array('user_id'=>'user_id'); $tables_used=array();

    $limit="LIMIT ".count($users);

    if($cols =='*') {
        $tables_used=self::$users_profiles;
        $selected="*";
    } else { 
        foreach($cols as $col) if(self::$cols_def[$col]&& !$selected[$col]) {
            $tables_used=array_merge($tables_used,self::$cols_def[$col]);
            $selected[$col]=coalesce(array_mask(self::$cols_def[$col],"`%s`.`$col`"),$col);
        }elseif(strpos($col," ")!==false) $selected[]=$col;
        $selected=join(',',$selected);
    }

    if($where && $slice=array_keys($tmp=array_filter($where,'is_array'))){
        foreach($slice as $k=>$col){
                $tables_used=array_merge($tables_used,self::$cols_def[$col]);
                $slice[$k]=coalesce(array_mask(self::$cols_def[$col],"`%s`.`$col`"));
        } $where=array_merge(array_diff($where,$tmp),array_combine($slice,$tmp)); //powa
    }

    if($start!==false) $limit="LIMIT $by OFFSET $start";

    if($where) $limit=""; else $start=$by=false;

    $tables_used=array_diff(array_unique($tables_used),array('ks_users_list'));
    $where=sql::where(array_merge(array('user_id'=>$users),$where));

    if($sort) {
        if(!self::$cols_def[$sort]) $order="ORDER BY $sort";
        else $order="ORDER BY TRIM(".coalesce(array_mask(self::$cols_def[$sort],"`%s`.$sort")).')';
    } else $order="";

    sql::query("SELECT $selected 
        FROM `ks_users_list` ".mask_join(' ',$tables_used,"LEFT JOIN `%s` USING(`user_id`)")."
        $where $order $limit
    "); $users_infos=sql::brute_fetch('user_id',false,$start,$by);

    if(!$sort)
        $users_infos=array_filter(array_merge_numeric(array_flip($users),$users_infos),'is_array');
    return $users_infos;
  }

  static function get_addr($user_id){return sql::row("ks_users_addrs",compact('user_id'));}
  static function get_parents($user_id){ return get_parents($user_id,'ks_users_tree','user_id'); }
  static function get_children($user_id,$depth=-1){
    return get_children($user_id,'ks_users_tree','user_id',$depth);}

    //this implementation only works for postgres 
  static function get_children_infos($parent_id, $where=true, $cols=array()){
    $query = "SELECT * FROM
    ivs_users_tree($parent_id) AS (user_id INTEGER, parent_id INTEGER, depth INTEGER)
    LEFT JOIN ivs_users_list USING(user_id) 
    ".sql::where($where);
    sql::query($query);
    $users_list = sql::brute_fetch('user_id');
    if($cols)
        $users_list = array_merge_numeric($users_list,
            self::get_infos(array_keys($users_list), $cols));
    return $users_list;
  }

  static function get_root_dir($user_id){ return 'files/'.crpt($user_id,FLAG_FILE,10);}
  static function get_tmp_dir($user_id){ return ROOT_PATH.'/config/tmp/'.crpt($user_id,FLAG_FILE,10);}

  static function show($user_infos){
    if(!$avatar=$user_infos['user_avatar'])$avatar="/imgs/blank.png";
    return "<div class='user'>
        <img src='$avatar' alt='avatar'/><br/>{$user_infos['user_name']}
    </div>";
  }

  static function init(){
    $users_type=vals(yks::$get->types_xml->user_type);
    self::$users_profiles=array_mask($users_type,'%s_profile');

    self::$cols_def=array(); $tables_xml = yks::$get->tables_xml;
    foreach(self::$users_profiles as $k=>$table_name)
        if(!$tables_xml->$table_name)unset(self::$users_profiles[$k]);

    $tables=array('ks_users_list','ks_users_tree','ks_auth_password');
    foreach(array_merge(self::$users_profiles,$tables) as $table_name)
        self::$cols_def=array_merge_recursive(self::$cols_def,
            array_fill_keys(
                vals($tables_xml->$table_name,'field'),
                array($table_name)
            ));
    //done
  }

}

