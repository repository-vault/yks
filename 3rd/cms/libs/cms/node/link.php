<?php

class cms_node_link extends cms_node {
  var $editables = array("link_dest_node");

  function get_children(){
    $verif_related = array('node_id'=> $this->link_dest_node);
    $verif_parent  = array('parent_id'=> $this->node_id);
    return   array_merge_numeric(
        cms_node::from_where($verif_parent),
        cms_node::from_where($verif_related)
    ) ;
    
  }
}