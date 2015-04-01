
<div id="ivs_header">
<p>
<?php
if(sess::$sess['user_name']){
    echo "Bonjour ".sess::$sess['user_name'];
    echo " - <span onclick=\"Jsx.action({ks_action:'deco'},this,true)\">Se deconnecter <img src='&COMMONS_URL;/imgs/Ivs/exit.png' alt='exit'/></span>";
}
?>
</p>


<p>
<a href='/?/Trad/Manage/Export' target='trad_export'>Exporter</a> - <a href="/?/Trad/Manage/Items" target="item_manage">Ajouter un item</a>
</p>
</div>

<div class="ivs_body">
