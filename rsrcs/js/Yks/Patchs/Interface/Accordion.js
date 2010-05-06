


Accordion.implement({
  display:function(index, element){
    var previous = index != this.previous;
    return this.parent.apply(this, arguments).chain(function(){
        if(previous) this.elements[index].setStyle('height', '');
    });
  }
});
