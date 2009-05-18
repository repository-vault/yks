var Uploader = {
  flags:{},
  end:function(upload_flag,rbx){
    var upload, form = this.flags[upload_flag];
    if(rbx.error) return form.js_valid(rbx);

    upload = rbx.upload;
    $(upload.src).form.retrieve('jsx').js_valid(rbx);
    $(upload.src).set('value', upload.upload_flag+'.'+upload.ext).fireEvent('change');

    $$('div#label_'+upload.src).dispose();
    $n('div',{
        id:'label_'+upload.src,
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
