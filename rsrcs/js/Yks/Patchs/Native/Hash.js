
Hash.implement({
  sum:function(){
    var base=0;
    this.each(function(val){base+=val; });
    return base;
  },

  toQueryList: function(base){
    var queryList= [];
    Hash.each(this, function(value, key){
        if (base) key = base + '[' + key + ']';
        var result;
        switch ($type(value)){
            case 'object': result = Hash.toQueryList(value, key); break;
            case 'array':
                var qs = {};
                value.each(function(val, i){
                    qs[i] = val;
                });
                result = Hash.toQueryList(qs, key);
            break;
            default: result = [{key:key, value:value}];
        }
        if (value != undefined) queryList.extend(result);
    });
    return queryList;
  }
});
