<?php

  $verif_project=array('project_id'=>$locale_projects);
  $tags_list = locale_tag::from_where($verif_project);

  $tags_tree = array_extract($tags_list, 'parent_tag');


    ///cre un arbre de racine 'null'
$tags_tree=make_tree($tags_tree);
$tags_tree_splat = linearize_tree($tags_tree[null]);
unset($tags_tree_splat[null]); //remove null root



$tags_list = array_sort($tags_list, array_keys($tags_tree_splat));

