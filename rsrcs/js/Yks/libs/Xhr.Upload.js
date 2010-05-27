
Xhr.implement({

 encode_multipart:{

  transport_callback:'sendAsBinary',

  encode: function(hash){

    var test = "test";
    alert(test.repeat);

    var boundary = ("-").repeat(10) + $uniqueID(10);

    var str = "";

    hash.each(function(val) {
        str += "--"+boundary+CRLF;
        str += "Content-Disposition: form-data; name=\""+val.key+"\"; ";

        if(val && val.value && val.value.getAsBinary) {
            str += "filename=\""+val.value.name+"\""+CRLF;
            str += "Content-Type: "+val.value.type + CRLF;
            str += CRLF;
            str += val.value.getAsBinary() + CRLF;
        } else {
            str += CRLF + CRLF;
            str += val.value + CRLF;
        }
    });

    str += "--" + boundary + "--" + CRLF;

    this.addHeaders({
        'Content-Type'   : "multipart/form-data; boundary="+boundary,
        'Content-Length' : str.length
    });

    return str;
  }
 },

  prepareUpload:function(){

   this.lnk.upload.addEventListener("progress", function(e) {  
      if (!e.lengthComputable) return;
      var percentage = Math.round((e.loaded * 100) / e.total); 
      this.fireEvent('progress', percentage);
   }.bind(this), false);  
     
   //this.lnk.xhr.upload.addEventListener("load", function(e)     
  }


});
