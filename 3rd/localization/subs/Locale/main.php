<?

locale::init();

// Fonction outils pour les locales.
include "functions.locale.php";

tpls::export(array("locale_fold"=>$href_fold));


exyks::$head->title = "Translation Interface";

$lang_root='en-us_1'; // Domaine 1 par defaut !!

locales_processor::register_mykse_renderer("project_id");
locales_processor::register_mykse_renderer("lang_key");
locales_processor::register_mykse_renderer("locale_tag", "tag_id", "tag_name");


$locale_languages = array_extract(sess::$sess->lang_key, 'lang_key');
$locale_projects  = array_extract(sess::$sess->project_id, 'project_id');


$locale_domain_id = 'locale_domain_id';

sql::select(locale::sql_table, array(locale::sql_key => $locale_languages), $locale_domain_id, 'GROUP BY '.$locale_domain_id);
$locale_domains = sql::brute_fetch($locale_domain_id, $locale_domain_id);
sess::$sess->locale_domains = $locale_domains;


sql::select('ks_locale_domains_list', array($locale_domain_id => array_filter($locale_domains)));
$locale_domains_list = sql::brute_fetch($locale_domain_id);

$lang_infos = locale::languages_infos($locale_languages);

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




