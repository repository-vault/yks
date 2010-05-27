var Urls = {

  jsx_eval:function(a) {
    return  a?eval('('+a+')'):{};
  },

  parse:function(url) {
    var ret = {full:url,args:{}}, m, tmp, query;
    tmp = url.split('://', 2);
    if(!tmp[1]) {
        if(!Urls.base) Urls.base = Urls.parse(document.location.href);
        return Urls.parse(Urls.base.site_url+(url.charAt(0)=='/'?'':Urls.base.path)+url);
    } ret.protocol = tmp.shift();
    tmp = tmp[0].split('/',2); ret.domain=tmp.shift();
    tmp = tmp[0].split('?',2);
    if(tmp[1])
        while ((m = /([^=]+)=([^&]+)&?/g.exec(tmp[1]) ))
            ret.args[m[1]] = m[2];

    query = '/'+tmp[0]; tmp = query.lastIndexOf('/');
    ret.dir = query.substr(0,tmp+1);
    ret.file = query.substr(tmp+1);
    ret.path = ret.dir+ret.file;
    ret.site_url = ret.protocol+'://'+ret.domain;
    return ret;
  },

  reloc:function(href,target){
    if($type(target)=="string") window.open(href, target);
    if($defined(target)) Jsx.open(href,target);
    else window.location.href=href;
  },

  serial_post:function(data,pref) {
    var value, key, preff, re=[]; if(!$defined(pref)) pref='';
    for(key in data){
        value = data[key]; preff = pref?(pref+'['+key+']'):key;
        if($type(value)!='object') re.push(preff+'='+encodeURIComponent(value));
        else re.push(Urls.serial_post(value,preff));
    } return re.join('&');
  }
};

