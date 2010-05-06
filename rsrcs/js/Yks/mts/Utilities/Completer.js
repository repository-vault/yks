
var Completer = new Class ({
  Implements:[Options],
  Declare : 'Completer',

  index:-1,
  vals:[],
  opened:false,
  options:{
    min_chars:3,
    action: 'search',
    one_out:true     //is there's only one result, it's the one
  },
  old_value:'',
  save:false,
  timeout:false,

  initialize:function(el,options){
    this.setOptions(options);
    this.options.url = this.options.url || href_ks;
    this.input = el;
    this.input.addEvent('click', this.load.bind(this));
    if(this.options.save && this.options.onChange )
        this.options.save.addEvent('change', this.options.onChange);
  },

  load:function(){
    this.input.removeEvents('click');
    this.input.addEvent('keyup',this.keypress.bind(this) );
    this.input.addEvent('keydown',this.keydown.bind(this) );
    this.input.addEvent('blur',function(){this.close.delay(200,this)}.bind(this) );

    this.ul = $n('ul',{'class':'completer'});
    this.form= this.input.getParent('form');
    var dims = this.input.getCoordinates();
    this.ul.setStyles({left:dims.left,width:dims.width,top:dims.top+dims.height});
  },

  keydown:function(event){ //up & down options
    var code = event.code;
    if(code == 13 ){event.stop();
        this.set(this.index);
        event.stop();
        return false;
    }
    if(code == 40 ||code == 38){ 
        if( !this.opened ) return false;
        if(this.index ==-1) this.index = (code==38?0:-1);this.value=this.index;
        this.index = (this.index+(code-39)+this.vals.length)%this.vals.length;
        this.ul.getElements('.focused').removeClass('focused');
        this.vals[this.index].addClass('focused');
        return;
    }
  },

  keypress:function(event){ //chars
    var code = event.code;

    if((!this.input.value) || this.input.value==this.old_value) return;
    this.old_value=this.input.value;
    if(this.input.value.length < this.options.min_chars) {
        this.timeout = this.fire.delay(1000,this);
        return;
    };
    $clear(this.timeout);
    this.fire();
  },

  fire:function(){
    var data = {ks_action:this.options.action, q:this.input.value};
    http_lnk('post', this.options.url, data, this.populate.bind(this));
  },

  populate:function(data){
    var val,li, results_list = data.results_list; if($type(results_list)!="object") return;
    this.ul.empty();
    this.vals = []; this.index = -1;
    $H(results_list).each(function(label, key) {
        this.vals.push( li = $n('li').set('html',results_list[key]).store('value', key) );
        li.addEvent('click',this.set.pass(this.vals.length-1,this));
        li.inject(this.ul);
    },this);
    this.ul.inject($(document.body));
    this.opened = true;
    if(this.vals.length == 1 && this.options.one_out) this.set(0);
  },

  set:function(key){
    var item = this.vals[key]; if(!item) return false; 
    this.old_value = this.input.value = item.innerHTML;
    if(this.options.save) this.options.save.set('value', item.retrieve('value'));
    this.close();
    this.input.focus();this.input.highlight();
    this.options.save.fireEvent('change');
  },

  close:function(){
    this.ul.dispose();
    this.opened = false;
  }

});