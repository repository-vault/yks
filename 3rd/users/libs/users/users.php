<?php

function coalesce($cols,$alias=false){
 return count($cols)==1?$cols[0]:("COALESCE(".join(',',$cols).")".($alias?" AS $alias":''));}


class users  {
    // user_id || users_id

  static $cols_def=false;
  static $users_profiles =array();

  static function get_addrs($user_id,$addr_type=array('sql'=>"!=''")){
    sql::select('ks_users_addrs',compact('user_id','addr_type'));
    return sql::brute_fetch('addr_type');
  }

  static private $infos_tables = array('ks_users_list','ks_users_tree','ks_auth_password', 'ks_users_addrs');

  static function get_infos_unique($user_id, $cols=array('user_name'), $where=array()){
     if(!$user_id) return array();
     $tmp=self::get_infos(array($user_id) , $cols, $where);
     return $tmp?$tmp[$user_id]:array();
  }


  static function update_password($user, $user_login, $user_pswd) {
    $user_id    = $user['user_id']; $verif_user = compact('user_id');
    $data       = compact('user_login', 'user_pswd');
    if($data['user_login']) {
        $tmp = sql::row("ks_auth_password", array('user_login'=>$data['user_login']));
        if($data['user_pswd'] && ( !$tmp || $tmp['user_id']==$user_id) ){
            $data['user_pswd'] = crpt($data['user_pswd'], FLAG_LOG);
            sql::replace("ks_auth_password", $data, $verif_user);
        } else throw rbx::warn("Invalid password");
    } else throw rbx::warn("Invalid login");
  }


