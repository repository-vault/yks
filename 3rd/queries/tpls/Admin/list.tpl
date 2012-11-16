<box theme="&pref.dft;" caption="Liste des requetes"  style='width:1000px' class='center'>


<table class='center' style='width:100%'>
<tr class='line_head'>
    <th style='width:25px'>#</th>
    <th style='width:200px'>Nom</th>
    <th>Description</th>
    <? if(auth::verif("yks_query","admin")) { ?>
    <th>Visibility</th>
    <? } ?>
    <th style='width:200px'>Actions</th>
</tr>

<?

foreach($queries_list as $query_id=>$query_infos){

    $extras= "";
    if($query_infos['has_parameters'])
        $extras .=" Requete paramétrée";
    

    $actions = array();
    $actions[] = "<a href='/?&href_fold;//$query_id/data//1' class='ext'>Export</a>";    
    if(auth::verif("yks_query","action")) {
      $actions[]="<a href='/?&href_fold;//$query_id/Manage'>Modifier</a>";
      $actions[]="<a onclick=\"Jsx.action({ks_action:'query_trash',query_id:$query_id}, this, this.innerHTML)\">Supprimer</a>";
    }


    $dsp = "<tr class='line_pair'>
        <td class='id'>$query_id</td>
        <td><a href='/?&href_fold;//$query_id/table'>{$query_infos['query_name']}</a></td>
        <td>{$query_infos['query_descr']} $extras </td>";
        if(auth::verif("yks_query","admin"))
          $dsp .= "<td>{$query_infos['query_visibility']}</td>";
    $dsp .= "<td>".join(' - ',$actions)."</td>
    </tr>";
    
    echo $dsp;

} if(!$queries_list)
    echo "<tfail>Aucune requete n'a été définie</tfail>";


?>
</table>

<? if(auth::verif("yks_query","action")) { ?>
<button href="/?&href_fold;/Manage">Nouvelle requête</button>
<? } ?>




</box>