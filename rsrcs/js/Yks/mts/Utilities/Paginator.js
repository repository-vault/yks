var Paginator = new Class({
  pages:0,
  current:0,
  active:0,

  options:{
    pad:4
  },

  initialize:function(el, def){ //pages,current,href
    var tmp;

    this.anchor = el;
    this.href_mask = def.href;
    this.pages = Math.ceil(def.max/def.by);
    this.current = def.page_id;
    this.active = this.current;

    this.str_length = Math.floor(Math.log(this.pages)/Math.log(10))+1 ; //strlen is so lame here

    tmp = $n('a',{html:"["+this.pages+"]"}).inject(this.anchor);
    this.a_width = tmp.getSize().x;

    this.anchor.addEvent('mousewheel',this.wheel.bind(this));
    this.wheel({wheel:0});
  },

  populate:function(){

    var  tmp,a, start, end,pad=this.options.pad;
    start = Math.max(Math.min(this.active-pad, this.pages-2*pad-1),0);
    end = Math.min(start+pad*2, this.pages-1);

    this.active = Math.floor((end+start)/2);
    this.anchor.empty();
    for(a=start;a<=end;a++){
        current = (a==this.current);
        str = ("00000000"+(a+1)).slice(-this.str_length);
        tmp=$n('a', {href:this.href_mask+a}).setStyle('width',this.a_width+'px')
            .set('html', current?"["+str+"]":str);
        tmp.addClass(current?'current':'');
        tmp.inject(this.anchor);
        this.anchor.appendText(" ");
    }
    Doms.scan(this.anchor);
  },
  wheel:function(event){
    this.active=Math.max(Math.min(this.active-event.wheel,this.pages),0);
    this.populate();
  }
});


