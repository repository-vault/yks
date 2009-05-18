
Wyzzie.implement({
  smileys:{
	list:[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21],
	open:function(){
		if(this.smileys_box)return false;
		this.smileys_box=$n("div",{'class':"smileys_box"});
		var close=$n("img",{src:'/css/Kse/imgs/close_popup.png','class':'close'});
		close.inject(this.smileys_box);close.addEvent("click",this.smileys.close.bind(this));
		var path=site_url+"/imgs/Kse/smileys/yks";
		this.smileys.list.each(function(item){
			var pict=$n("img",{src:path+"/"+item+".png"}).inject(this.smileys_box);
		}.bind(this));
		this.actions.smileys.div.adopt(this.smileys_box);
		
		
  	},
	close:function(){ this.smileys_box.remove();this.smileys_box=false; }
  }
});

$extend(Wyzzie.prototype.actions,
	{'smileys':{'key':'s','onclick':function(){this.smileys.open.bind(this)()}}}
); 