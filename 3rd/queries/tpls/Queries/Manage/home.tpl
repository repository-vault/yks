<box theme="&pref.dft;" caption="&query_action;" id="query_manager" style="width:100%">

<ks_form ks_action="&query_action;" submit="Save">

<fields>
    <field type="title" name="query_name" title="Query name" value="<?=$query['query_name']?>"/>
    <p><a href="/?&href_fold;/params" target="query_params">Voir la liste des parametres disponibles</a></p>
    <field type="textarea" name="query_def" title="Query contents" style="float:right;clear:both;height:300px;width:100%;padding:-3px;"><?=$query['query_def']?></field>
    <field type="text" name="query_descr" title="Query description" style="height:200px"><?=$query['query_descr']?></field>

</fields>

</ks_form>


<a class='clear_right float_right' style='margin-top:20px' href='/?&queries_fold;'>Retour à la liste des requetes</a>
</box>

