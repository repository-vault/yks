<?php


$node_id  = (int)$sub0;

tpls::export(compact('node_id'));
$node = cms_node::instanciate($node_id);

$children = $node->children;
