<box theme="&pref.dft;" caption="Configuration SQL">

<ks_form ks_action="manage_sql">
    <field title="Driver SQL">
        <select name='sql_driver'>
            <option value=''>--Aucun--</option>
            <?=dsp::dd($sql_drivers, (string)$config->sql['driver'])?>
        </select>
    </field>
</ks_form>

    <?=$grid_list->render()?>

</box>