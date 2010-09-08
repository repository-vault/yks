var ColorPicker = new Class({
  Extends: MooRainbow,
  initialize:function(element, options){

    options = options || {};

    var id = "colorpicker_"+$uid(element);
    options.id = id;
    options.imgPath = '/?/Yks/Scripts/Contents|path://skin.js/3rd/Wooly_Sheep.net/MooRainbow/images/';


    element.addEvent('change', function(){
        this.manualSet(element.value.hexToRgb(true), 'rgb');
    }.bind(this));
    element.addEvent('keypress', function(){
        this.manualSet(element.value.hexToRgb(true), 'rgb');
    }.bind(this));

    options.startColor = element.value.hexToRgb(true);
    options.onChange = function(color){
        element.value = color.hex.substr(1);

    };

    this.parent(element, options);

  }
});