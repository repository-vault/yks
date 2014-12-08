
Element.implement({
  dispose: function(){
     try {
      if(this.fireEvent) document.id(this).fireEvent("onRemove");
        //legacy code here
      return (this.parentNode) ? this.parentNode.removeChild(this): this;
    } catch(err) {
      return this;
    }
  },

  effect: function(property,options){
    return new Fx.Tween(this,$merge(options,{property:property}));
  },

  getInputs:function(arg, nocash){
    return this.getElements('input[type!=image][type!=submit], textarea, select', nocash);
  },

  effects: function(options){
    return new Fx.Morph(this,options||{});
  },

  get: function(prop,force){
    if(force) return this.getAttribute(prop, 2);
    //legacy code here
    var property = Element.Properties.get(prop);
    return (property && property.get) ? property.get.apply(this, Array.slice(arguments, 1)) : this.getProperty(prop);
  },
  
  toQueryList: function(){
    var queryList = [];
    this.getInputs(false, true).each(function(el){
      if (!el.name || el.disabled) return;
      var tmp, value = (el.tagName.toLowerCase() == 'select')
        ? ( (tmp = Element.getSelected(el).map( function(opt){ return opt.value;} ))
             && tmp.length ? tmp : [''] )
        : ((el.type == 'radio' || el.type == 'checkbox') && !el.checked) ? null : el.value;
      $splat(value).each(
        function(val){ queryList.push({key:el.name,value:val}); }
      );
    });
    return queryList;
  },


  toQueryString: function(){
    //better implementation of form specials (input/image & submit & co)
    return Xhr.encode_urlencode(this.toQueryList());
  },

  removeAll:function(){
    var ctn = [];
    for(var i = 0, end = this.childNodes.length ; i< end; i++)
      ctn.push(this.removeChild(this.firstChild));  
    return ctn;
  },

  blink:function(times){
    if(!times) return;
    this.setStyle('visibility', 'hidden');
    (function(){
        this.setStyle('visibility', 'visible');
        this.blink.delay(200, this, times-1);
    }).delay(200, this);
  }

});



Element.FixedDelayed = function(el){

    var ctn = el.removeAll();

    var coords = el.getCoordinates();

    var stuff =  {
      position:'fixed',
      top:coords.top,
      left:coords.left,
      right:window.innerWidth - coords.right,
      bottom:window.innerHeight - coords.bottom,
      width:'auto',
      height:'auto',
      display:'block'
    }; el.setStyles(stuff);

    Array.each(ctn, function(node){el.appendChild(node)});

};