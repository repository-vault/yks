<?

class cms_node_template extends cms_node {
  var $editables = array("template_xsl");

  function save($data){
    $data['template_xsl'] = specialchars_decode($data['template_xsl']);


    return parent::save($data);
  }

}