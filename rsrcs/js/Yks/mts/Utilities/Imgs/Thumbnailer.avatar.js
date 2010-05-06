
var Thumbnailer=new Class({
  Occlude : 'Thumbnailer',

  resize_mode:false,
  move_mode:true,
  limits:{w:20,h:20},
  clip:{x:0,y:0,w:0,h:0},

  initialize:function(img, options){
    if(this.occlude(img)) return;

	this.options=options||{};
	this.img=img;

	this.container=$n('div',{'class':'mask_zone','styles':{position:'relative'}}
		).inject(img.getParent()).adopt(img.setStyles({'position':'absolute'}));
	this.mask=$n('img',{src:img.src,styles:{'position':'relative'} } ).inject(this.container).setStyle('opacity',0.3);

	this.clip.x=this.clip.y=Math.floor((this.clip.w=this.limits.w)/2);
	this.radius=Math.floor(this.clip.w/2); this.cursor('move');

	this.img.addEvent("onRemove",this.remove.bind(this) );
	var completed=function(){
		this.calibrate();this.interval=this.calibrate.periodical(1000,this);
		this.container.setStyles({width:this.coords.width+'px',height:this.coords.height+'px'});
		this.container.addEvent('dblclick',this.toggle_resize.bind(this));
		this.container.addEvent('click',this.toggle_move.bind(this));
		this.container.addEvent('mousemove',this.mask_move.bind(this));
		this.mask_draw.delay(100,this);
	}.bind(this);
	if(img.complete) completed(); else img.addEvent("load",completed);

  },
  calibrate:function(){ this.coords=this.mask.getCoordinates(); },
  remove:function(){ $clear(this.interval); },

  get_clip:function(){
  	var params=[this.clip.x-this.radius, this.clip.y-this.radius, this.clip.w, this.clip.w].join(";"); //h=w
	return params;
  },

  toggle_resize:function(){
	this.resize_mode=true;
	this.move_mode=false;
	this.cursor('se-resize');
	this.delay=true; (function(){this.delay=false;}).delay(300,this);
  },
  toggle_move:function(){
	if(this.delay) this.mask_max();
	if(this.resize_mode) this.move_mode=true;
	this.resize_mode=false;if(!this.delay) this.move_mode=!this.move_mode;
	this.cursor(this.move_mode?'move':'');
	
  },
  mask_move:function(event){ 
	
	var posx= (event.page.x-this.coords.left), posy= (event.page.y-this.coords.top);
	if(this.resize_mode){
		this.clip.w=Math.max(this.limits.w,Math.max(posx-this.clip.x,posy-this.clip.y)*2);
		this.clip.w=Math.min(this.clip.w,Math.min(Math.min(this.clip.x,this.clip.y),Math.min(this.coords.width-this.clip.x,this.coords.height-this.clip.y))*2);
		this.radius=Math.floor(this.clip.w/2);
	}else if(this.move_mode){
		this.clip.x=Math.max(this.radius, Math.min(posx,this.coords.width-this.radius));
		this.clip.y=Math.max(this.radius, Math.min(posy,this.coords.height-this.radius));
	} this.mask_draw();
  },
  mask_draw:function(){
	var px=(this.clip.x-this.radius), py=(this.clip.y-this.radius);
	this.img.style.clip=tmp="rect("+py+"px "+(px+this.clip.w)+"px "+(py+this.clip.w)+"px "+(px)+"px )";
  },
  mask_max:function(){
	this.clip.w=Math.min(this.coords.width,this.coords.height);
	this.clip.x=this.clip.y=Math.floor(this.clip.w/2);
	this.radius=Math.floor(this.clip.w/2); this.mask_draw();
  },
  cursor:function(mode){ this.container.setStyle('cursor',mode); }
});

