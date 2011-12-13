Xhr.implement({

 encode_multipart:{

  transport_callback:'sendAsBinary',

  encode: function(hash, callback){

    var boundary = ("-").repeat(10) + $uniqueID(10);

    var format = function(hash, str, back){

      if(!hash || hash.length == 0)
        return back(str);

      var val = hash.shift();

      if($type(val) == 'string') {
          val = val.split('=', 2);
          val = {key:val[0],value:val[1]};
      }


      str += "--" + boundary + CRLF;
      str += "Content-Disposition: form-data; name=\"" + val.key + "\"; ";

      if(val && val.value && $instanceOf(val.value, File)) {
          str += "filename=\""+val.value.name+"\""+CRLF;
          str += "Content-Type: "+val.value.type + CRLF;
          str += CRLF;
          format_file(val.value, hash, str, back);
      } else {
          str += CRLF + CRLF;
          str += val.value + CRLF;
          format(hash, str, back);
      }
    };

    var format_file = function(val, hash, str, back){  
       var reader = new FileReader();

       reader.onload = function(evt) {

          str +=  evt.target.result + CRLF;

          format( hash, str, back);
       }; reader.readAsBinaryString(val);  
    };


    format(hash, "", function(str){
      //final
      str += "--" + boundary + "--" + CRLF;

      this.addHeaders({
          'Content-Type'   : "multipart/form-data; boundary="+boundary,
          'Content-Length' : str.length
      });

      callback(str);
    }.bind(this));

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
