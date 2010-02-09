<box>

<?

foreach($node_types as $node_type_dsp){
  $str = "<div id='node_type_{$node_type_dsp}'>";
  $str.= "<a href='/?&href;//;$node_type_dsp'>$node_type_dsp</a>";
  if($node_type_dsp != $node_type) {
    $str.= "</div>";
    echo $str;
    continue;
  }

  foreach($nodes_list as $node_id=>$node) {
    $str.="<box id='node_{$node_id}'>
        <a href='/?&href_fold;/node//$node_id'>$node</a></box>";
  }

  $str .= "</div>";
  echo $str;



}

?>


</box>