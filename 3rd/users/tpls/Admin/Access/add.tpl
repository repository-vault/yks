
<box theme="&pref.dft;" caption="Creation d'une nouvelle zone" class="float_right">
    <ks_form ks_action="zone_add" submit="Creer la zone">
        <field type="access_zone" title="Nom de la zone"/>
        <field title="Zone parente" >
            <select name="access_parent">
            <option value=''>-- Zone racine--</option>
            <?=dsp::dd($access_zones, array('col'=>'access_zone'))?>
            </select>
        </field>
        <field type="text" title="Description" name="zone_descr" style="height:150px"/>
    </ks_form>

</box>
