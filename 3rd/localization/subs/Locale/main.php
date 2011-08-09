<?

 
locale::init();

// Fonction outils pour les locales.
include "functions.locale.php";

tpls::export(array("locale_fold"=>$href_fold));


exyks::$head->title = "Translation Interface";

$lang_root='en-us';

locales_processor::register_mykse_renderer("project_id");
locales_processor::register_mykse_renderer("lang_key");
locales_processor::register_mykse_renderer("locale_tag", "tag_id", "tag_name");

user::register("locale_languages", "ks_users_profile_locale_languages");
user::register("locale_projects", "ks_users_profile_locale_projects");


$locale_languages = sess::$sess->locale_languages;
$locale_projects = sess::$sess->locale_projects;


  //add to recursive childs to enabled projects
  $locale_projects = array_merge($locale_projects, sql_func::get_children($locale_projects,"ks_projects_list","project_id"));
  $verif_projects = array('project_id'=>$locale_projects);


  //filtre les branches innaccessibles
  $traversable = sql_func::filter_parents($locale_projects, "ks_projects_list", "project_id");

  $query = "SELECT project_id as id, parent_id as parent
        FROM `ks_projects_list` ORDER BY project_order";

  $projects_tmp_tree = sql_func::make_tree_query($query );
  $projects_tree = $projects_tmp_tree;
  $projects_tmp_tree = array(PROJECT_ROOT => $projects_tmp_tree[PROJECT_ROOT]);
  
  $projects_dsp = array_intersect_key(linearize_tree($projects_tmp_tree), array_flip($traversable));
  foreach($projects_dsp as $project_id=>&$project_infos)
  if(!in_array($project_id,$locale_projects))
    $project_infos['disabled']='disabled';

 