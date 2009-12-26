
var Palette = new Class({
  map:null,

  initialize:function(map, els, keys){

    this.map = map;
    this.els = els;

    this.els.each(function(el, i){
        var brush_key = keys[i] || el.get('brush'),
            brush = new Brush(brush_key);
        this.map.brush_adopt(brush);
        el.store('brush', brush);
        el.addClass(brush_key);
        el.addEvent('click', this.select.pass([el], this));
    }.bind(this));

  },

  select:function(el){
    this.els.removeClass('selected');
    $(this.map).addClass('ready');
    el.addClass('selected');
    this.map.brush_select(el.retrieve('brush'));
  }
});


