<box theme="&pref.dft;" class="center " caption="<?=$query['query_name']?>" options="reload" style="width:100%">
<p>
<?=$query->query_descr?>
</p>
<button ext="/?&href;//download">Export Excel</button>


<toggler caption="Détails de la query" class="closed">
    <textarea class="fill" style="height:400px"><?=specialchars_encode($query->sql_query)?></textarea>
</toggler>
<?if($query->ready) {?>
  <table class='table' style='width:100%'>

    <?if($query->data_headers):?>
    <tr class='line_head'>
      <? foreach($query->data_headers as $col_key=>$col_name): ?>
        <th><?=$col_name?></th>
      <? endforeach; ?>
    </tr>
    <?endif?>

    <? foreach($query->data_results as $line): ?>
      <tr class='line_pair'>
        <? foreach($query->data_headers as $col_key=>$col_name): ?>
          <td><?= $line[$col_key] ?></td>
        <? endforeach; ?>
      </tr>
    <? endforeach; ?>

    <? if(!$query->data_results): ?>
      <tfail>No data</tfail>
    <? endif; ?>

  </table>
<?  } else {?>
<div class="rbx" style="height:40px;display:block">
    <div class="rbx_loader">&#160;</div>
</div>
Parametrage de la requete en cours<blink>…</blink>


<b>Veuillez specifier les paramètres suivants : <?=join(',',array_extract($query->params_list, "param_title"))?></b>
<?}?>
</box>