<?

/**
    Bon, on va avoir un peu de travail à faire ici, puisque les vues ne PEUVENT pas êtres managées (drop&create) comme bon me semble, les liaisons/dependances entre elles imposent un ordre.

    Cette premiere partie de pavé de truc est un machin qui resoud et ordonne des dependances, à mutualiser please.

*/
   rbx::title("Step 1 : resolving dependencies");

$views_list = array();

    $search  = "#`([a-z0-9_-]+)`#i";

    foreach($views_xml->view as $view_xml){
        $view_name = (string)$view_xml['name'];
        $def = (string) $view_xml->def;
        preg_match_all($search, $def, $out);
        $dependencies = array_unique($out[1]);
        $views_list[$view_name]['name'] = $view_name;
        $views_list[$view_name]['xml'] = $view_xml;
        $views_list[$view_name]['dependencies'] = $dependencies;
    }


//Step 1 : building recursive tree
$build_list=array();
foreach($views_list as $view_name=>$view_infos){

	$dependencies = $view_infos['dependencies'];
	unset($view_infos['dependencies']);

	$build_list[$view_name]=array_merge(
		$build_list[$view_name]?$build_list[$view_name]:array(),
		$view_infos
	);

	foreach($dependencies as $dependency)
		 $build_list[$view_name]['dependencies'][$dependency]= &$build_list[$dependency];


}

	//Step 2 : retrieving maximum depths for a script
$build_depth=array();

function scan_depth($infos,$depth=0,$path=array()){
	global $build_depth;
	$name=$infos['name'];
	if(in_array($name,$path))return;
	$build_depth[$name]=max($build_depth[$name],$depth);
	if($infos['dependencies'])
	foreach($infos['dependencies'] as $infos)
		scan_depth($infos,$depth+1,array_merge($path,array($name)));
}

foreach($build_list as $name=>$infos) scan_depth($infos);

	//Step 3 : Reversing depth && re-ordering base list
arsort($build_depth);
$views_list=array_sort($views_list,array_keys($build_depth));


