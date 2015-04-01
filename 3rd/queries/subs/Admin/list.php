<?php
if($action == "query_trash") try {
    $query_id = (int)$_POST['query_id'];
    $query = query::instanciate($query_id);
    $query->trash();
    jsx::$rbx = false;
} catch(rbx $e){}


sql::select("ks_queries_list",sql::true,'*','ORDER BY query_id');
$queries_list = sql::brute_fetch('query_id');

// TODO : Remplacer ça par un filtre SQL quand la visibilité sera une table à part (Quentin)
if(!auth::verif("yks_query","admin")) // yks_query admin n'a pas de restriction
  foreach($queries_list as $query_id=>$query_infos){
    $visibility = array_filter(explode(',',$query_infos['query_visibility']));
    $visibility = array_map('trim',$visibility);
    $can_view = array_intersect($visibility ,sess::$sess->users_tree);
    // Heritage depuis l'arbre de visibilité ou créateur de la requête.
    if(!($can_view || $query_infos['query_creator'] == sess::$sess->user_id ))
      unset($queries_list[$query_id]);
  }
