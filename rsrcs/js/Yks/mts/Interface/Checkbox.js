
var CheckBox = new Class({
  initialize:function(el){ if(CheckBox.extended(el)) return;
    var tmp = $n('input',
        {type:'hidden',name:el.name,disabled:!(el.checked),value:'On'}
    ).replaces(el) , 
        div = $n('div',{'class':'checkbox '+el.className+(el.checked?' selected':'')}
    ).inject(tmp ,'before');

    div.addEvent('click',function(){
        this.toggleClass('selected');
        tmp.disabled = !(this.hasClass('selected'));
        this.fireEvent('change');
     });
  }
});


