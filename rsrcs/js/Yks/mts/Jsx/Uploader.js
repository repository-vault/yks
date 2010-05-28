var Uploader =  {
  flags:{},

 end_upload:function(upload_flag, rbx){
    var input  = this.flags[upload_flag];
    var upload = rbx.upload;

    input.form.retrieve('jsx').js_valid(rbx);

    var container_id = 'label_'+upload.src;
    var reset = function(){
        $$('#'+container_id).dispose();
        $(upload.src).set('value','');
    }; reset();

    $(upload.src).set('value', upload.upload_flag+'.'+upload.ext).fireEvent('change');

    var upload_table = $n('table', {
        id:container_id,
        'class':'upload_table',
        html:"<tbody><tr><th>File</th><th>Size</th><th>Action</th></tr></tbody>"
    }).inject($(upload.src),'after').getElement('tr').getParent();
    var line = $n('tr').inject(upload_table);
    $n('td',{text:upload.name}).inject(line);
    $n('td',{text:FileUtils.format_size(upload.size)}).inject(line);
    $n('td',{text:"[remove]"}).inject(line).addEvent('click', reset);

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
        this.end_upload(upload_flag, rbx);
    }
 },

 start_upload:function(input, file){

    var jsx = new Jsx( {url:input.upload_url, encoding:"multipart"}, input.form);
    jsx.data_stack({key:'APC_UPLOAD_PROGRESS',value:input.upload_flag});
    jsx.data_stack({key:'ks_action',value:'upload_tmp'});
    jsx.data_stack({key:'user_file',value:file});

    jsx.fire( function(rbx){
        this.end_upload(input.upload_flag, rbx);
    }.bind(this));

 }

};
