<box theme="&pref.dft;" caption="Importation d'items" id="item_import">
  <ks_form ks_action="items_import" submit="Envoyer">
    <field type="lang_key" title="Langue"/>
    <p class='tog'>Contenu depuis text brut</p>
    <field class='alt' title="Texte" type="textarea" name="items_list"></field>
    <p class='tog'>Contenu depuis un fichier</p>
    <field class='alt' title="Fichier" type="upload" name="items_file" upload_type="locale_file" upload_title="Attacher un fichier"/>
  </ks_form>

<style>
#item_import p.tog {
    cursor:pointer;
    background-position:right center;
    background-repeat:no-repeat;
    background-image:url(/css/Yks/mts/toggle_down.png);
}

#item_import p.tog.active {
    background-image:url(/css/Yks/mts/toggle_up.png);
}

</style>

<domready>
var res =  Doms.instanciate('Accordion', $$('#item_import p.tog'), $$('#item_import p.alt'), {

  onActive:function(tog, elem){
    tog.addClass('active');
    elem.getInputs().set('disabled', false);
  },
  onBackground:function(tog, elem){
    tog.removeClass('active');
    elem.getInputs().set('disabled', 'disabled');
  },

});



$$('#item_import p.alt').addEvent('injected', function(){
    res.display(-1);
    res.display(this);
  }
);

</domready>


</box>