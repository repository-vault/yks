
Function.implement({
  stack : function(args, thisArg){
    return function(){
        var tmp = args.extend($A(arguments));
        this.apply(thisArg, tmp);
    }.bind(this);
  }
});
