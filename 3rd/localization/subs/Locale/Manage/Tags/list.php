<?

$project_id = $sub0;
if($project_id){
    if(!isset($projects_tree[$project_id]))
        throw rbx::error("Invalid project");

    $list = array_keys(linearize_tree($projects_tree[$project_id]));
    $projects_list = array_merge($list, array($project_id));
    $verif_projects = array('project_id'=>$projects_list);
}

if($action=="tag_delete")try{
    $tag_id=(int)$_POST['tag_id'];
    $locale_tag = new locale_tag($tag_id);
    $locale_tag->delete();

    jsx::$rbx = false;
}catch(rbx $e){}




$locale_tags_list = locale_tag::from_where($verif_projects);
if($locale_tags_list) ksort($locale_tags_list);
