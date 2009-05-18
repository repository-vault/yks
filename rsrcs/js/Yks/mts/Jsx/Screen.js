var Screen = {
  screen_id:screen_id,

  panel:false,	//panel box
  box_zImax:90,

  boxes_list:{},
  lambda_box:0,
  box_focus:false,
  modal_lvl:0,

  get_lambda_box:function(){ return "lambda_"+(this.lambda_box++); },

  initialize:function(){
    document.addEvent('keydown',function(e){
        if(e.code!=27) return;
        if(Screen.box_focus)
            Screen.box_focus = Screen.box_focus.close();
    });

    if(!document.body) document.body = $E('body');
    Doms.scan();
  },

  boxer:function(anchor, options){
    var old = this.boxes_list[options.box_name],tmp = false;
    if(old){
        tmp = new Box( anchor.replaces(old.anchor.fireEvent('unload')), $merge(options,
            old.fly?{modal_box:old.modal_box,fly:true,place:old.getPosition()}:{fly:false},
            old.opener && (options.opener.box_name==options.box_name)?{opener:old.opener}:false
        ));
        for (var type in old.$events) { //clone events
            old.$events[type].each(function(fn){ tmp.addEvent(type, fn); }, this);
        }
    } else tmp = new Box( anchor.inject($('container')),options);
    Doms.scan(anchor);
    if(old) tmp.fireEvent('reloaded');
    return tmp;
  },



  modaler:function(options){
    var scroll_size = getScrollSize();
    Screen.modal_lvl++;

    return $n('div',{'class':'modal_mask',styles:{
        'opacity':0.5,
        'z-index':Screen.box_zImax-1,
        'height':scroll_size.y,
        'width':scroll_size.x
      }}).addEvent('onRemove',function(){Screen.modal_lvl--;}
      ).inject($('container'));
  }
};
