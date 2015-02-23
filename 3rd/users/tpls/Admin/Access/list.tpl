<box theme="&pref.dft;" caption="Gestion des zones d'accès" id="access_zone_box" class="float_left" style="width:900px">

<button href="?&href_fold;/manage" target='manage_access'>Create new access area</button>

<table class='table' id="access_zone_list">
    <tr class='line_head'>
        <th>Zone</th>
        <th>Zone parente</th>
        <th>Description</th>
        <th>Actions</th>
    </tr>
<?

foreach($access_zones as $access_zone=>$zone_infos){
    $actions = array();
    $actions[] = "<div class='user_icon icon_zone_trash' onclick=\"zone_delete('{$zone_infos['access_zone']}')\">&#160;</div>";
    $actions[] = "<a target='::modal' href='/?&href_fold;/manage//$access_zone'>Modifier</a>";

    echo "<tr class='line_pair'>
        <td>{$zone_infos['access_zone']}</td>
        <td>{$zone_infos['access_zone_parent']}</td>
        <td>{$zone_infos['zone_descr']}</td>
        <td>".join(' - ', $actions)."</td>
    </tr>";
}
?>
</table>
<button href="?&href_fold;/manage" target='manage_access'>Create new access area</button>

<script>
function zone_delete(access_zone){
  Jsx.action({
	ks_action:'zone_delete',
        access_zone:access_zone
  },$('access_zone_list'),'Supprimer');
}
</script>

</box>
