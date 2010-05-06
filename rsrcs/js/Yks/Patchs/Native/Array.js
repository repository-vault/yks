Array.implement({
  exclude: function(item){
    if (!this.contains(item)) return this;
    this.remove(item);
    return this;
  },
  diff:function(map){
    map.each(this.remove.bind(this));
    return this;
  },
  remove:function(item){
    var key = this.indexOf(item);
    if(key == -1) return this;
    return this.splice(key, 1);
  }
});