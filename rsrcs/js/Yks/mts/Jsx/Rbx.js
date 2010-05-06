
var Rbx = new Class({
  Occlude : 'Rbx',
  fx:false,

  initialize:function(anchor, box){
    anchor= anchor || $('rbx') || $("container") || $E("body");

    if(this.occlude(anchor)) return;

    this.anchor = anchor;
    if(box) { this.box = box; return ; }

    var width = this.anchor.getWidth() - Hash.map(
        anchor.getStyles('padding-left','padding-right','border-left','border-right'),
        String.toInt
    ).sum();
    this.box = $n('div',{'class':'rbx','styles':{width:width}}).inject(this.anchor,'top');
  },

  replace_fx:function(fx){
    if(this.fx.cancel)this.fx.cancel();
    this.fx = fx;
  },

  msg:function(zone,msg){
    var tmp, from = this.box.setStyle('display','block').getHeight();
    this.box.empty().adopt( tmp = $n('div',{'class':'rbx_'+zone}));
        
    tmp.innerHTML = msg; this.box.setStyle('height', from);

    var picto = $n('div',{'class':'rbx_close'}).injectTop(tmp);
    picto.addEvent("click",this.close.bind(this)); this.$closed = false;

    var to = 0; this.box.getChildren().each(function(elem){ to += elem.getHeight()});
    Doms.instanciate('Fx.Scroll',window).toElement(this.box);
    this.replace_fx(this.box.effect('height').start(from,to));
  },

  close:function(){
    this.replace_fx(this.box.effect('height').start(
        this.box.getHeight(),0).chain(Element.empty.pass(this.box)));
  },

  walk:function(step){ this.walker.setStyle('width',step+'%');  },
  loader:function(start){
    this.box.setStyle('display','block').empty();
    this.walker = $n('div', {'class':'rbx_loader'})
        .setStyle('width', $defined(start)?start+'%':'auto').inject(this.box);

    this.replace_fx(this.box.effect('height').start(this.box.getHeight(),14));
  }
});
