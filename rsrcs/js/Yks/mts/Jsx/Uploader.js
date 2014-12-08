
var Uploader =  {
  flags:{},

  end_upload:function(upload_flag, input, rbx ){
    var upload = rbx.upload;
    input.uid();

    input.form.retrieve('jsx').js_valid(rbx);

    var reset = function(){
        if(!input.upload_table)return;
        input.upload_table.dispose();
        input.upload_table = false;
    };
    
    if(!input.upload_multiple)
        reset();
    
    if(!input.upload_table) {
      input.upload_table = $n('table', {
        id:'label_'+upload.src,
        'class':'upload_table',
        html:"<tbody><tr><th>File</th><th>Size</th><th>Action</th></tr></tbody>"
      }).inject(document.id(upload.src),'after').getElement('tr').getParent();
    }

    var line = $n('tr').inject(input.upload_table);

    var basename = (upload.name||"").specialchars_decode() ;
    var infotd = $n('td',{text:basename}).inject(line);
    var upload_pfx = input.upload_name; // + '['+upload_flag+']';
    $n('input', {
        name:upload_pfx + '[path]',
        type:'hidden',
        value:upload_flag+'.'+upload.ext
    }).inject(infotd);

    $n('input', {
        name:upload_pfx + '[basename]',
        type:'hidden',
        value:basename
    }).inject(infotd);

    input.anchor.fireEvent('change');


    $n('td',{text:FileUtils.format_size(upload.size)}).inject(line);
    $n('td',{text:"[remove]"}).inject(line).addEvent('click',  function(){
        this.getParent('tr').dispose();
        if(!input.upload_multiple)
            reset();
    });

  },

 /* modal callback, two possibles cases
    -failure : stay on modal form
    -sucess : close box & pass to Uploader.jsx
 */
  end_upload_static:function(upload_flag, rbx, form){
    var success = !!rbx.upload;
    if(!success) {
        form.retrieve('jsx').js_valid(rbx);
    } else {
        form.getBox().close();
        var input = this.flags[upload_flag];
        this.end_upload(upload_flag, input, rbx);
    }
  },

  start_upload:function(input, file){
    var jsx = new Jsx( {url:input.upload_url, encoding:"multipart"}, input.form);
    jsx.data_stack({key:'APC_UPLOAD_PROGRESS',value:input.upload_flag});
    jsx.data_stack({key:'ks_action',value:'upload_tmp'});
    jsx.data_stack({key:'user_file',value:file});

    jsx.fire( this.end_upload.stack([input.upload_flag, input], this) ); // \o/
  }

};
