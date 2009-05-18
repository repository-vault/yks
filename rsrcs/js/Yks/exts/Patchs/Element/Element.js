
Element.implement({
  dispose: function(){
    if($(this.fireEvent)) this.fireEvent("onRemove");
    return (this.parentNode) ? this.parentNode.removeChild(this): this;
  },

  effect: function(property,options){
    return new Fx.Tween(this,$merge(options,{property:property}));
  },
  
  effects: function(options){
    return new Fx.Morph(this,options||{});
  },

  get: function(prop,force){
    if(force) return this.getAttribute(prop, 2);
    var property = Element.Properties.get(prop);
    return (property && property.get) ? property.get.apply(this, Array.slice(arguments, 1)) : this.getProperty(prop);
  },
    
  toQueryString: function(){
    var queryString = [];
    this.getElements('input[type!=image], select, textarea', true).each(function(el){
      if (!el.name || el.disabled) return;
      var tmp, value = (el.tagName.toLowerCase() == 'select')
        ? ( (tmp = Element.getSelected(el).map( function(opt){ return opt.value;} ))
             && tmp.length ? tmp : [''] )
        : ((el.type == 'radio' || el.type == 'checkbox') && !el.checked) ? null : el.value;
      $splat(value).each(
        function(val){ queryString.push(el.name + '=' + encodeURIComponent(val)); }
      );
    });
    return queryString.join('&');
  }

});
