var Planning = new Class({
  Binds: ['brush_lambda_apply', 'brush_select', 'stop'],
  Declare: 'Planning',

  paint_area_mask:".cell",

  draw:false, // le bouton de la souris est il pressé ??

  brushes_list:{},
  brush:null,

  initialize:function(anchor){
    this.anchor = $(anchor);

     this.anchor.addEvent('mouseup', this.stop)
    .addEvent('mouseleave', this.stop)
    .addEvent('mousedown',function(ev){ new Event(ev).stop();
        if(!this.brush) return;
        this.draw = true;
        //this.anchor.setStyle('cursor','crosshair');
        }.bind(this))
    .addEvent('mousemove',function(event){
        new Event(event).stop();
        if(this.draw && this.brush && $(event.target).match(this.paint_area_mask))
          this.brush.use($(event.target),this.brush,event);
        }.bind(this))
    .addEvent('click',function(event){
        if(this.brush && $(event.target).match(this.paint_area_mask))
          this.brush.use($(event.target),this.brush);
        }.bind(this))
    .addEvent('dblclick',function(ev){
        if(this.brush && $(event.target).match(this.paint_area_mask))
          this.brush.use($(event.target),this.brush);
        }.bind(this))
    ;

  },


  load:function(data, key ){
    key = key || 'key';
    var id, brush;
    this.getElements().each(function(el){
        id = el.get(key); brush = data[id];
        if(! $defined(this.brushes_list[brush]) ) return;
        this.brushes_list[brush].use(el);
    }.bind(this));
  },

  getElements:function(){
    return this.anchor.getElements(this.paint_area_mask);
  },

  brush_lambda_apply:function(cell, brush, event){
    var base = cell.retrieve('base_class', cell.className );
    cell.className = base;
    if(event && event.event && event.event.ctrlKey) cell.store('value', '');
    else  cell.addClass(this.key).store('value', this.key);
  },

    // existing table initialization
  brush_adopt:function(){
    $A(arguments).each(function(brush){
        if($type(brush)=="string") brush = {key:brush, use:this.brush_lambda_apply};
        else if($type(brush)!="Brush") return false;
        this.brushes_list[brush.key] =  brush;

        this.cell_cleanup(brush);
    }, this);
  },

  cell_cleanup:function(brush){
    this.anchor.getElements(this.paint_area_mask+"."+brush.key).each(function(cell){
        cell.removeClass(brush.key);
        var base = cell.store('base_class', cell.className );
        cell.addClass(brush.key).store('value', brush.key);
    });
  },

  stop:function(ev){
    if(!this.brush) return;
    this.draw = false;
    //this.anchor.setStyle('cursor','pointer');
  },

  brush_select:function(brush_key){
    var brush;
    if($type(brush_key) == 'Brush') brush_key = brush_key.key;
    if(!(brush = this.brushes_list[brush_key])) return false;
    this.brush = brush;
  },

  toElement:function(){
    return this.anchor;
  }

});


