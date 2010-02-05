<box theme="fieldset" caption="&addr_type.<?=$addr_infos['addr_type']?>;" class="addr">
    <?=dsp::addr($addr_infos,'$addr_lastname<br/>$addr_field1<br/>$addr_field2<br/>$addr_zipcode $addr_city<br/>&country_code.$country_code;')?>
<br/>
<div  class="float_right">
    <button href="<?="/?$href_fold//$addr_id/manage"?>" target="addr_edit">Modifier</button>
</div>
</box>

