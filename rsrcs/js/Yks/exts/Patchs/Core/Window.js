
Window.implement({

  $E: function(selector,filter){
    return ($(filter) || $(this.document.documentElement)).getElement(selector);
  },

  $v: function(a,b){
    if(b!=undefined)$(a).value=b;else return $(a).value;
  },

  $n: function(a,b){
    var tmp = new Element(a);
    return $defined(b)?tmp.set(b):tmp;
  },

  $N: function(a){
    return $E('*[name="'+a+'"]');
  },

  $take:function(obj, path){
    for(var tmp = obj, i=0, l=path.length;i<l && tmp;i++)
        tmp = tmp[path[i]];
    return tmp;
  },

  encodeURIComponent: function(str) {
    return (''+str).areplace(URIescaped);
  }

});



  //here are some ugly functions / stuffs 'im not happy with.
function stop(event){ if(event)(new Event(event)).stop(); };
function chr(a) {  return String.fromCharCode(a); };
function autoq(elem,base) { if(elem.value==base)elem.value=''; };
function $equal(obj1,obj2) { var eq=true; for(k in obj1) eq&=(obj1[k]==obj2[k]); return eq; };
