
Function.implement({
  stack : function(args, thisArg){
    return function(){
        var tmp = args.extend($A(arguments));
        this.apply(thisArg, tmp);
    }.bind(this);
  },

 curry : function() {
    var args = $A(arguments), method = this;
    return function() {
        return method.apply(this, args.concat($A(arguments)));
    };
  }
});
