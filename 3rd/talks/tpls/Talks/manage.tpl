<box theme="&pref.dft;" caption="Editer" options="modal,fly,close" style="width:600px;height:400px">


<?

$fields = array('talk_content');
foreach($fields as $field)
    echo "<textarea class='bbcoder' style='width:100%;height:300px'>{$talk->talk_content}&XML_EMPTY;</textarea>";

?>

</box>