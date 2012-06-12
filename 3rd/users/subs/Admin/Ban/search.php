<?php
$sort_fields = array(
   "Ip"    => 'failed_ip',
   "Login" => 'failed_login',
   "Count" => 'count',
);

if($action == 'search_failed_auth'){
  
  try{
      if($_POST['search_count_start'])
        if($_POST['search_count_start'] == sql::clean($_POST['search_count_start']))
          if(!is_numeric($_POST['search_count_start']))
            throw rbx::error("Erreur le count minimal n'est pas valide");
            
      if($_POST['search_count_end'])
        if($_POST['search_count_end'] == sql::clean($_POST['search_count_end']))
          if(!is_numeric($_POST['search_count_end']))
            throw rbx::error("Erreur le count maximal n'est pas valide");
      
      if($_POST['sort_by'] != "" && !array_key_exists($_POST['sort_by'], $sort_fields))
        throw rbx::error("Erreur le trie n'est pas valide");
    }
    catch(rbx $e){return;}
  
  
  $data = array(
    "search_login" => $_POST['search_login'],
    "search_ip" => $_POST['search_ip'],
    "search_count_start" => $_POST['search_count_start'],
    "search_count_end" => $_POST['search_count_end'],
    "sort_by" => $sort_fields[$_POST['sort_by']],
    "sort_way" => $_POST['sort_way'],
  );
  
  sess::store($search_flag, $data);

  jsx::js_eval("Jsx.open('/?$href_fold/list', 'list_failed', this)"); 
}