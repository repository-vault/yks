<box theme="&pref.dft;" caption="Editer" options="fly,close,reload,resize" style="width:400px;">
<ks_form ks_action="talk_manage">

    <field type="title" name="talk_title" title="Titre" value="<?=$talk->talk_title?>"/>
    <field type="time" name="talk_date" title="Date" value="<?=dsp::datef($talk->talk_date, DATETIME_MASK)?>"/>

<button>Save</button>
<clear/>
<?

foreach($fields as $field)
    echo "<textarea class='bbcoder glued' glued='.inner' name='talk_content' style='resize:none;height:100px;width:100%;'>{$talk->talk_content}&XML_EMPTY;</textarea>";

?>

</ks_form>
</box>