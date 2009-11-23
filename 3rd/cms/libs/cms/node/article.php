<?

class cms_node_article extends cms_node {
  var $editables = array("node_contents");


  function __toString(){
    return parent::__toString($this->lang_key);
  }

  function save($data){
    $data['node_contents'] = specialchars_decode($data['node_contents']);


    return parent::save($data);
  }


}