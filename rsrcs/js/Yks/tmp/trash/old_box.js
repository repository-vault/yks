
    if(this.glue=anchor.getElement('.box_glue')){
      var tmp={handle:$E('.noia0_rd',anchor).addClass('resized').adopt($n('div'))};
      if(this.glue.title){
        $extend(tmp,Urls.jsx_eval(this.glue.title));this.glue.title='';
      }
      tmp=anchor.makeResizable(tmp);
      tmp.addEvent("onBeforeStart",function(){
        this.anchor.setStyles(this.anchor.getStyles('width','height')); //no deformation
        this.alt=anchor.retrieve('alt',
            $n('div',{styles:{'width':'100%','height':'100%'}})).replaces(this.glue);
      }.bind(this));
      var func=function(){
        this.glue.style.width=(this.anchor.style.width.toInt()-30)+'px';
        this.glue.style.height=(this.anchor.style.height.toInt()-32)+'px';
        this.glue.replaces(this.alt);
      }.bind(this);
      tmp.addEvent("onComplete",func);
      tmp.addEvent("onCancel",func);
    }


  save:function(){
    var url_save='/?/Yks/Box';
    var params=$merge( this.getPosition(), {
        ks_action:'box_save',
        screen_url:href_ks,
        box_name:this.box_name,
        box_url:this.url
    });http_lnk('post',url_save,params,$empty);
  }