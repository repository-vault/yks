<box>
<?
if($query->params_list)
    echo "<box src='/?&href_base;/params'/>";
?>

<box src="/?&href_base;/data"  id='query_data'  theme="&pref.dft;" class="center " caption="<?=$query['query_name']?>" style="width:100%">
<div class="rbx" style="height:40px;display:block">
    <div class="rbx_loader">&#160;</div>
</div>
La requete est en cours d'exécution, veuillez patienter...
</box>


<a href="/?&href_fold;">&lt;&lt; Retourner à la liste des requetes</a>
</box>