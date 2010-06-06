Xhr.implement({

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
