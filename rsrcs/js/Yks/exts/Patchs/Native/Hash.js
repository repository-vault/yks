
Hash.implement({
  sum:function(){
    var base=0;
    this.each(function(val){base+=val; });
    return base;
  }
});
