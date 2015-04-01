<box theme='&pref.dft;' caption="Gestion des parametres" options="fly,close,reload">
<fields caption="Liste des parametres existants">
<table class='table' style='width:100%'>
<tr class='line_head'>
  <th>Clée</th>
  <th>Null</th>
  <th>Multi</th>
  <th>Type</th>
  <th>Actions</th>
</tr>

<?php
foreach($params_list as $param_id=>$param) {
  $actions = "";
  $actions .= "<img alt='trash_icon' src='/css/Yks/icons/trash_24' onclick=\"Jsx.action({ks_action:'param_trash', param_id:$param_id},this)\"/>";

  echo "<tr class='line_pair'>
    <td>{$param['param_key']}</td>
    <td>&bool.{$param['param_nullable']};</td>
    <td>&bool.{$param['param_multiple']};</td>
    <td>{$param['param_type']}</td>
    <td class='align_center'>$actions</td>
  </tr>";

} if(!$params_list) echo "<tfail>There are no defined parameteres </tfail>";
?>
</table>
</fields>

<br/><br/>

<fields caption="Ajout d'un nouveau parametre">

    <ks_form ks_action="params_add" submit="Creation du parametre">
        <field type="param_key" title="Clée du parametre"/>

        <field type="string" name="param_descr" title="Description"/>
<hr/>

        <field type="bool" name="param_nullable" title="Nullable"/>
        <field type="bool" name="param_multiple" title="Multiple"/>

        <field title="Type de parametre">
                <select name='param_type'>&select.choose;
                    <?=dsp::dd("param_type")?>
                </select>
        </field>

        <box id='param_specs'/>
    </ks_form>
</fields>
<domready>
$N('param_type').addEvent('change', function(){
    var type = this.value;
    if(type) Jsx.open('/?&href_base;/params//'+ type, 'param_specs', this);
});

</domready>
</box>