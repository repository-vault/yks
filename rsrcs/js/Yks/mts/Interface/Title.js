
var Title = new Class({

  Occlude: 'Title',

  setPseudo:function(el, pseudo){
    el.src = '/?/Yks/Scripts/Imgs/titles//'
            + el.get('theme') + (pseudo || '')
            + "|" + el.alt;
  },

  initialize:function(el){

    if(this.occlude(el)) return;

    if(el.hasClass('hover')) {
        el.addEvent('mouseenter', this.setPseudo.pass([el, ':hover']));
        el.addEvent('mouseleave', this.setPseudo.pass([el]));
    }

  }
});




