Xhr.implement({

    //prepare data as a list of {key:key,value:value} pairs, ready to be encoded

  data_cleanup:function(data){
    var ret = [];
    data.each(function(data){
      switch ($type(data)){
        case 'string' : data = data.split('=',2); ret.push({key:data[0],value:data[1]});break;
        case 'element': ret.extend(document.id(data).toQueryList()); break;
        case 'hash': ret.extend(Hash.toQueryList(data));break;
        case 'object':ret.push(data);break; //default
      }
    });
    return ret;
  },

 encode_urlencode: {
  transport_callback:'send',
  encode:function(hash){
    var str = [];
    hash.each(function(val){
        str.push(val.key + '=' + encodeURIComponent(val.value));
    });

    this.addHeaders({
        'Content-Type'   : 'application/x-www-form-urlencoded',
        'Content-Length' : str.length
    });

    return str.join('&');
  }
 }

});
