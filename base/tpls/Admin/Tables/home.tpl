<box theme="&pref.dft;" caption="Liste des requetes"  style='width:1000px' class='center'>


<table class='center'>
<tr class='line_head'>
    <th style='width:25px'>#</th>
    <th style='width:200px'>Nom</th>
    <th style='width:100px'>Exporter</th>
    <th >Description</th>
    <th >Extras</th>
</tr>

<?

foreach($queries_list as $query_id=>$query_infos){
    $extras= "";
    if($query_infos['has_parameters'])
        $extras .=" Requete paramétrée";
    $export = "<a href='/?&href_fold;//$query_id/data//1' class='ext'>Export.xls</a>";

    echo "<tr class='line_pair'>
        <td class='id'>$query_id</td>
        <td><a href='/?&href_fold;//$query_id/table'>{$query_infos['query_name']}</a></td>
        <td>$export</td>
        <td>{$query_infos['query_descr']}</td>
        <td>$extras</td>
    </tr>";


} if(!$queries_list)
    echo "<tfail>Aucune requete n'a été définie</tfail>";




?>
</table>

<button href="/?&href_fold;/Manage">Nouvelle requête</button>




</box>