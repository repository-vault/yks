<box theme="&pref.dft;" caption="Liste des produits" id="products_list">

<table class='table'>
  <tr class='line_head'>
    <th>#</th>
    <th>Product name</th>
    <th>Product Price</th>
    <th>Category</th>
    <th>Relation type</th>
    <th>Action</th>
  </tr>

<?
foreach($products_infos as $product_id=>$product_infos){
    $actions = "";
    $actions .="<img onclick=\"Jsx.action({ks_action:'product_delete',product_id:$product_id},this,this.title)\" title='Supprimer ce produit' src='&COMMONS_URL;/css/Yks/icons/trash_24'/>";
    $actions .="<a href='/?&href_base;/Manage//$product_id' target='product_manage'><img alt='details' src='&COMMONS_URL;/css/Yks/icons/edit_24'/></a>";

  echo "<tr class='line_pair'>
    <td>[$product_id] {$product_infos['product_ref']}</td>
    <td>".dsp::element_truncate($product_infos['product_name'],20,"span")."</td>
    <td>{$product_infos['product_price']}</td>
    <td>".join(',', $product_infos['product_categs'])."</td>
    <td>{$product_infos['product_relation_type']}</td>
    <td>$actions</td>
  </tr>";
}if(!$products_list) echo "<tfail>Aucun produit à ce niveau</tfail>";

?>

</table>
</box>