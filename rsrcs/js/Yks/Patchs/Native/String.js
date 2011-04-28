
var URIescaped =['&ks'+'pc;','%25','%26','%2B'].associate( ['%','&ks'+'pc;',"&","\\+"]);

String.prototype.old_split = String.prototype.split;

String.implement({
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

  reduce: function(regex){
     regex = ((typeof regex == 'string') ? new RegExp(regex, null) : regex);
     return this.match(regex)[0];
  }

});
