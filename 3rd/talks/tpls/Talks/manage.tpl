<box theme="&pref.dft;" caption="Editer" options="fly,close,reload" style="width:600px;height:400px">
<ks_form ks_action="talk_manage" submit="Save">

<?

foreach($fields as $field)
    echo "<textarea class='bbcoder' name='talk_content' style='width:100%;height:300px'>{$talk->talk_content}&XML_EMPTY;</textarea>";

?>

</ks_form>
</box>