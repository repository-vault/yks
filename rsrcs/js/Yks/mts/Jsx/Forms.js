/*
    As for Jsx.A, initialize should be minimal, and fire only when necessary
*/

Jsx.Form = new Class({
  Extends: Jsx,
  Declare : ['Jsx.Form'],
  action_image:false,
  initialize:function(form){  if(Jsx.Form.extended(form)) return;
    this.anchor = form.store('jsx',this).addEvent('submit',this.submit.bind(this));
    var self = this;
    form.getElements('input[type=image]').addEvent('click',function(event){
        if(self.action_image) self.action_image = self.action_image.destroy();
        self.action_image = $n('input',{type:'hidden',name:this.name,value:'on'}).inject(form);
    }); 
  },
  extended:function(){
    this.box = this.anchor.getBox();
    var url = Urls.parse(this.anchor.getAttribute('action',2)
          || this.box.url
          || href_ks);
    this.setOptions({
        url:url.full,
        target:this.anchor.getAttribute('target') || this.box.box_name
    }); return true;
  },
  submit:function(event){
    this.xtd = this.xtd || this.extended();
    this.anchor.fireEvent('onQuery');
    this.anchor.getElements('.error').removeClass('error');


    if(this.anchor.enctype!="multipart/form-data"){
        this.options.data = 'jsx=1&'+this.anchor.toQueryString();
        this.rbx = this.rbx || new Rbx(this.anchor);
        if(!this.anchor.hasClass('no_loader')) this.rbx.loader();
        stop(event); this.fire();
    } else {
        this.upload_flag = this.anchor.APC_UPLOAD_PROGRESS.value;
        Doms.wake('Uploader').flags[this.upload_flag]=this;
    }
    if(this.action_image) this.action_image = this.action_image.destroy();

  },

  js_valid:function(rbx){
    this.xtd = this.xtd || this.extended();
    if(rbx.warn){
        var tmp = this.anchor.getElement("*[name='"+(rbx['warn'].trim())+"']");
        if(tmp) tmp.addClass('error').focus();
    } this.parent(rbx);
  }
});

//If you wan an element to automaticly submit a form when a file is attached, you should use 
//$$('input[name=wire_proof]').addEvent('change',function(){this.form.fireEvent('submit'); });


