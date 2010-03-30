var Uploader = {
  flags:{},
  end:function(upload_flag,rbx){
    var upload, form = this.flags[upload_flag];
    if(rbx.error) return form.js_valid(rbx);

    upload = rbx.upload;
    $(upload.src).form.retrieve('jsx').js_valid(rbx);
    $(upload.src).set('value', upload.upload_flag+'.'+upload.ext).fireEvent('change');

    var container_id = 'label_'+upload.src;
    var reset = function(){
        $$('#'+container_id).dispose();
        $(upload.src).set('value','');
    }; reset();


    var head = "<tr><th>File</th><th>Size</th><th>Action</th></tr>";
    var upload_table = $n('table', {id:container_id, 'class':'upload_table',html:head});
    upload_table.inject($(upload.src),'after');
    var line = $n('tr').inject(upload_table);
    $n('td',{text:upload.name}).inject(line);
    $n('td',{text:FileUtils.file_size(upload.size)}).inject(line);
    $n('td',{text:"[remove]"}).inject(line).addEvent('click', reset);

    form.box.close();
    return;

    $n('div',{
        id:container_id,
        html:"Fichier : "+upload.name+" ("+FileUtils.file_size(upload.size)+")"
    }).inject($(upload.src),'after');
    form.box.close();
  }
};


var FileUtils = {
  file_size:function(size){
    return (size>>30)?(size/(1<<30)).round(2)+' Go':
        ((size>>20)?(size/(1<<20)).round(2)+' Mo':
            ((size>>10)?(size/(1<<10)).round(2)+' Ko':size+" octets"));
  }
};
