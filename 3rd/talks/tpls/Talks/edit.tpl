
<?php
echo <<<EOS

<ks_form ks_action="talk_manage" submit="Enregistrer">
<fields>
    <field type="title" title="Titre" value="{$talk['talk_title']}"/>
</fields>
</ks_form>


EOS;

?>