Jsx.Form.File = new Class({

  Binds: ['drag_cancel', 'drop', 'drag_leave', 'drag_enter'],
  Occlude:'Jsx.Form.File',
  anchor:false,
  upload_type:false,

  drag_cancel:function(e){  (new Event(e)).stop(); },


  drag_leave:function(e){
    this.form.removeClass('upload_ready');
  },

  drag_enter:function(e){
    this.form.addClass('upload_ready');
    //document.title= print_r(e);
    (new Event(e)).stop();
  },

  drop:function(e){
    (new Event(e)).stop();
    var dt = e.dataTransfer;  

    if(dt.files.length == 0)
        return;

    for(var i=0;i<dt.files.length;i++) {
        this.uid();
        Uploader.start_upload( this, dt.files[i]);
    }
  },

  uid:function(){
    this.upload_flag = $uniqueID();

    this.upload_url  = "/?/Yks/Wysiwyg//"
        + [this.upload_flag, this.upload_name, this.upload_type].join(';')
        + "/upload";

    Uploader.flags[this.upload_flag] = this;
    this.button.set('href', this.upload_url );
  },

  initialize:function(el){
    if(this.occlude(el)) return;
    this.anchor = el;
    this.form   = this.anchor.getParent('form');

    this.upload_name = this.anchor.get("id");
    this.upload_type = this.anchor.get("upload_type");
    this.upload_multiple = this.upload_name.substr(-2) == "[]";
    this.button = this.anchor.getParent().getElement('a[target=upload_file]');
    this.uid(); //initial link

    if(!Browser.Features.files)
        return; //manage JSX Upload here


    this.anchor.getParent('p').addClass('droppable_ready');

    this.form.addEventListener("dragleave", this.drag_leave, false);
    this.form.addEventListener("dragenter", this.drag_enter, false);

    this.form.addEventListener("dragenter", this.drag_enter, false);  
    this.form.addEventListener("dragover", this.drag_cancel, false);
    this.form.addEventListener("drop", this.drop, false);
  }
});