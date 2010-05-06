var ShowCase = new Class({
  Declare : 'ShowCase',

  canvas:null,
  ctx:null,

  screens_list:[],

  angle_view:0,

  options : {
    canvas_width:600,
    canvas_height:300,
    rows:3,
    cols:4,
    angle_max:70,
    y_top:50,
    y_bottom:-50,
    height_pad:0.5,
    angle_pad:0.5
  },

  screen_options:{
    wireframe: false,
    subdivisionLimit: 2,
    patchSize: 50
  },

  $:{}, //cache

  initialize:function(canvas, options, screen_options ){

    this.canvas = canvas; this.ctx = this.canvas.getContext('2d');
    this.ctx.lineWidth = 1;

    $extend(this.options, options);
    $extend(this.screen_options, screen_options);

    for(var tmp=0;tmp<this.options.cols;tmp++){
        this.screens_list[tmp]=[];
    }

    var canvas_dims = {
        width:this.options.canvas_width,
        height:this.options.canvas_height
    };
    $extend($('canvas').style, canvas_dims);
    $extend($('canvas'), canvas_dims);

    // on calcule l'angle de tick en fonction du nombre de colonnes
    // la hauteur selon le nombre de ligne
    this.$.angle_tick = (this.options.angle_max/this.options.cols);
    this.$.box_height = (this.options.y_top-this.options.y_bottom)/this.options.rows;
    this.$.ray = 100;

    var angle_max_tmp = Math.rad(this.options.angle_max/2);
    //les focales sont calculées pour qu'aux extremitées, on soit à 100% de notre angle de vue
    this.$.Kx = this.options.canvas_width/(2*Math.tan(angle_max_tmp));
    this.$.Ky = (this.options.canvas_height * this.$.ray * Math.cos(angle_max_tmp) )
                / (2 *this.options.y_top);

    this.$.decX = this.options.canvas_width/2;
    this.$.decY = this.options.canvas_height/2;
    this.$.height_inner = this.$.box_height - (this.options.height_pad*2);
    this.$.angle_tick_inner = this.$.angle_tick - (this.options.angle_pad*2);
    this.angle_view = 0;    
  },

  newGrid:function(points){
    var decX = this.$.decX, decY = this.$.decY;
    return points.map(function(a){ return [decX+a[0], decY-a[1]] });
  },



    // Creer un shell autour de l'image, et l'injecte dans le showcase
  adopt:function(image, x, y){
    var shell = this.screen_shell(image);
    this.screens_list[x][y] = shell;
    this.draw(shell, x, y);
    return shell;
  },


  screen_shell:function(image){
    var canvas_tmp= new Element('canvas').addClass('screen').inject(this.canvas, 'before');
    var screen_tmp = new Canvas(canvas_tmp, this.screen_options);
    canvas_tmp.addEvent('mouseover', function(){
        this.ctx.lineWidth = 8;
       //this.ctx.square(this.points).stroke();;
        this.ctx.lineWidth = 1;
    }.bind(screen_tmp));
    screen_tmp.setImage(image);
    return screen_tmp;
  },


  draw:function(shell, col, row, trace){
    var angle = -this.options.angle_max/2 + this.$.angle_tick * col,
        y = this.options.y_top - this.$.box_height * row;

    var points = this.ctx.box_projection_2d(
        Math.rad(angle+this.angle_view + this.options.angle_pad),
        Math.rad(this.$.angle_tick_inner),
        y-this.options.height_pad,
        this.$.height_inner,
        this.$.ray,
        this.$.Kx,
        this.$.Ky
    ), points_newgrid = this.newGrid(points);

    shell.perspective(points_newgrid);
    //this.ctx.lineWidth = 3;
    //this.ctx.square(points_newgrid).stroke();

  },

  redraw:function(angle_view){
    this.angle_view = angle_view || 0;
    for(var col=0; col<this.screens_list.length; col++) {
      for(var row=0; row<this.options.rows; row++) {
        var shell = this.screens_list[col][row];
        if(shell) this.draw(shell, col, row);
      }
    }

  }
});




