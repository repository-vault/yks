<box theme="&pref.dft;" caption="Liste des requetes"  style='width:1000px' class='center'>


<table class='center' style='width:100%'>
<tr class='line_head'>
    <th style='width:25px'>#</th>
    <th style='width:200px'>Nom</th>
    <th style='width:100px'>Exporter</th>
    <th >Description</th>
    <th >Actions</th>
</tr>

<?

foreach($queries_list as $query_id=>$query_infos){
    $extras= "";
    if($query_infos['has_parameters'])
        $extras .=" Requete paramétrée";
    $export = "<a href='/?&href_fold;//$query_id/data//1' class='ext'>Export.xls</a>";

    $actions = '';
    $actions .="<a href='/?&href_fold;//$query_id/Manage'>Modifier</a>";
    $actions .="<a onclick=\"Jsx.action({ks_action:'query_trash',query_id:$query_id}, this, this.innerHTML)\">Supprimer</a>";

    echo "<tr class='line_pair'>
        <td class='id'>$query_id</td>
        <td><a href='/?&href_fold;//$query_id/table'>{$query_infos['query_name']}</a></td>
        <td>$export</td>
        <td>{$query_infos['query_descr']} $extras </td>
        <td>$actions</td>

    </tr>";

} if(!$queries_list)
    echo "<tfail>Aucune requete n'a été définie</tfail>";




?>
</table>

<button href="/?&href_fold;/Manage">Nouvelle requête</button>




</box>