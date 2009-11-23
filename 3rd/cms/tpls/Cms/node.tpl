<box>
<domready>
Jsx.open('/?&href_fold;//&node_id;/details', 'node_infos', this);
</domready>
<p><?=$node?></p>
<?
if($children) foreach($children as $node_id=>$node){
 echo "<p><box><a href='/?&href;//$node_id'>$node</a></box></p>";
} else {
  echo "<p>--no children--</p>";
}

?>

</box>