
Jsx.Mailto = new Class({
  Extends: Jsx,
  Occlude : 'Jsx.Mailto',

  initialize:function(el){
    if(this.occlude(el)) return;

    var str0 = "", str = el.get('secured');
    str0 = Doms.wake("Crypt").Base64.decode(str);

    el.href="mailto:"+str0;
  }
});
