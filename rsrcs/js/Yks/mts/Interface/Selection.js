Selection = {};

Selection.makeSelectable = function(container, options){
  options = options || {allowDrag:false};
  container.getChildren().addClass('selectable');
  container.dragmode = false;
  container.lastSelected = -1;

  if(options.allowDrag) {
    container.addEvent('mouseleave', function(event){
        container.dragmode = false;
    });

    container.addEvent('mousedown', function(event){
        container.dragmode = true;
        if(!event.control ) clean(resolve(event.target));
    });

    container.addEvent('mouseup', function(event){
        container.dragmode = false;
    });


    container.addEvent('mousemove', function(event){
       if(!container.dragmode) return;
        var el = resolve(event.target);
        if(!el) return;
        toggle(el, true);        

    });
  }

  container.addEvent('click', function(event){
      var el = resolve(event.target);
      if(!el) return;

      if(!event.control && !event.shift ){
        clean(el);
        toggle(el, true);
      }  else {
        if(event.shift && container.lastSelected != -1 ) {
            var i = el.getAllPrevious('.selectable').length;
            var start = Math.min(i, container.lastSelected), end = Math.max(i, container.lastSelected);
            for(var list = container.getChildren(), i = start; i <= end; i++)
                toggle(list[i], true);
        } else toggle(el);
      }

      container.lastSelected = el.selected ? el.getAllPrevious('.selectable').length : -1;
      container.fireEvent('change');
  });

  function toggle_off(el){
    toggle(el, false);
  }

  function clean(exclude){
      if(exclude) exclude.addClass('now');
      container.getElements('.selected:not(.now)').each(toggle_off);
      if(exclude) exclude.removeClass('now');
  }

  function resolve(el){
    if(!el.hasClass('selectable'))
        el = el.getParent('.selectable');
    return el;
  }

  function toggle(el, state){
    el.selected = (state != undefined ? state : !el.selected);

    if(el.selected) {
        if(!el.hasClass('selected')) el.addClass('selected');
    } else el.removeClass('selected');
  }
}