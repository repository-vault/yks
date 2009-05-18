/*
    reflection.js for mootools v1.2
    original by Christophe Beyls (http://www.digitalia.be) - MIT-style license
    modified by me - 131, same licence
*/

var Reflection = new Class({
  Declare : ['Reflection'],
  options: {
    height: 0.33,
    opacity: 0.8
  },
  initialize: function(img, options){ if(Reflection.extended(img))return;
    this.options=$merge(this.options,options);
    this.img=img;
    var loader= {bind:this};
    if (Browser.Engine.trident) loader.delay=50;
    this.preload=new Image();
    this.preload.onload = this.reflect.create(loader);
    this.preload.src = img.src;
  },

  reflect: function(){
    this.preload.onload=null;

    var width=this.img.width, height=this.img.height;
    var canvas, canvasHeight = Math.floor(height*this.options.height);
    var styles={width:width, 'position':'relative','margin-top':height,'margin-left':-width};

    if (Browser.Engine.trident){
        $(this.preload).setStyles($merge(styles,{
            'margin-bottom': -height+canvasHeight,
            'filter': 'flipv progid:DXImageTransform.Microsoft.Alpha(opacity='+(this.options.opacity*100)+', style=1, finishOpacity=0, startx=0, starty=0, finishx=0, finishy='+(this.options.height*100)+')'
        }) ).inject(this.img,'after'); return;
    } this.preload=null;
 
    canvas = $n('canvas', {'styles': $merge(styles,{height:canvasHeight})});
    if (!canvas.getContext) return; canvas.inject(this.img,'after');

    var context = canvas.setProperties({'width': width, 'height': canvasHeight}).getContext('2d');
    context.save();
    context.translate(0, height-1);
    context.scale(1, -1);
    context.drawImage(this.img, 0, 0, width, height);
    context.restore();
    context.globalCompositeOperation = 'destination-out';
    var gradient = context.createLinearGradient(0, 0, 0, canvasHeight);
    gradient.addColorStop(0, 'rgba(255, 255, 255, '+(1-this.options.opacity)+')');
    gradient.addColorStop(1, 'rgba(255, 255, 255, 1.0)');
    context.fillStyle = gradient;
    context.rect(0, 0, width, canvasHeight);
    context.fill();
  }
});

