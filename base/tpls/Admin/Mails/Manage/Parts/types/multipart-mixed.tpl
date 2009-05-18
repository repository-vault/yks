<?include '_top.tpl'?>


<p>
Ce type est utilisé pour définir un contenu complexe composé de multiples parties de types différents. Le cas le plus courant est la définition d'un message HTML complexe utilisant des images intégrées (embeded).</p>

<p>Sous parties présentes : </p>
<?

if($part_children) foreach($part_children as $part_id=>$part_infos){
  echo "<box id='mail_part_$part_id' src='/?$href_fold//$part_id;1'/>";
}else echo "(aucune)";


?>

<fieldset style="width:300px"><legend>Ajouter un sous élément ici</legend>
<ks_form ks_action="part_add" submit="Ajouter">
    <field name="content-type" type="mime_content_type" title="Type de contenu"/>
</ks_form>
</fieldset>

<?include '_bottom.tpl'?>