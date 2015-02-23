<box theme="&pref.dft;" caption="Gestion de zone" class="float_right" options="reload, close, fly, modal">
    <ks_form ks_action="zone_manage" submit="Creer la zone">
        <field type="access_zone" title="Nom de la zone" value="<?=$zone['access_zone']?>"/>
        <field title="Zone parente" >
            <select name="access_zone_parent">
            <option value=''>-- Zone racine--</option>
                <?=dsp::dd($access_zones, array('col'=>'access_zone', 'selected' => $zone['access_zone_parent']))?>
            </select>
        </field>
        <field type="textarea"   title="Description" name="zone_descr" style="width:400px;height:300px"><?=$zone['zone_descr']?>&XML_EMPTY;</field>
    </ks_form>

</box>