  static function get_infos($users, $cols=array('user_name'), $where=array(), $sort=false, $start=false, $by=false) {

    if(!self::$cols_def) self::init();

    if(!is_array($users) || !$users) return array();
    $selected=array('user_id'=>'user_id'); $tables_used=array();

    $limit="LIMIT ".count($users);

    if(is_string($cols) ) {
        $tables_used = self::$users_profiles;
        $selected = $cols;
    } else { 
        foreach($cols as $col) if(self::$cols_def[$col]&& !$selected[$col]) {
            $tables_used    = array_merge($tables_used, self::$cols_def[$col]);
            $selected[$col] = coalesce(array_mask(self::$cols_def[$col],"`%s`.`$col`"),$col);
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

    sql::query("SELECT "
        .CRLF."$selected "
        .CRLF."FROM `ks_users_list` "
        .CRLF.mask_join(CRLF, $tables_used, "LEFT JOIN `%s` USING(`user_id`) ")
        .CRLF." $where $order $limit"
    ); $users_infos=sql::brute_fetch('user_id',false,$start,$by);

    if(!$sort)
        $users_infos=array_filter(array_merge_numeric(array_flip($users),$users_infos),'is_array');
    return $users_infos;
  }

  static function get_addr($user_id){return sql::row("ks_users_addrs",compact('user_id'));}
  static function get_parents($user_id){
    if(SQL_DRIVER =='pgsql') {
        sql::select("`ks_users_parents`($user_id) AS (parent_id INTEGER)");
        $res = array_reverse(sql::brute_fetch(false, 'parent_id')); $res[]= $user_id;
        return $res;
    } else return sql_func::get_parents_path($user_id,'ks_users_tree','user_id');
  }

  static function get_children($user_id, $depth=-1){
    $users_list = sql_func::get_children($user_id, 'ks_users_tree', 'user_id', $depth);
    return array_unique($users_list);
  }

    //this implementation only works for postgres 
  static function get_children_infos($parent_id, $where=true, $cols=array()){
    $mask_tree = "`ks_users_tree`(%d) AS (user_id INTEGER, parent_id INTEGER, depth INTEGER)";
    if(!$parent_id) return array();
    if(!is_array($parent_id)) $parent_id = array((int)$parent_id);
    $query_tree = "(".mask_join(" UNION ", $parent_id, " SELECT * FROM $mask_tree").") as tmp";
    $query = "SELECT * FROM
        $query_tree    
        LEFT JOIN `ks_users_list` USING(user_id) 
    ".sql::where($where);
    sql::query($query);
    $users_list = sql::brute_fetch('user_id');
    if($cols)
        $users_list = array_merge_numeric($users_list,
            self::get_infos(array_keys($users_list), $cols));
    return $users_list;
  }

  static function get_root_path($user_id){ return 'files/'.crpt($user_id,FLAG_FILE,10);}
  static function get_tmp_path($user_id){ return TMP_PATH.'/'.crpt($user_id,FLAG_FILE,10);}

  static function show($user_infos){
    if(!$avatar=$user_infos['user_avatar'])$avatar="/imgs/blank.png";
    return "<div class='user'>
        <img src='$avatar' alt='avatar'/>{$user_infos['user_name']}
    </div>";
  }

  static function mailto(){//$users,
    $args = func_get_args(); //no aargs, users could be simple arrays
    $mailto = array();
    foreach($args as $user)
        $mailto[] = mailto_escape("{$user['user_name']} <{$user['user_mail']}>");
    return 'mailto:' . join('; ', $mailto);
  }

  static function infos_renderer($user_ids, $key, $lang, $cols=array('user_name','user_avatar')){
    $users_infos = users::get_infos($user_ids, $cols);$res=array();
    foreach($users_infos as $user_id=>$user_infos)$res["&$key.$user_id;"] = self::show($user_infos);
    return $res;
  }

  static function print_path($user_id){
    $parents = is_array($user_id)?$user_id:array_merge(users::get_parents($user_id), array($user_id));
    return mask_join(" &gt; ", array_extract(users::get_infos($parents), 'user_name'), "%s");
  }

  static function init(){
    if(!classes::init_need(__CLASS__)) return;

    $users_type = vals(yks::$get->types_xml->user_type);
    self::$users_profiles = array_mask($users_type,'%s_profile');

    self::$cols_def=array(); $tables_xml = yks::$get->tables_xml;
    foreach(self::$users_profiles as $k=>$table_name)
        if(!$tables_xml->$table_name)unset(self::$users_profiles[$k]);

    $tables = array_merge(self::$users_profiles, self::$infos_tables);
    foreach($tables as $table_name)
        self::$cols_def = array_merge_recursive(self::$cols_def,
            array_fill_keys(
                vals($tables_xml->$table_name,'field'),
                array($table_name)
            ));
    self::$cols_def['user_id'] = array('ks_users_list');
    //done
  }


/*
    (void) Extend an object list and add new property, enable user creations
    Use users::inform((node)$mystufflist, 'sender_id') //populate all (node) with ->sender
    Also users::inform((node)$mystufflist, array('sender'=>'user_id' [,…]));
*/

  public static function inform($list, $fields, $cols=array('user_name', 'addr_phone', 'user_mail')) {
    if(!is_array($fields)) $fields = array( strip_end($fields, '_id') => $fields);

    $users_list = array();
    foreach($fields as $tmp) $users_list = array_merge($users_list, array_extract($list, $tmp));
    $users_list = array_filter(array_unique($users_list));
    $users_list = users::get_infos($users_list, $cols);

    foreach($list as $item)
        foreach($fields as $prop=>$key)
            $item->{$prop} = $users_list[$item->{$key}];
  }

/*
    Check if a file the user upload is fine to be used
    Usage $file_infos = upload_check( upload_type, $_POST['myfile'] )
    returns compact('tmp_file', 'file_ext');
*/
  static function upload_check($upload_type, $upload_file){
    $tmp_path = users::get_tmp_path(sess::$sess['user_id']); //tmp upload dir
    $tmp_file = "$upload_type.$upload_file";

    if(!preg_match(FILE_MASK,$tmp_file)|| !is_file($tmp_file="$tmp_path/$tmp_file") )
        return false;
    $file_ext = files::ext($tmp_file);
    return compact('tmp_file','file_ext');
  }

}

