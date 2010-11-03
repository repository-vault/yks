<box>


<style type="text/css">
a.node_type {
  font-size: 15px;
  margin: 5px 2px;
  display: block;
}

a.node_item, div.root_node p {
  margin: 2px 0 2px 10px;
  font-weight: normal;
  font-size: 11px;
}

div.root_node p a {
  font-weight: normal;  
}

div.root_node p.parent{
  margin-left: 0px;
}

div.root_node p.child{
  margin-left: 5px;
  border-left: 1px solid white;
  padding-left: 5px;
}
</style>


<?

foreach($node_types as $node_type_dsp){
  $str = "<div id='node_type_{$node_type_dsp}' class='root_node'>";
  $str.= "<a class='node_type' href='/?&href;//;$node_type_dsp'>$node_type_dsp</a>";
  if($node_type_dsp != $node_type) {
    $str.= "</div>";
    echo $str;
    continue;
  }

  foreach($nodes_list as $node_id=>$node) {
    $str.="<box id='node_{$node_id}'>
        <a class='node_item' href='/?&href_fold;/node//$node_id'>$node</a></box>";
  }

  $str .= "</div>";
  echo $str;



}

?>


</box>