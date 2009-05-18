<?include '_top.tpl'?>


<p>
Ce type est utilisé pour composer un mail en plusieurs parties d'égales importances, alternatives. Le cas le plus courant est l'utilisation d'une sous partie textuelle (text/plain) et d'une autre sous partie formatée en html (text/html). Les clients mails ne supportant pas l'affichage des mails au format html (ou sous l'autorité d'une politique de sécurité restrictive) afficheront alors automatiquement le contenu textuel alternatif.</p>


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