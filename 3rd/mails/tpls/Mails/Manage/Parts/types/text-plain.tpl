<?include '_top.tpl'?>

<p>C'est le plus simple des types de contenu, vous ne pouvez y enregistrer qu'un simple texte</p>

<ks_form ks_action="part_update" submit="Enregistrer">
    <textarea name="part_contents"><?=$part_infos['part_contents'].XML_EMPTY?></textarea>
</ks_form>


<?include '_bottom.tpl'?>