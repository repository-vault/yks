
var URIescaped =['&ks'+'pc;','%25','%26','%2B'].associate( ['%','&ks'+'pc;',"&","\\+"]);

String.prototype.old_split = String.prototype.split;

try {
delete String.prototype.contains;
} catch(e){}

String.implement({

  contains: function(string, separator){
    return (separator) ? (separator + this + separator).indexOf(separator + string + separator) > -1 : this.indexOf(string) > -1;
  },

  split: function(separator,limit){
    var tmp = this.old_split(separator);
    if(limit == undefined) return tmp;
    limit--;
    res = tmp.slice(0,limit);
    res[limit] = tmp.slice(limit).join(separator);
    return res;
  },

  areplace: function(h) {
    var tmp = this;
    for(var k in h) tmp = tmp.replace(new RegExp(k,"g"),h[k]);
    return tmp;
  },

  trim: function(flag){
    if(!$defined(flag))flag="\\s";
    return this.replace(new RegExp('^['+flag+']+|['+flag+']+$','g'),'');
  },

  startsWith: function(str){
     return (this.indexOf(str) === 0);
  },

  rot13:function(){
    return this.replace(/[a-zA-Z]/g,function(c){
        return String.fromCharCode((c<="Z"?90:122)>=(c=c.charCodeAt(0)+13)?c:c-26);});
  },

  reduce: function(regex){
     regex = ((typeof regex == 'string') ? new RegExp(regex, null) : regex);
     return this.match(regex)[0];
  },

  specialchars_decode:function(){
    var out = this;
    var replace = {"&amp;":"&", "&lt;":"<", "&gt;":">", "&quot;":'"' };
    var re = new RegExp("&#([0-9]+);", "g");
    var match;
    while (match = re.exec(out)) {
      out = out.replace(match[0], String.fromCharCode(match[1]));
    }
    return out.areplace(replace);
  }

});

