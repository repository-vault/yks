
var Thumbnailer=new Class({
  Implements:[Events],
  Declare : ['Thumbnailer'],

  resize_mode:false,
  move_mode:false,
  limits:{w:20,h:20},
  clip:{xr:0,xl:0,yu:0,yd:0},

  initialize:function(img, options){  if(Thumbnailer.extended(img)) return;
    this.options=options||{};
    this.img=img;

    var size=this.img.getSize();
        size=this.img.getStyles('width','height');

        var params={cursor:'move',position:'relative',backgroundImage:'url('+img.src+')'};
        this.container=$n('div',{styles:$merge(size,params) });


        this.container.replaces(this.img);

        var maskStyles = $merge({
            cursor :'default',
            position : 'absolute',
            overflow: 'hidden',
            'background-color' : 'black',
            opacity :0.3}, options.maskstyle
        );

        this.north = $n("div", {'styles' : $merge(maskStyles,{width:size.width})}).inject(this.container);
        this.south = $n("div", {'styles' : $merge(maskStyles,{bottom:'0px',width:size.width})}).inject(this.container);
        this.east =  $n("div", {'styles' : maskStyles}).inject(this.container);
        this.west =  $n("div", {'styles' : maskStyles}).inject(this.container);


    var completed=function(){
        this.calibrate(); this.interval=this.calibrate.periodical(1000,this);
        //this.container.setStyles({width:this.coords.width+'px',height:this.coords.height+'px'});
        if(this.options.start){this.clip=$merge(this.options.start); this.mask_draw(); }

        this.container.addEvent('mousedown',this.mousedown.bind(this));
        this.container.addEvent('dblclick',this.clear.bind(this));

        this.container.addEvent('mouseup',function(event){
            this.resize_mode=this.move_mode=false;
            if($equal(this.old,this.clip))this.clear();
            this.fireEvent('change',this.clip);
        }.bind(this));
        this.container.addEvent('mousemove',this.mask_move.bind(this));
        //this.mask_draw.delay(100,this);
    }.bind(this);
    //if(img.complete) completed(); else img.addEvent("load",completed);
    completed();

  },

  clear:function(){ this.fireEvent('reset'); this.clip={xr:0,xl:0,yu:0,yd:0};this.mask_draw(); },
  calibrate:function(){ this.coords=this.container.getCoordinates(); },
  remove:function(){ $clear(this.interval); },
  
  mousedown:function(event){



    this.old=$merge(this.clip);

    this.clic={x:event.page.x-this.coords.left,y:event.page.y-this.coords.top};
    if(this.clic.x.between(this.clip.xl,this.clip.xr) && this.clic.y.between(this.clip.yu,this.clip.yd)) { //inside


        this.clip.w=this.clip.xr-this.clip.xl; this.clip.h=this.clip.yd-this.clip.yu;
        this.clip.decx = this.clic.x -this.clip.xl; this.clip.decy = this.clic.y -this.clip.yu;

        if(this.delay) this.mask_max();
        if(this.resize_mode) this.move_mode=true;
        this.resize_mode=false;if(!this.delay) this.move_mode=!this.move_mode;

    } else {
        this.resize_mode=true;
        this.move_mode=false;
        this.delay=true; (function(){this.delay=false;}).delay(300,this);
    }

  },

  mask_move:function(event){ 
    var posx= (event.page.x-this.coords.left), posy= (event.page.y-this.coords.top);

        if(!(this.resize_mode || this.move_mode)) return;


    if(this.resize_mode){
            //4 rules on SHIFT[0|1] * CONTROL[0|1]
            //rule (1,0)~50%; (0,0),(1,1) ok

        if(event.control){
            if(event.shift){
                this.clip.r=Math.max(this.limits.w/2,Math.max(Math.abs(posx-this.clic.x),Math.abs(posy-this.clic.y)));
                this.clip.r=Math.min(this.clip.r,this.clic.x,
                       this.clic.y,this.coords.width-this.clic.x,this.coords.height-this.clic.y);
                this.clip.xl=this.clic.x-this.clip.r; this.clip.xr=this.clic.x+this.clip.r;
                this.clip.yu=this.clic.y-this.clip.r; this.clip.yd=this.clic.y+this.clip.r;
           }

        }else {
                this.clip.xl=Math.max(0,Math.min(this.clic.x,posx,this.coords.width));
                this.clip.xr=Math.min(this.coords.width,Math.max(this.clic.x,posx));
                this.clip.yu=Math.max(0,Math.min(this.clic.y,posy,this.coords.height));
                this.clip.yd=Math.min(this.coords.height,Math.max(this.clic.y,posy));

            if(event.shift){
                this.clip.d=Math.max(Math.abs(this.clip.xr-this.clip.xl),Math.abs(this.clip.yd-this.clip.yu));
                this.clip.d=Math.min(this.clip.d,this.coords.width-this.clip.xl,this.coords.height-this.clip.yu);
                this.clip.yd=this.clip.yu+this.clip.d; this.clip.xr=this.clip.xl+this.clip.d;
            }

        }
    } else if(this.move_mode){
        posx-=this.clip.decx;  posy-=this.clip.decy;
        this.clip.xl=Math.max(0,Math.min(posx,this.coords.width-this.clip.w));
        this.clip.yu=Math.max(0,Math.min(posy,this.coords.height-this.clip.h));

        this.clip.xr=this.clip.xl+this.clip.w; this.clip.yd=this.clip.yu+this.clip.h;

    } this.mask_draw();
  },

  mask_draw:function(){
        this.clip.w=this.clip.xr-this.clip.xl; this.clip.h=(this.clip.yd-this.clip.yu);

        this.north.setStyle('height', this.clip.yu+'px' );
        this.south.setStyle('height', (this.coords.height-this.clip.yd)+'px');
        this.east.setStyles({height: this.clip.h+'px', width: (this.coords.width-this.clip.xr)+'px',  top: this.clip.yu +'px', left: this.clip.xr+'px'});
        this.west.setStyles({height: this.clip.h+'px', width: this.clip.xl+'px', top: this.clip.yu+'px'});

        // window.status=print_r(this.clip);
  },
  mask_max:function(){
    this.clip.w=Math.min(this.coords.width,this.coords.height);
    this.clip.x=this.clip.y=Math.floor(this.clip.w/2);
  },

  transform : function(el){
    var tmp = el.getCoordinates(this.container);
    this.clip={xr:tmp.right,xl:tmp.left,yu:tmp.top,yd:tmp.bottom};
    this.mask_draw();
    this.fireEvent('change',this.clip);
  }
});

Math.sign=function(a){ return a<0?-1:1; }
