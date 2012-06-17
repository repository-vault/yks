/*
    As for Jsx.A, initialize should be minimal, and fire only when necessary
*/

Jsx.Form = new Class({
  Extends: Jsx,
  Binds:['submit', 'cleanup'],

  Occlude : 'Jsx.Form',
  action_image:false,

  initialize:function(form){

    if(this.occlude(form)) return;

    this.anchor = form.store('jsx',this).addEvent('submit',this.submit);

        //behave nicely with input[type=image]
    var self = this;
    form.getElements('input[type=image]').addEvent('click', function(event){
        if(!this.name)return;
        self.action_image = {key:this.name, value:this.value||'on'};
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
    this.rbx = this.rbx || new Rbx(this.anchor);
    if(!this.anchor.hasClass('no_loader')) this.rbx.loader();

    this.data_reset();
    this.data_stack(this.anchor);

    if(this.action_image) {
        this.data_stack(this.action_image);
        this.action_image = false;
    }

    if(this.anchor.enctype == "multipart/form-data"){
        return;
    }

    stop(event);
    this.fire();
  },

  js_valid:function(rbx){
    this.xtd = this.xtd || this.extended();
    if(rbx.warn){
        var tmp = $N(rbx['warn'].trim(), this.anchor);
        if(tmp) tmp.addClass('error').focus();
    } this.parent(rbx);
  }

});

//If you wan an element to automaticly submit a form when a file is attached, you should use 
//$$('input[name=wire_proof]').addEvent('change',function(){this.form.fireEvent('submit'); });
