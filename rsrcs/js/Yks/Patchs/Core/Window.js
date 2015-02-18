Browser.Features.files = !!window.File;

Window.implement({
  $: function(el, nc){
    if(el && el.toElement) return el.toElement();
    return document.id(el, nc, this.document);
  },

  $E: function(selector,filter){
    return (document.id(filter) || document.id(this.document.documentElement)).getElement(selector);
  },

  $v: function(a,b){
    if(b!=undefined)document.id(a).value=b;else return document.id(a).value;
  },

  $n: function(a,b){
    var tmp = new Element(a);
    return $defined(b)?tmp.set(b):tmp;
  },

  $N: function(a, filter){
    return $E('*[name="'+a+'"]', filter);
  },

  $take:function(obj, path){
    for(var tmp = obj, i=0, l=path.length;i<l && tmp;i++)
        tmp = tmp[path[i]];
    return tmp;
  },

  encodeURIComponent: function(str) {
    return (''+str).areplace(URIescaped);
  },

  $uniqueID:function(length){
    return ("abcdef0123456789"+$time()).split('').shuffle().join('').substr(0,length||8);
  },

  $instanceOf:function(item, object){
    if (item == null) return false;
    var constructor = item.$constructor || item.constructor;
    while (constructor){
        if (constructor === object) return true;
        constructor = constructor.parent;
    }
    return item instanceof object;
  }

});



  //here are some ugly functions / stuffs 'im not happy with.
function stop(event){ if(event)(new Event(event)).stop(); };
function chr(a) {  return String.fromCharCode(a); };
function autoq(elem,base) { if(elem.value==base)elem.value=''; };
function $equal(obj1,obj2) { var eq=true; for(k in obj1) eq&=(obj1[k]==obj2[k]); return eq; };
