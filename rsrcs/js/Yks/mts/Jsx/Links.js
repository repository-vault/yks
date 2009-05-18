/*
As for Jsx.Form, initialize should be minimal, and fire only when necessary
*/

Jsx.A = new Class({
  Extends: Jsx,
  Declare : ['Jsx.A'],

  initialize:function(el){  if(Jsx.A.extended(el)) return;
    if(el.get('tag')!='a') el.addClass('click');
    this.anchor = el.addEvent('click', this.click.bind(this));
  },
  extended:function(){
    this.box = this.anchor.getBox();

    var url = Urls.parse(this.anchor.get('href',true));
    if(url.domain!=site_domain) return false;
    var target = this.anchor.get('target',true) || this.box.box_name;
    if(target=='::new') target = Screen.get_lambda_box();
    this.setOptions({
        url:url.full,
        method:'get',
        target:target,
        target_base:target,
        lang:this.anchor.getAttribute("xml:lang") || false
    }); return true;
  },
  click:function(event){ event.stop();
    if(!$defined(this.xtd)) this.xtd=this.extended();
    if(event.alt){
        var target = this.options.target+'_modal';
        this.setOptions({box:{modal:true,fly:true},target:target});
    } else this.setOptions({box:false,target:this.options.target_base});
    if(this.xtd) this.fire(); 
  }
});

Jsx.A.popup_default = "menubar=no,statusbar=no,adressbar=no,height=400,width=500";

//Doms. loaders["a:not([href^='http://'])"]=Jsx.A; - should be enought
