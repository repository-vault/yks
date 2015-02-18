
var Switcher = new Class ({
  Declare : 'Switcher',

  anchor:false,
  children:false,
  slide_active:false,
  anchor_size:{},
  mask_zone:false,

  initialize:function(el,children,titles,show_titles){
    this.anchor = $n('div').inject(el,'top');
    var children_list= {};
    this.children = el.getElements(children).addClass('switch_item').inject(this.anchor);
    slide_active = this.children[0].id;
    this.slide_to(slide_active);
    if(!$defined(show_titles))show_titles = true;

    this.build_title_nav(titles, show_titles);

    this.anchor.setStyle('overflow','hidden');
    this.anchor_size.x=this.children[0].getScrollSize().x+20;
    this.anchor_size.y = this.anchor.getScrollSize().y;
    this.anchor.setStyles({'height': this.anchor_size.y+'px', width:this.anchor_size.x+'px'});
    this.mask_zone=$n('div',{'class':'switch_container'})
            .inject(this.children[0],'before');
    this.children.inject(this.mask_zone);
  },
  build_title_nav:function(titles, show_titles){
    var nav=$n('div',{'class':'switch_titles'});
    this.children.getElement(titles).each(function(el,k){
        if(k) nav.appendText(' - ');
        var id,tmp=$n('span',{html:el.innerHTML}).inject(nav);
        id = this.children[k].id;
        tmp.addEvent('click',this.slide_to.pass(id,this));
    }.bind(this));
    if(show_titles) nav.inject(this.anchor,'top');
    this.anchor.getElements("*[slide_to]").each(function(el){
        var id = el.get('slide_to');
        el.addEvent('click',this.slide_to.pass(id,this));
    }.bind(this));
  },

  slide_to:function(new_slide_id){
    if(this.slide_active==new_slide_id) return;

    var tmp;
    if(this.slide_active){
        tmp  = document.id(new_slide_id);    
        tmp.removeClass('off').inject(document.id(this.slide_active),'after');
        this.mask_zone.effect('margin-left').start(0,-this.anchor_size.x).chain(function(old_id,new_id){
            document.id(old_id).addClass('off');
            this.mask_zone.setStyle('margin-left',0);
            this.slide_active=new_id;
        }.pass([this.slide_active,new_slide_id],this));
        this.slide_active=new_slide_id;
        
    } else {
        this.children.addClass('off');
        document.id(new_slide_id).removeClass('off');
    }
    this.slide_active=new_slide_id;

  }

});