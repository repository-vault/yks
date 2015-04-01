<box theme="&pref.dft;" caption="Liste des items">
<style>
.tags_list {
  font-size: 10px;
}
</style>

<table class='table' style='width:1000px'>
  <tr class='line_head'>
    <th>Item key</th>
    <th>Tags</th>
    <th>Actions</th>
  </tr>
<?php

if($items_list)
foreach($items_list as $item){
  $actions = array();
  $actions[]  = "<span onclick=\"Jsx.action(\$H({ks_action:'item_delete',item_key:'{$item['item_key']}'}), this)\">delete</span>";
  $actions[] = "<a href='/?&href_fold;//".base64_encode($item['item_key'])."' target='manage_version'>Edit</a>";  
  // Manage/Items
  echo "<tr class='line_pair'>
    <td>{$item['item_key']}</td>
    <td><span class='tags_list'>".join("<br/>",$item->tags)."</span></td>
    <td>".join(' - ',$actions)."</td>
  </tr>";
} if(!$items_list)
    echo "<tfail>No items</tfail>";

?>

</table>
</box>