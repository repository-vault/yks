
var CheckBox = new Class({

  Occlude: 'CheckBox',

  initialize:function(el){
    if(this.occlude(el)) return;

    el.setStyle('display', 'none');

    var args = {'class':el.className+(el.checked?' selected':'')};
    var div = $n('div',args ).inject(el ,'before');

    el.addEvent('change', div.fireEvent.pass(['sync'], div) );

    div.addEvent('sync', function(status){

        if(el.checked) this.addClass('selected');
        else this.removeClass('selected');
    });

    div.addEvent('click', function(){
        el.checked = el.hasClass('radio') ? true : !el.checked;
        this.getParent('form').getElements('input[name="'+el.name+'"]').fireEvent('change');
    });

  }
});


