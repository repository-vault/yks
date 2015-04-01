<?php

$node_type = $sub1;


$verif_root = array('parent_id IS NULL');
$nodes = cms_node::from_where($verif_root);
$node_types = array_extract($nodes, 'node_type', true);

if(!$node_type)
    $node_type = first($node_types);

$nodes_list = array();
if($node_type) {
    foreach($nodes as $node_id=>$node)
        if($node->node_type == $node_type)
            $nodes_list[$node_id] = $node;

}
 