if(!Cookie.prototype.options.document.cookie) {
    Cookie.prepare = function(){
      new IFrame({src: '/?/Yks/blank', styles:{display:'none'}, onload:function(){
        Cookie.prototype.options.document = this.document;
        window.fireEvent('cookieReady');
      }}).inject($E('body'));
    }
    if(!$E("body")) window.addEvent('domready', Cookie.prepare); else Cookie.prepare();
} else window.fireEvent('cookieReady');

