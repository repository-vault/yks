

function http_lnk(options,url,data,async_func){
    var lnk = new Browser.Request();

    if($type(options)!="object") options = {method:options,async:true,target:false};
    if($type(data)=="object") data = Urls.serial_post(data);
    lnk.open(options.method, url, options.async);
    if(options.headers)
        for(var key in options.headers)lnk.setRequestHeader(key,options.headers[key]);

    var state_change = function(){
        if( this.readyState!=4 || !(/200|404/).test(this.status)) return false;
        this.onreadystatechange = $empty;
        var content_type = (this.getResponseHeader("Content-Type") || "text/xml").split(';')[0];
        var val = content_type=="application/json"?Urls.jsx_eval(this.responseText):
            ((/[^a-z]xml$/).test(content_type)?this.responseXML:this.responseText);
        async_func(val);
    }; lnk.onreadystatechange = state_change.bind(lnk);

    if(options.method=='post'){
        if(http_lnk.security_flag) data += "&ks_flag=" + http_lnk.security_flag;
        lnk.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
        lnk.send(data);
    } else lnk.send(null);

    if((!options.async) && lnk.readyState==4) state_change.call(lnk);
} http_lnk.security_flag = false;