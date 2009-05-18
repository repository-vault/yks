

Slider = new Class({
	Extends:Slider,
	initialize:function(element, knob, options){
		this.parent.call(this,element, knob, options);

		if(options.name){
			this.input=$n('input',{type:'hidden',name:options.name}).inject(this.element);
			this.addEvent('onChange', function(step){this.input.value=step}.bind(this));
		}
	}
});

