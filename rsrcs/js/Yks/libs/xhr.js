

function http_lnk(options,url,data,async_func){
    var lnk = new Browser.Request();

    if($type(options)!="object") options = {method:options,async:true,target:false};
    if($type(data)=="object") data = Urls.serial_post(data);
    lnk.open(options.method, url, options.async);
    if(options.headers)
        for(var key in options.headers)lnk.setRequestHeader(key,options.headers[key]);

    var state_change = function(){
        if( this.readyState!=4 || !(/200|404/).test(this.status)) return false;
        this.onreadystatechange = $empty
        var content_type = (this.getResponseHeader("Content-Type") || "text/xml").split(';')[0];
        var val;
        if(content_type=="application/json") val = Urls.jsx_eval(this.responseText);
        else if(!(/[^a-z]xml$/).test(content_type)) val = this.responseText;
        else {
            val = this.responseXML;//prepare serialize for later, no other chance after here (BB)
            if(!val.xml && !window.XMLSerializer) val.xml = this.responseText;
        }
        async_func(val);
    }; lnk.onreadystatechange = state_change.bind(lnk);

    if(options.method=='post'){
        if(http_lnk.security_flag) data += "&ks_flag=" + http_lnk.security_flag;
        lnk.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
        lnk.send(data);
    } else lnk.send(null);

    if((!options.async) && lnk.readyState==4) state_change.call(lnk);
} http_lnk.security_flag = false;

http_lnk.split_headers = function(str){
  var ret = {}, e;
  str.split("\n").each(function(line){    
    line = line.trim();
    if(!line) return;
    e = line.split(':',2)
    ret[e[0].toLowerCase().trim()] = e[1].trim();
  });
  return ret;
};
