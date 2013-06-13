/*
As for Jsx.Form, initialize should be minimal, and fire only when necessary
*/

Jsx.A = new Class({
  Extends: Jsx,
  Occlude : 'Jsx.A',
  Binds: ['click'],

  initialize:function(el){
    if(this.occlude(el)) return;

    if(el.get('tag')!='a') el.addClass('click');
    this.anchor = el.addEvent('click', this.click);
  },

  extended:function(){
    this.box = this.anchor.getBox();

    var href = this.anchor.get('href',true), url = Urls.parse(href);
    if(url.domain!=site_domain) return false;
    var target = this.anchor.get('target',true) || this.box.box_name;
    if(target=='::new')   target = Screen.get_lambda_box();
    if(target=='::modal'){
        target = Screen.get_lambda_box();
        this.setOptions({box:{modal:true,fly:true}});
    }

    this.setOptions({
        url:url.full,
        href:href,
        method:'get',
        target:target,

        lang:this.anchor.getAttribute("xml:lang") || false
    }); return true;
  },

  click:function(event){ event.stop();
    if(!$defined(this.xtd)) this.xtd = this.extended();
    var href = this.anchor.get('href',true);

    if(href != this.options.href)
        this.options.url = href;

    if (typeof history.pushState != 'undefined') {
      history.pushState(
        null,
        null,
        href);
    }

    if(this.base_options) {
        this.setOptions(this.base_options);
        this.base_options = false;
    }

        //alter options for one time only, force modal
    if(event.alt){
        this.base_options = this.options;
        var target = this.options.target+'_modal';
        this.setOptions({box:{modal:true,fly:true},target:target});
    }

    if(this.xtd) this.fire();
  }
});

Jsx.A.popup_default = "menubar=no,statusbar=no,adressbar=no,height=400,width=500";
