<box>
<domready>
Jsx.open('/?&href_fold;//&node_id;/details', 'node_infos', this);
</domready>
<p class="parent">
  <a href='/?&href;//<?=$node_id?>'><?=$node?></a>
</p>
<? foreach($children as $node_id=>$node): ?>
 <p class="child">
  <box>
    <a href='/?&href;//<?=$node_id?>'><?=$node?></a>
  </box>
 </p>
<? endforeach; ?>

</box>