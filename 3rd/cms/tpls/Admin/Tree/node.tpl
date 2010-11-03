<box>
<domready>
Jsx.open('/?&href_fold;//&node_id;/details', 'node_infos', this);
</domready>
<p class="parent"><?=$node?></p>
<? foreach($children as $node_id=>$node): ?>
 <p class="child">
  <box>
    <a href='/?&href;//<?=$node_id?>'><?=$node?></a>
  </box>
 </p>
<? endforeach; ?>

</box>