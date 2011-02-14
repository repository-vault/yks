<?php

function coalesce($cols,$alias=false){
 return count($cols)==1?$cols[0]:("COALESCE(".join(',',$cols).")".($alias?" AS $alias":''));}


class users  {
    // user_id || users_id

  private static $table                 = "ks_users_list";
  private static $cols_def_horizontal  = false;
  private static $cols_def_vertical    = false;

  private static $users_linear_tables   = array(); //todo refactor & merge with horizontal

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


  static function get_infos($users, $cols=array('user_name'), $where=array(), $sort = null, $start=false, $by=false) {

    if(!self::$cols_def_horizontal) self::init();

    if(!is_array($users) || !$users) return array();
    $selected=array('user_id'=>'user_id');
    $tables_used_horizontal = array();
    $tables_used_vertical   = array();

    $limit="LIMIT ".count($users);

    if(is_string($cols) ) {
        $tables_used_horizontal = self::$users_linear_tables;
        $selected = $cols;
    } else {
        foreach($cols as $col) if(self::$cols_def_horizontal[$col]&& !$selected[$col]) {
            $tables_used_horizontal    = array_merge($tables_used_horizontal, self::$cols_def_horizontal[$col]);
            $selected[$col] = coalesce(array_mask(self::$cols_def_horizontal[$col],"`%s`.`$col`"),$col);
        } elseif(strpos($col," ")!==false) $selected[]=$col;
        $selected=join(',',$selected);
    }
    
      //attention, les filtres where ne sont pas appliqués verticalement.
    if($where && $slice=array_keys($tmp=array_filter($where,'is_array'))){
        foreach($slice as $k=>$col){
                $tables_used_horizontal=array_merge($tables_used_horizontal,self::$cols_def_horizontal[$col]);
                $slice[$k]=coalesce(array_mask(self::$cols_def_horizontal[$col],"`%s`.`$col`"));
        } $where=array_merge(array_diff($where,$tmp),array_combine($slice,$tmp)); //powa
    }

    if($start!==false) $limit="LIMIT $by OFFSET $start";

    if($where) $limit=""; else $start=$by=false;

    $tables_used_horizontal=array_diff(array_unique($tables_used_horizontal),array('ks_users_list'));
    $where = sql::where(array_merge(array('user_id'=>$users),$where));

    if($sort) {
        if(!self::$cols_def_horizontal[$sort]) $order="ORDER BY $sort";
        else $order="ORDER BY TRIM(".coalesce(array_mask(self::$cols_def_horizontal[$sort],"`%s`.$sort")).')';
    } else $order="";

    sql::query("SELECT "
        .CRLF."$selected "
        .CRLF."FROM `ks_users_list` "
        .CRLF.mask_join(CRLF, $tables_used_horizontal, "LEFT JOIN `%s` USING(`user_id`) ")
        .CRLF." $where $order $limit"
    ); list($users_infos) = sql::partial_fetch('user_id', false, $start, $by);

        //traitement vertical 
        //!! attention on traite (aujoud'hui) au PLUS une colonne de valeur (non membre de la clée)
    $vcols = $cols;
    if($vcols == "*") {
     $vcols = array();
     foreach(self::$cols_def_vertical as $col_name=>$index_infos)
        $vcols[$index_infos['index_name']]= $col_name;
    }
    if(is_array($vcols))
      foreach($vcols as $col_name) if($tmp = self::$cols_def_vertical[$col_name]) {
        $index_name   =  $tmp['index_name'];
        $table_name   = (string)$tmp['table_children_name'];
        $verif_users  = array('user_id' => array_keys($users_infos));
        sql::select($table_name, $verif_users);
        while($line = sql::fetch()) {
          $index_value = array();
          foreach($tmp['keys'] as $k=>$v) $index_value[$k] = $line[$k];
          $index_value = '{'.mask_join(',', $index_value, '%2$s:%1$s').'}';
          $users_infos[$line['user_id']][$col_name][$index_value] = $line;          
        }
     }
    
    
    if(is_null($sort))
        $users_infos = array_sort($users_infos, $users);
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

  static function recompute_tree_from_flying_nodes($users_ids, $uttermost_user = USERS_ROOT){
    
    $tree_table = "ks_users_tree";
    $tree_key   = "user_id";
    $tree = sql_func::filter_parents($users_ids, $tree_table, $tree_key);
    sql::select($tree_table, array($tree_key => $tree));
    $treex =  sql::brute_fetch("user_id");

      //on recompose un tree complet
     $tree = array();
     foreach($treex as $user_id=>$user){
       
        $parent_id = $user['parent_id'];
        if ($user_id == $parent_id) $parent_id = null;
        if(!$tree[$user_id] ){
            $tree[$user_id]  = $user;
        } else {
           $tmp =  &$tree[$user_id]['children'];
           $tree[$user_id] = $user;
           $tree[$user_id]['children'] = $tmp; unset($tmp);
        }
        if(!$tree[$parent_id] ) $tree[$parent_id]  = array();
        $tree[$parent_id]['children'][$user_id] = &$tree[$user_id];
     }
     $tree=  array_intersect_key($tree, array($uttermost_user => false));;
     return $tree;
  }
  
  static function get_children($user_id, $depth=-1){
    $users_list = sql_func::get_children($user_id, 'ks_users_tree', 'user_id', $depth);
    return array_unique($users_list);
  }
  
  static private function get_children_query($parent_id){
    $mask_tree = "`ks_users_tree`(%d) AS (user_id INTEGER, parent_id INTEGER, depth INTEGER)";
    if(!is_array($parent_id)) $parent_id = array((int)$parent_id);
    $query = mask_join(" UNION ", $parent_id, " SELECT * FROM $mask_tree");
    return $query;
  }

  static function get_children_tree($parent_id){
    //TODO : TRAHSME & use recompute_tree_from_flying_nodes (a lot smarter)
  $parents =  (!is_array($parent_id))?array((int)$parent_id): $parent_id;
  $query_tree = self::get_children_query($parents);
  foreach($parents as $parent_id)
    $query_tree .= " UNION (SELECT $parent_id, $parent_id,  0) ";
  
  $query = "SELECT * FROM `ks_users_list` INNER JOIN ($query_tree) AS tmp USING(user_id)";
   sql::query($query);
   //sql::select(array("ks_users_tree", "user_id"=>"ks_users_list"));
   $users_list = sql::brute_fetch("user_id");

   $tree = array();
   foreach($users_list as $user_id=>$user){
      $parent_id = $user['parent_id'];
      if ($user_id == $parent_id) $parent_id = null;
      if(!$tree[$user_id] ){
          $tree[$user_id]  = $user;
      } else {
         $tmp =  &$tree[$user_id]['children'];
         $tree[$user_id] = $user;
         $tree[$user_id]['children'] = $tmp; unset($tmp);
      }
      if(!$tree[$parent_id] ) $tree[$parent_id]  = array();
      $tree[$parent_id]['children'][$user_id] = &$tree[$user_id];
   }
   
   $ret = array();
   foreach($parents as $parent_id )
      $ret[$parent_id] = $tree[$parent_id];
   return $ret;
  }
  
  

  static function clean_children_tree_by_last_node_type($tree, $user_type){
    $me = $tree;
    $children = $me['children']; unset($me['children']);
    if ($children)
    foreach($children as $child_id=>$child) {
      $child = self::clean_children_tree_by_last_node_type($child, $user_type);
      if($child)
        $me['children'][$child_id] = $child;
    }
    
    if (in_array($me['user_type'], $user_type)  || $me['children'])
      return $me;
    return ;
  }
    
  
  function linearize_tree($tree,$depth=0){
    $ret=array();
    foreach($tree as $cat_id=>$children){
      $ret[$cat_id]=array('id'=>$cat_id,'depth'=>$depth);
      if($children['children'])
        $ret += self::linearize_tree($children['children'],$depth+1);
    }return $ret;
}
  
  
    //this implementation only works for postgres 
    //but it's cool !!! useit !
  static function get_children_infos($parent_id, $where=true, $cols=array(), $sort = false){
    if(!$parent_id) return array();
    
    $query_tree = self::get_children_query($parent_id);
    $query = "SELECT * FROM
        ($query_tree) as tmp
        LEFT JOIN `ks_users_list` USING(user_id) 
    ".sql::where($where);
    sql::query($query);
    $users_list = sql::brute_fetch('user_id');
    if($cols)
        $users_list = array_merge_numeric(
            self::get_infos(array_keys($users_list), $cols, array("true"), $sort),
            $users_list
         );
    return $users_list;
  }

  static function get_root_path($user_id){ return 'files/'.crpt($user_id,FLAG_FILE,10);}

  static function get_tmp_path($user_id, $create = false){
    $path  = TMP_PATH.'/'.crpt($user_id,FLAG_FILE,10);
    if($create && !is_dir($path)) files::create_dir($path);
    return $path;
  }

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
    
    // On récupère les tables de types _profile
    self::$users_linear_tables = array_mask($users_type,'%s_profile');
    
    self::$cols_def_horizontal=array(); $tables_xml = yks::$get->tables_xml;
    foreach(self::$users_linear_tables as $k=>$table_name)
        if(!$tables_xml->$table_name) unset(self::$users_linear_tables[$k]);
        
    // On récupère les tables qui étendent Users_list ! /!\ on va avoir des tables en doubles entre les deux.
        

    /*///////////////*/

    $tables = array_merge(self::$users_linear_tables, self::$infos_tables);
    foreach($tables as $table_name) {
        $table_xml = $tables_xml->$table_name;

        self::$cols_def_horizontal = array_merge_recursive(self::$cols_def_horizontal,
            array_fill_keys(array_keys(fields($table_xml)), array($table_name))
        );

        if(!$table_xml->child) continue;
        foreach($table_xml->child as $table_children_name){

            $keys   = fields($tables_xml->$table_children_name, true);
            $fields = fields($tables_xml->$table_children_name);
    
            if(!in_array('user_id',array_values($keys)))
              contine;

            if(array_values($keys) == array('user_id')) {
              self::$users_linear_tables[] = $table_children_name;

              self::$cols_def_horizontal = array_merge_recursive(self::$cols_def_horizontal,
                  array_fill_keys(array_keys($fields), array($table_children_name))
              );
            }
            else {

               //on exclue de la liste des fields les clefs
              $fields = array_diff($fields, $keys);

                //on exclue de la liste dee clées les clées de type user_id
              $keys = array_diff($keys, array('user_id'));
              $index_name = join('-', array_keys($keys));
              self::$cols_def_vertical[$index_name] = compact('index_name', 'keys', 'table_children_name'); //!
              foreach($fields as $k=>$v)
                self::$cols_def_vertical[$k] = &self::$cols_def_vertical[$index_name];

            }
        }
     }
     
    self::$cols_def_horizontal['user_id'] = array('ks_users_list');

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
    $upload_path = $upload_file['path'];

    $tmp_path = users::get_tmp_path(sess::$sess['user_id']); //tmp upload dir
    $tmp_file = "$upload_type.$upload_path";

    if(!preg_match(FILE_MASK,$tmp_file)|| !is_file($tmp_file="$tmp_path/$tmp_file") )
        return false;
    $file_ext = files::ext($tmp_file);
    return compact('tmp_file','file_ext');
  }

}

