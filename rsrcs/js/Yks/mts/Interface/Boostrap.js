window.addEvent('domready', function(){
    //minimal boostrap implementation

   //event delegation for .dropdown-toggle release
  $E('body').addEvent('click', function(e){
    var target = e.target.getParent('.dropdown-toggle')
        || e.target.match('.dropdown-toggle') && e.target;

    if(target) target.getParent('.dropdown').toggleClass('open');
    $$('.dropdown-toggle').exclude(target).getParent('.dropdown').removeClass('open');
  });

  $E('body').addEvent('click', function(e){
    //event delegation for .navbar-toggle
    var el = e.target.getParent('.navbar-toggle')
        || e.target.match('.navbar-toggle') && e.target;
    if(el) {
      var target = el.getParent('.navbar').getElement(el.get('data-target'));
      if(target.hasClass('in')) target.setStyle('height','1px').removeClass('in');
      else target.setStyle('height','auto').addClass('in');
    }
  });

});