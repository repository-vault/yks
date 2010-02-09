<box theme="&pref.dft;" caption="Gestion des coordonnees" options="modal,fly,close,reload">
Utilisateur : <?=$user_infos['user_name']?>


<ks_form ks_action="addr_manage" style="width:400px;margin-top:20px;">
<field title="Type"><select name="addr_type"><?=dsp::dd("addr_type",$addr_infos['addr_type'])?></select></field>
<hr/>
<?
$cc=dsp::dd($countries_list, array(
    'mykse'=>'country_code',
    'selected'=>$addr_infos['country_code'],
));
?>
<?=<<<EOS
<field title="Nom" type="user_name" name="addr_lastname" value="{$addr_infos['addr_lastname']}"/>
<field title="Prénom" type="user_name" name="addr_firstname"  value="{$addr_infos['addr_firstname']}"/>

<field title="Adresse" type="addr_text" name="addr_field1"  value="{$addr_infos['addr_field1']}"/>
<field title="Adresse (complement)" type="addr_text" name="addr_field2"  value="{$addr_infos['addr_field2']}"/>
<field title="Code postal" type="addr_zipcode"  value="{$addr_infos['addr_zipcode']}"/>
<field title="Ville" type="addr_city"  value="{$addr_infos['addr_city']}"/>
<field title="Pays"><select name="country_code"><option value=''>--Select--</option>$cc</select></field>


<field title="Téléphone" type="addr_phone"  value="{$addr_infos['addr_phone']}"/>
<field title="Fax" type="addr_phone" name="addr_fax"  value="{$addr_infos['addr_fax']}"/>

EOS;
?>

<div style="text-align:center">
<?if($addr_id){?>
    <button name="ks_action[addr_delete]" confirm="this.title" class="float_left">Supprimer</button>
<?}?>

    <button class="float_right">Enregistrer</button>
    <button name="ks_action[addr_duplicate]">Dupliquer</button>
</div>

</ks_form>

</box>