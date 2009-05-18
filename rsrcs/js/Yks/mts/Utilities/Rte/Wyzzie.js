var blank_frame;

var Wyzzie = new Class({
  Declare : ['Wyzzie'],

  focus: function() { (this.designed ?  this.win : this.area).focus(); },

  initialize: function(area) {
  
    if(area.wyzzie) return false;
    if(!blank_frame) blank_frame="/blank.htm";

    area.wyzzie = this;
    
    this.area=area;
    this.form=$(this.area.form);
    this.designed=false;

    var box_size=this.area.getSize();
    var container_size={'width':box_size.x,'height':box_size.y-30};


    this.container = new Element('div',{'class':'rte_container',styles:container_size}).inject(this.area,'before');

    var frame_size={width:box_size.x,height:container_size.height-10};

    if(this.form)this.form.addEvent("onQuery",this.sync.bind(this));

    this.area.setStyles($merge(frame_size,{'display':'none'}) );
    this.area.inject(this.container);

    this.toolbar=new Element('div',{'class':'rte_toolbar'}).inject(this.container,'top');
    for(var key in this.actions){ var item=this.actions[key];
        item.div=new Element('div',{'class':key,'unselectable':'on'});
        item.div.inject(this.toolbar);
        if(item.action) item.div.onclick=this.action.pass(item.action,this);
        if(item.onclick)item.div.onclick=item.onclick.bind(this);
    }

    this.frame=new Element('iframe', {'src':blank_frame,styles:frame_size} 
    ).inject(this.container);
    this.win = this.frame.contentWindow || this.frame;

    this.frame.addEvent("load",function(){
        
        this.doc = this.win.document;
        this.doc.designMode = 'On';

        this.doc = this.win.document; // IE access grant

        var win = new Window(this.win);
        var doc = new Document(this.doc);
        $extend(win.Element.prototype, Element.Prototype);

        $clear(this.loading);//IE shift recursive declaration
        this.loading=window.setInterval(function(){
            if(!(this.doc && this.doc.body && this.doc.body.innerHTML)) return;
            $clear(this.loading);this.toggle(true);
        }.bind(this),10);
      }.bind(this));

    //this.frame.fireEvent("load");

  },
    
  toggle: function(designed) {
    if(!this.ready){
        this.ready=true;
        //this.win.addEvent('keypress',function(event){});
        //$(this.doc.body).addEvent('keypress',function(event){});
    }

    if($defined(designed))this.designed =designed;
    else this.designed = !this.designed*1;

    if (this.designed) this.doc.body.innerHTML = this.area.value;
    else this.area.value = this.doc.body.innerHTML;

    var el = ['area', 'frame'];
    this[el[this.designed*1 ]].setStyle('display', 'block');
    this[el[!this.designed*1]].setStyle('display', 'none');
    this.focus();
  },

  sync:function(){ if (this.designed) this.area.value = this.doc.body.innerHTML; },
    
  action: function(cmd, html) {
    var action=window.ie?this.doc.selection.createRange():this.doc;
    if ($type(cmd) != 'array') cmd = [cmd];
    if (this.designed) try { action.execCommand(cmd[0],false,cmd[1]) } catch(e) {}
    this.focus();
 },

  actions:{
    'italic':{'key':'i','action':["italic", "<em>$</em>"]},
    'bold':{'key':'b','action':["bold", "<strong>$</strong>"]},
    'underline':{'key':'u','action':["underline", "<u>$</u>"]},
    'left':{'key':'l','action':["justifyleft", "#<div class='align_left'>#$#</div>#"]},
    'right':{'key':'r','action':["justifyright", "#<div class='align_right'>#$#</div>#"]},
    'center':{'key':'c','action':["justifycenter", "#<div class='center'>#$#</div>#"]},
    'toggle':{'key':'t','onclick':function(){this.toggle()} } 
  }
});


