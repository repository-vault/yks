var Nav = new Class({



  initialize:function(anchor){

        //this is all styled via nav.css, thx
    anchor.getElements('li a').each(function(el){
        el.opened_height = el.getHeight();
        el.collapse_height = el.getElement('*').getHeight();
        el.setStyle('height', el.collapse_height);
    });

    anchor.getElements('li').addEvent('mouseenter',function(){
        if(this.fx_opening) return;

        var a = this.getElement('a');
        this.fx_opening = a.effect('height').start(a.collapse_height,a.opened_height).chain(function(){
            this.fx_opening = false;
        }.bind(this));
    });

    anchor.getElements('li').addEvent('mouseleave',function(){
        if(this.fx_opening) {this.fx_opening.cancel(); this.fx_opening = false; }
        if(this.fx_closing) return;

        var a = this.getElement('a');

        var from = this.getHeight();
        this.fx_closing = a.effect('height').start(from,a.collapse_height).chain(function(){
            this.fx_closing = false;
        }.bind(this));
    });


 }

});