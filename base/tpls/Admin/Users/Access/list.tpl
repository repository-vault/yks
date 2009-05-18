<box theme="&pref.dft;" caption="Gestion des zones d'accès" id="access_zone_box" class="float_left" style="width:620px">

<table class='table' id="access_zone_list">
    <tr class='line_head'>
        <th>Zone</th>
        <th>Zone parente</th>
        <th>Description</th>
        <th>Actions</th>
    </tr>
<?

foreach($access_zones as $access_zone=>$zone_infos){
    echo "<tr class='line_pair'>
        <td>{$zone_infos['access_zone']}</td>
        <td>{$zone_infos['access_zone_parent']}</td>
        <td>{$zone_infos['zone_descr']}</td>
        <td><div class='button_trash' onclick=\"zone_delete('{$zone_infos['access_zone']}')\"> </div></td>
    </tr>";
}
?>
</table>

<script>
function zone_delete(access_zone){
  Jsx.action({
	ks_action:'zone_delete',
        access_zone:access_zone
  },$('access_zone_list'),'Supprimer');
}
</script>

</box>
