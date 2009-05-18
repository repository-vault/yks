var Box = new Class({
  Implements: [Events],
  Declare : ['Box'],

  box_name:'',
  url:'',
  rbx:false,
  opener:false,

  initialize:function(anchor,options){  if(Box.extended(anchor)) return;

    options = options || {};
    this.anchor = anchor.store('box',this);
    this.box_name = options.box_name || anchor.id || Screen.get_lambda_box();
    this.fly = $defined(options.fly)?options.fly: (this.anchor.hasClass('fly') ||false);

    var src = anchor.getAttribute('src');

    this.anchor.id = this.box_name;
    var parent_box = anchor.getParent().getBox()
    this.url = options.url || src || parent_box.url || href_ks ;
    this.opener = options.opener || parent_box || false;
    Screen.boxes_list[this.box_name] = this;

    if(src) return Jsx.open(src, this.anchor.id, parent_box.anchor);

    $$("div[class$='_close']")
        .filter(function(el){return el.getParent(Doms.box_mask)==this.anchor;}.bind(this))
        .addEvent('click', this.close.bind(this));

    $$("div[class$='_reload']")
        .filter(function(el){return el.getParent(Doms.box_mask)==this.anchor;}.bind(this))
        .addEvent('click', this.reload.bind(this));

    if(!this.fly) return;

    this.size = anchor.getSize();

    var screen_size = getSize(), scroll_top = getScroll();
        //y positioning can be done better (Math.max&min a lot
    if(!$defined(options.place))
        options.place = {
            top:Math.max((screen_size.y-this.size.y)/2+scroll_top.y,0),
            left:(screen_size.x-this.size.x)/2
        };

    anchor.setStyles($merge(options.place, {position:'absolute'}));
    var scroll_size=getScrollSize(); //must be done AFTER absolute positionning

    Doms.autoload("Drag.Move");

        //!!!
    if(!anchor.makeDraggable && Browser.Engine.trident){ anchor.$family = false; $(anchor); }

    anchor.makeDraggable({handle:$E("*[class$='_u']",anchor).addClass('dragged')});
    anchor.addEvent('click', this.focus.bind(this) );

    this.focus();

    var modal = options.modal || this.anchor.hasClass('modal') ;
    if(options.modal_box) this.modal_box = options.modal_box;
    else if(modal) this.modal_box = Screen.modaler();
  },

  reload:function(){ Jsx.open(this.url, this.box_name, this.anchor); },

  scrollTo:function(){
    this.focus();
    new Fx.Scroll(window).start(
        Math.max(anchor.style.left.toInt()-250,0),
        Math.max(anchor.style.top.toInt()-20,0)
    );
  },

  focus:function(){
    var box = this; while(box && !box.fly && (box=box.anchor.getParent()) && (box=box.getBox()) );
    if(!box) return; Screen.box_focus = box;
        
    var zI = box.anchor.style.zIndex, zN=0;

    for(box_name in Screen.boxes_list){
        if((zN=Screen.boxes_list[box_name].anchor.style.zIndex) <= zI ) continue;
        Screen.boxes_list[box_name].anchor.style.zIndex=zN-1;
    } box.anchor.style.zIndex=Screen.box_zImax;
  },

  getPosition:function(){ return this.anchor.getStyles('width','height','left','top','zIndex');},

  getRbx:function(){
    if(this.rbx) return this.rbx;
    var tagname = this.anchor.get('tag');
    if(tagname=='table')
        return this.rbx = new Rbx(this.anchor.getElement('.inner'));
    else if(tagname=='fieldset') { this.rbx=new Rbx(this.anchor);
            this.rbx.box.inject(this.anchor.getChildren('legend')[0],'after');
            return this.rbx;
    } else return this.rbx=new Rbx(this.anchor);
 },

  close:function(event){stop(event);
    if(this.modal_box) $(this.modal_box).destroy();
    this.anchor.effect('opacity',{duration:200}).start(1,0).chain(function(){
        delete Screen.boxes_list[this.box_name];
        this.anchor=$(this.anchor).destroy();
        if(this.opener) this.opener.focus();
    }.bind(this));
    return false;
  }

});


$.Box = function(box){ return box.anchor; }


Element.implement({
  getBox:function(){ 
    var box = this.retrieve('box');
    if(box) return box;
    box = this.getParent(Doms.box_mask);
    box = box?box.retrieve('box'):false;
    this.store('box', box);
    return box;
}});
