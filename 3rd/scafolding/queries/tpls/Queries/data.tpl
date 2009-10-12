<box theme="&pref.dft;" class="center " caption="<?=$query['query_name']?>" options="reload" style="width:100%">


<toggler caption="Détails de la query" class="closed">
    <textarea class="fill" style="height:400px"><?=specialchars_encode($sql_query)?></textarea>
</toggler>
<?if($query->ready){?>

<table class='table center' style="width:100%">
<tr class='line_head'>
<?
foreach($cols as $col_key=>$tmp)
    echo "<th>$col_key</th>";
?>
</tr>

<?
foreach($data as $line){
  echo "<tr class='line_pair'>";
  foreach($cols as $col_key=>$col_infos){
    echo "<td>{$line[$col_key]}</td>";
  }
  echo "</tr>";
}if(!$data)
    echo "<tfail>La requete n'a retourné aucun resultat</tfail>";

?>

</table>
<?}else{?>
<div class="rbx" style="height:40px;display:block">
    <div class="rbx_loader">&#160;</div>
</div>
Parametrage de la requete en cours<blink>…</blink>

<b>Veuillez specifier les paramètres suivants : <?=join(',',array_keys($query->params_list))?></b>
<?}?>
</box>