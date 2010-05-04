var Nav_bar = new Class({

  initialize:function(id){
    var depth,sel, anchor;
    if(! (anchor = $(id)) )
        return;

    for(depth=0,tmp='#'+id ; depth<=1 ; depth++, tmp+='>li>ul')
        $$([tmp, tmp+'>li', tmp+'>li>a'].join(',')).addClass('d_'+depth);

    $$("li.d_0.parent").addEvent('mouseover',function(){
        var sub= this.getElement('ul'), to = sub.getScrollSize().y,tmp;
        if(!sub.retrieve('trans')) {
            if(Browser.Engine.trident)sub.setStyle('margin-left',-this.getWidth());
            sub.set('morph', {duration: 500, transition:Fx.Transitions.Quint.easeOut}).get('morph')
            sub.store('trans',true);
        } sub.morph({height:to}); 
    }).addEvent('mouseout',function(){ this.getElement('ul').morph({height:0}); });

    $(id).setStyle('visibility','visible');
  }
});