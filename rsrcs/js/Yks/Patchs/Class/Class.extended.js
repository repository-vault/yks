Class.prototype.extended = function(el){
    if(!$defined(this.done)) this.done = {};
    if(this.done['-'+el.uid]) return true;
    return !(this.done['-'+el.uid] = true);
};

if(document.createElementNS && document.documentElement.namespaceURI){
  Document.implement({
    createElement:function (tag){
      return this.createElementNS(this.documentElement.namespaceURI, tag);
  }});
}

Class.Mutators.Declare = function(self, test){
    self.$family = {'name':test};
    return self;
};


