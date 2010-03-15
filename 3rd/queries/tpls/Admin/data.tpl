<box theme="&pref.dft;" class="center " caption="<?=$query['query_name']?>" options="reload" style="width:100%">


<toggler caption="Détails de la query" class="closed">
    <textarea class="fill" style="height:400px"><?=specialchars_encode($query->sql_query)?></textarea>
</toggler>
<?if($query->ready)
    $query->print_data();
else {?>
<div class="rbx" style="height:40px;display:block">
    <div class="rbx_loader">&#160;</div>
</div>
Parametrage de la requete en cours<blink>…</blink>

<b>Veuillez specifier les paramètres suivants : <?=join(',',array_keys($query->params_list))?></b>
<?}?>
</box>