var Toggler = new Class({
  Declare : ['Toggler'],

  anchor:false,
  zone:false,
  anchor_height:false,
  fx:false,
  open:true,

  initialize:function(el){
    this.zone = el;
    this.anchor = el.getElement('.toggle_anchor');  
    this.anchor.addEvent('click',this.toggle.bind(this) );
    this.anchor_height = this.anchor.getSize().y;
    if(this.zone.hasClass('closed')) this.toggle(null,true);
    this.zone.addEvent('toggle_resize',this.toggle.bindWithEvent(this,[true,'recalc']));
    var self_zone = this.zone;
    this.zone.getElements('.box').each(function(el){
        el.getBox().addEvent('reloaded', self_zone.fireEvent.pass(['toggle_resize'],self_zone));
    });
  },
  
  toggle:function(event,fast,open){
    if(this.open && open) this.zone.setStyle('height','auto');
    this.open= (open=='recalc')?this.open:(open || !this.open);
    this.zone[this.open?'removeClass':'addClass']("closed");
    var max = this.zone.getScrollSize().y, min = this.anchor_height, open = this.open;
    if(this.fx) this.fx.cancel();
    if(fast) this.zone.setStyle('height',(open?min:max,open?max:min)+'px');
    else this.fx = this.zone.effect('height').start(open?min:max,open?max:min);
  }
});

