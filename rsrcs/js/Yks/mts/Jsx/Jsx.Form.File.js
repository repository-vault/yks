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
    document.title= print_r(e);
    (new Event(e)).stop();
  },

  drop:function(e){
    (new Event(e)).stop();
    var dt = e.dataTransfer;  
    var files = dt.files;  
    var file = files[0];

    Uploader.start_upload(this, file);
  },


  initialize:function(el){
    if(this.occlude(el)) return;
    this.anchor = el;
    this.form   = this.anchor.getParent('form');

    this.upload_flag = $uniqueID();
    this.upload_name = this.anchor.get("name");
    this.upload_type = this.anchor.get("upload_type");


    this.upload_url  = "/?/Yks/Wysiwyg//"
        + [this.upload_flag, this.upload_name, this.upload_type].join(';')
        + "/upload";

    Uploader.flags[this.upload_flag] = this;

    if(false) return; //manage JSX Upload here
    this.button = this.anchor.getParent().getElement('a[target=upload_file]');
    this.button.set('href', this.upload_url);

    //this.upload_flag = this.anchor.APC_UPLOAD_PROGRESS.value;


    //var jsx = .retrieve('jsx');

    this.form.addEventListener("dragleave", this.drag_leave, false);
    this.form.addEventListener("dragenter", this.drag_enter, false);

    this.form.addEventListener("dragenter", this.drag_enter, false);  
    this.form.addEventListener("dragover", this.drag_cancel, false);
    this.form.addEventListener("drop", this.drop, false);
  }
});