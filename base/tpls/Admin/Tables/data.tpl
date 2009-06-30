<box theme="as" class="center" caption="<?=$query_infos['query_name']?>">
<?if($ready){?>
<table class='table center'>
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
}

?>

</table>
<?}else{?>
<b>Veuillez specifier les paramètres suivants : <?=join(',',array_keys($query_infos['params']))?></b>
<?}?>
</box>