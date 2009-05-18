
Element.implement({
  select:function(start, end){
    if(this.get('tag')!='input') return;
    if(!Browser.Engine.trident) 
        return input.setSelectionRange(start, end);
    var range = input.createTextRange();
    range.collapse(true);
    range.moveStart("character", start);
    range.moveEnd("character", end - start);
    range.select();
  }
});


