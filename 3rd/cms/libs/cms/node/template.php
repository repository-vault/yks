<?php

class cms_node_template extends cms_node {
  var $editables = array("template_xsl");

  function save($data){
    $data['template_xsl'] = specialchars_decode($data['template_xsl']);

    if(!$data['parent_id'])
        unset($data['parent_id']);


    return parent::save($data);
  }

}