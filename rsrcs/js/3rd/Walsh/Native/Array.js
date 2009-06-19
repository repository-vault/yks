Array.implement({
  shuffle:function() {
    this.sort(function (x,y) { return Math.random()*3-1; });
    return this;
  }
});

