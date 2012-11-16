<?
/** Droit : 'yks:yks_query' 
 *   ACCESS : voir la liste des queries et pouvoir les executer
 *   MODIFY : ajouter/modifier les queries
 *   ADMIN  : Voir toutes les queries sans restriction de visibilité
 */
if(!auth::verif("yks_query", "access"))
  abort(403);


tpls::page_def("list");
tpls::export(array('queries_fold'=>$subs_fold));


$query_id = (int)$sub0;
if($query_id) try {

    $query = new query_db($query_id);

} catch(Exception $e){}

