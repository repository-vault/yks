<?php

$verif_root = array('node_type'=>'article');
$nodes = cms_node::from_where($verif_root);
$node_types = array_extract($nodes, 'node_type', true);

$map = array();
$mask = '$lang_key/$node_title';
foreach($nodes as $node){
    $data = array(
        'node_title' => $node->node_title,
        'lang_key'   => $node->lang_key,
        'node_id'    => $node->node_id,
    );

    $map[$node->node_id] =  str_evaluate($mask, $data);

}
print_r($map);  

print_r($nodes);die;