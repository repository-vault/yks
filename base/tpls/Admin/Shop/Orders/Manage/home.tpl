

<box theme="&pref.dft;" caption="Manage order <?=$order_id?>" options=" reload">
<p class="align_right"><a href="/?&orders_list;">Retourner à la liste des commandes</a></p>


<ks_form ks_action="order_manage"  >
<fields caption="Informations sur la commande">
<field title="Montant de la commande" type="order_value" value="<?=$order_dsp['order_value']?>" />
<field title="Date de la commande" type="time" name="order_end" value="<?=$order_dsp['order_end']?>"/>

<field title="Status de la commande">
<select name="order_status">
    <?=dsp::dd("order_status",$order_dsp['order_status'])?>
</select>
</field>
<field type="text" name="order_comment" style="height:150px">
    <?=XML_EMPTY.$order_dsp['order_comment']?>
</field>
</fields>
<p><span>Client</span><var><a target='user_manage' href='/?/Admin/Users//<?=$distributor_id?>/Manage'><?=$distributor_infos['user_name']?></a></var></p>
<p><span>&order_start;</span><var><?=$order_dsp['order_start']?></var></p>
<p><span>&order_end;</span><var><?=$order_dsp['order_end']?></var></p>


<a ext="<?="/?/XsOrder/Shop/proforma//$order_id"?>" class="float_left"><title>Telecharger la proforma</title></a>
<button class="float_right">Modifier</button>

</ks_form>



</box>