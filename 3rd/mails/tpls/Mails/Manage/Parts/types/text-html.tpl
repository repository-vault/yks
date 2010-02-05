<?include '_top.tpl'?>


<p>Utilisez ce type pour rédiger un mail au format HTML</p>
<ks_form ks_action="part_update" submit="Enregistrer">
    <textarea name="part_contents" class="wyzzie"><?=specialchars_encode($part_infos['part_contents']).XML_EMPTY?></textarea>
</ks_form>



<?include '_bottom.tpl'?>