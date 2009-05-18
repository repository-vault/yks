
Elements.implement({
  reorder:function(){
    this.sort(function(a,b){
        return (a.innerHTML).localeCompare((b.innerHTML));
    });
    this.each(function(a){a.inject(a.getParent());});
  }
})

Element.activate = function(el,css_class){
    css_class = css_class || 'on';
    el.getParent().getElements('.'+css_class).removeClass(css_class);
    el.addClass(css_class);
}

