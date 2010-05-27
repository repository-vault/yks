
var Xhr = new Class({
  Implements: [Events],
  Binds:['state_change', 'request'],

  encoding:false, //urlencode|multipart
  headers:$H({}),

  initialize:function(async, encoding){
    this.async = $defined(async) ? async : true;
    this.lnk   = new Browser.Request();
    this.encoding = encoding || 'urlencode';
  },

  request:function(url, method, data){

    var encoder = this["encode_"+this.encoding]
    if(!encoder)
        throw "Unknow encoder";

    this.lnk.open(method||'GET', url, this.async);

    data = this.data_cleanup(data||[]);
    data = encoder.encode.call(this, data);

    this.headers.each(function(val, key){
        this.lnk.setRequestHeader(key, val);
    }.bind(this));

    this.lnk.onreadystatechange = this.state_change;

    if(method == 'GET')
        data = null;


    this.lnk[encoder.transport_callback](data);

    if((!this.async) && this.lnk.readyState==4)
        this.state_change();
  },

  isSuccess: function(){
    return this.lnk.readyState == 4
           && ((this.lnk.status >= 200) && (this.lnk.status < 300));
  },


  addHeaders:function(vals){
    $extend(this.headers, vals);
    return this;
  },


  state_change:function(){ 
    if(!this.isSuccess()) return false;
    this.lnk.onreadystatechange = $empty; //prevent dbl calls

    var headers = Xhr.split_headers(this.lnk.getAllResponseHeaders());

    var content_type = (headers['content-type'] || "text/xml").split(';')[0];
    var val;

    if(content_type=="application/json") val = Urls.jsx_eval(this.lnk.responseText);
    else if(!(/[^a-z]xml$/).test(content_type)) val = this.lnk.responseText;
    else {    
        val = this.lnk.responseXML;//prepare serialize for later, no other chance after here (BB)
        if(!val.xml && !window.XMLSerializer)
            val.xml = this.lnk.responseText;
    }
    this.fireEvent('success', [val, headers ] );
  }

});



