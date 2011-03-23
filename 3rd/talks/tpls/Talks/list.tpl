<box>
<p>Pages <?=$pages?></p>
<table class='table' style="width:100%">
<tr class='line_head'>
    <th>#</th>
    <th>Nom</th>
    <th>Actions</th>
</tr>
<?
foreach($children as $node_id=>$node){
    $actions = array();
    $actions []= "<a onclick=\"Jsx.action({ks_action:'delete',talk_id:$node_id}, this, this.innerHTML)\" class='icon icon_delete'>Delete</a>";
    $actions []= "<a href='/?&href_fold;//$node_id/manage' target='talk_manage'>Editer</a>";

  echo "<tr class='line_pair'>
        <td>$node_id</td>
        <td><a href='/?&href_fold;//$node_id' target='talk_home'>$node</a></td>
        <td>".join(' - ', $actions)."</td>
    </tr>";
}
if(!$children)
    echo  "<tfail>Aucune donnée</tfail>";
?>
</table>
<p>Pages <?=$pages?></p>

</box>