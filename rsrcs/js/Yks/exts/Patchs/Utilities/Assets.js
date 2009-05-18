

// onload property
Asset.css = function(source, properties){
    var onload = properties.onload || $empty;
    delete properties.onload;

    var css = new Element('style', {
        'media': 'screen', 'type': 'text/css'
    }).inject(document.head);
    
    http_lnk('get',source,{},function(txt){
        if (this.styleSheet) this.styleSheet.cssText = txt;
        else this.appendChild(document.createTextNode(txt));
        onload();
    }.bind(css));
};


