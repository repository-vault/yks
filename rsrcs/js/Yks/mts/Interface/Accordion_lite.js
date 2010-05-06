
var Accordion_lite=new Class({
  Declare : 'Accordion_lite',

  options:{},
  active_index:0,
  transition:false,

  initialize:function(togglers,elements){

    this.elements = elements;
    this.togglers = togglers;

    this.size = elements[0].getParent().getSize();this.size.x-=10;
    elements.setStyles({width:0,height:this.size.y,float:'left',overflow:'hidden'});

    elements[0].setStyle('width',this.size.x);
    
    this.togglers[this.active_index].addClass('activated');
    togglers.each(function(toggler,index){
        toggler.addEvent('click',this.toggle.pass(index,this));

    },this);
  },
  toggle:function(next_index){
    if(this.transition ){
        this.transition.set(this.transition.end);
        this.transition.complete();
    }

    if(this.transition || next_index==this.active_index) return false;
    var toggleds=new Elements([this.elements[this.active_index],this.elements[next_index]]);

    this.togglers[this.active_index].removeClass('activated');
    this.togglers[next_index].addClass('activated');

    this.transition = new Fx.Elements(toggleds, {
        onComplete: function(){
            this.active_index=next_index; this.transition=false;
        }.bind(this),
        duration:500
    }).start({
        '0': { 'width': [this.size.x,0]},
        '1': { 'width': [0, this.size.x] }
    });

  }
  
});
 