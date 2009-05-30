// Caroussel is the main application, while Showcase is only a viewport

var Carousel = new Class({
  Declare : ['Carousel'],
  Implements: [Events],

  movies_urls:[],
  movies_ids:[],

  showcase:null,
  showcase_options:{},

  start:0,
  by:null,
  elements:0,

  options: {
    rows:3,
    cols:4
  },

  initialize:function( canvas, options) {
    $extend(this.options, options);
    this.showcase = new ShowCase(canvas, this.options);

    this.by = this.options.cols * this.options.rows; 
    this.start = 0;
    this.addEvent('forward', this.next.pass(true,this));
    this.addEvent('backward', this.next.pass(false,this));

  },

  feed_images:function(images_hash){
    images_hash = $H(images_hash);
    this.images_urls = images_hash.getValues();
    this.images_ids = images_hash.getKeys();

    var end = this.start+this.by,
        tmp_urls = this.images_urls.slice(this.start, end),
        tmp_ids = this.images_ids.slice(this.start, end);

    var carousel = this;

    new Asset.images(tmp_urls, {
        onProgress:function(nb,idx) {
            carousel.adopt_fill(this, tmp_ids[idx]);
        }
    });
  },

  adopt_fill:function(image,id){
    var i=this.elements++, col=i%this.options.cols, row=(i-col)/this.options.cols;
    this.adopt(image, col, row, id); 
  },

  adopt:function(image, col, row, id){
    var screen_tmp = this.showcase.adopt(image, col, row);
    this.fireEvent('adoption', [screen_tmp, id]);

  },

  next:function(forward){

    var dec = this.options.rows, istart=0;
    if(forward){
        this.start += dec;
        istart = this.start+this.by;
    }
    else {
        this.start -= dec;
        istart = this.start-dec;
    }

    var iend = istart+dec,
        tmp_urls = this.images_urls.slice(istart, iend),
        tmp_ids = this.images_ids.slice(istart, iend);


    var carousel = this;

    var images_ready = Asset.images(tmp_urls, {
        onComplete:function() {
            carousel.adopt_next(images_ready, forward, tmp_ids);
        }
    });
  },

  adopt_next:function(images, forward, tmp_ids){
    var col, angle_tick = this.showcase.$.angle_tick, angle_max, step=5;

        //on injecte les images + shell où elles doivent apparaitre
    if(forward) {
        col = this.showcase.screens_list.push([])-1;
        angle_start = 0; angle_max = -angle_tick; step *= -1;
    } else {
        col = 0; this.showcase.screens_list.unshift([]);
        angle_start = -angle_tick; angle_max = 0;
    }

    this.showcase.redraw(angle_start);
    for(var y=0; y<images.length;y++){
       this.adopt(images[y], col, y, tmp_ids[y]);
    }

    this.dec = angle_start;

    this.time = setInterval(function(){
        if(forward?(this.dec<=angle_max):(this.dec>=angle_max))
            return this.nextdone(forward);
        this.dec += step;
        this.showcase.redraw(this.dec);
    }.bind(this) ,10);
  },

  nextdone:function(forward){
    $clear(this.time);
    (this.showcase.screens_list[forward?'shift':'pop'])().each(function(e){
        e.dispose();
    }); //yeah
    this.showcase.redraw();
  }


});