var WTextboxList = new Class({
  Extends: TextboxList,
  initialize:function(element, options){
    options = options || {};
    var tmp = {
      bitsOptions : {
        editable : { 
          growing:false
        }
      }
    };
    options = $merge(tmp, options);
    this.parent(element, options);
  }
});
