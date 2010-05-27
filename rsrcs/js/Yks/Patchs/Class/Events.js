
Events.implement({
  replaceEvent: function(type, fn){
    return this.removeEvent(type, fn).addEvent(type, fn);
  }
});