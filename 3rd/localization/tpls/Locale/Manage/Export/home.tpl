<box caption="Export des fichiers de traduction" theme="&pref.dft;" style="width:450px" options="modal,fly,close">
<ks_form ks_action="generate_lang" submit="Generer">

<?/*
<select name="project_id" size="<?=count($projects_dsp)?>">
<?=dsp::dd($projects_dsp,array('col'=>'project_name','truncate'=>20,'pad'=>"|&#160;&#160;&#160;"))?>
</select>
*/?>

Veuillez choisir la langue des fichiers à exporter : <br/>
<?
$langs_nb=count($locale_languages);
echo "<select name='lang_key'>";
echo dsp::dd($locale_languages,array('mykse'=>'lang_key'));
echo "</select>";
?>

</ks_form>

</box>