<box theme="&pref.dft;" caption="Liste des items">
<table class='table' style='width:600px'>
<tr class='line_head'>
<th>Item key</th>
<th>Actions</th>
</tr>
<?

foreach($items_list as $item){
  $actions  = "<span onclick=\"Jsx.action(\$H({ks_action:'item_delete',item_key:'{$item['item_key']}'}), this)\">delete</span>";
  echo "<tr class='line_pair'>
    <td>{$item['item_key']}</td>
    <td>{$actions}</td>

  </tr>";
} if(!$items_list)
    echo "<tfail>No items</tfail>";

?>

</table>
</box>