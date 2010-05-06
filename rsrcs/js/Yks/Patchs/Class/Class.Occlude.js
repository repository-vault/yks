if(document.createElementNS && document.documentElement.namespaceURI){
  Document.implement({
    createElement:function (tag){
      return this.createElementNS(this.documentElement.namespaceURI, tag);
  }});
}


Class.Mutators.Declare = function(name){
        //thx keeto
    this.prototype.$family = {name: name};
    return this;
};


Class.Mutators.Occlude = function(name){

    this.prototype.$family = {name: name};

    if($defined(this.prototype.occluded))
        return this;

    this.prototype.occlude = function(element, property){
        element  = document.id(element || this.element);
        property = name || property || this.property;

        var instance = element.retrieve(property);
        if (instance && !$defined(this.occluded))
            return this.occluded = instance;

        this.occluded = false;
        element.store(property, this);
        return this.occluded;
    }.bind(this);
    return this;
};



