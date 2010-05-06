Fx.Elements.implement({

	start: function(obj){
		if (!this.check(obj)) return this;
		var from = {}, to = {}, tto={};
		for (var i in obj){
			var iProps = obj[i], iFrom = from[i] = {}, iTo = to[i] = {}, itto= tto[i]={};
			
			for (var p in iProps){
				tto[i][p]=iProps[p][1]+1;
				var parsed = this.prepare(this.elements[i], p, iProps[p]);
				iFrom[p] = parsed.from;
				iTo[p] = parsed.to;
			}
		}
		this.end=tto;
		return this.parent(from, to);
	}

});