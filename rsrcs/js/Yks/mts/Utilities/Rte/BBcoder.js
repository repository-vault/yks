var BBcoder = new Class({

  Binds:['focus', 'selectedText', 'replaceSelection'],

  Declare : 'BBcoder',
  focus:function(){this.area.focus(); },
  selectedText:function(){ return this.area.value.substring(this.pos.start, this.pos.end); },

  initialize:function(area, options){
    if(area.bbcoder) return false;
    this.options = options || {};
    var actions = this.options.actions || BBcoder.actions;
    var controls = this.options.controls || [];

    this.area = $(area);
    this.area.bbcoder = this;

    var box_size = this.area.getSize();
    this.container = $n('div', {'class':'rte_bbcontainer'}).inject(this.area, 'before');
    this.area.inject($n('div',{'class':'rte_bbtxtcontainer'}).inject(this.container));

    if(this.options.toolbar)
        this.toolbar = this.options.toolbar;
    else this.toolbar = $n('div',{'class':'rte_toolbar topbar'}).inject(this.container,'top');

    $H(actions).each(function(callback, key){
        var div = new Element('div', {'class':key+' rte_button', 'unselectable':'on'});
        div.inject(this.toolbar);
        div.addEvent('mousedown', this.store.bind(this));
        div.addEvent('mouseup', callback.bind(this));
   }.bind(this));

    $A(controls).each(function(control){
        control[0].each(function(el){
            el.addEvent('mousedown', this.store.bind(this));
            el.addEvent('mouseup', control[1].curry(el).bind(this));
        }.bind(this));
    }.bind(this));
  },


  replaceSelection:function(str){
    var pos = this.pos, txt = this.area.value;
    this.area.value = txt.substring(0, pos.start) + str + txt.substring(pos.end);
    this.area.setCaretPosition(pos.start + str.length );
  },

  store:function(){
    this.pos = this.area.getSelectedRange();
  }


});




BBcoder.extend({
 addtag:function(type, start, end, mid){
    var pos = this.pos, txt = this.area.value;
    if(pos.start != pos.end) {
        this.area.value = txt.substring(0, pos.start) + start + txt.substring(pos.start, pos.end) + end + txt.substring(pos.end);
        this.area.selectRange(pos.start, pos.end + end.length + start.length);
    }  else {
        this.area.value = txt.substring(0, pos.start) + mid + txt.substring(pos.end)
        this.area.setCaretPosition(pos.start + mid.length - end.length );
    }
  }
});

BBcoder.extend({
  actions:{
    'picture'      : BBcoder.addtag.curry('picture', '[img]', '[/img]', '[img][/img]'),
    'float_left'   : BBcoder.addtag.curry('float_left', '[float=left]', '[/float]', '[float=left][/float]'),
    'float_right'  : BBcoder.addtag.curry('float_right', '[float=right]', '[/float]', '[float=right][/float]'),
    'hyperlink'    : function(){
        //yeah
        if(this.selectedText().substring(0,7)=="http://")
            BBcoder.addtag.call(this, 'hyperlink', '[url='+this.selectedText()+']', '[/url]', '[url][/url]');
        else BBcoder.addtag.call(this, 'hyperlink', '[url=http://]', '[/url]', '[url][/url]')
    },
    'underline'    : BBcoder.addtag.curry('underline', '[u]', '[/u]', '[u][/u]'),
    'bold'         : BBcoder.addtag.curry('bold', '[b]', '[/b]', '[b][/b]'),
    'italic'       : BBcoder.addtag.curry('italic', '[i]', '[/i]', '[i][/i]'),
    'strike'       : BBcoder.addtag.curry('strike', '[strike]', '[/strike]', '[strike][/strike]'),
    'left'         : BBcoder.addtag.curry('left', '[left]', '[/left]', '[left][/left]'),
    'right'        : BBcoder.addtag.curry('right', '[right]', '[/right]', '[right][/right]'),
    'center'       : BBcoder.addtag.curry('center', '[center]', '[/center]', '[center][/center]'),
    'justified'    : BBcoder.addtag.curry('justified', '[justify]', '[/justify]', '[justify][/justify]'),
    'color'        : BBcoder.addtag.curry('color', '[color=%s]', '[/color]', '[color=#][/color]'),
    'hr'           : BBcoder.addtag.curry('hr', '', '[hr/]', '[hr/]'),
    'small'        : BBcoder.addtag.curry('small', '[size=--]', '[/size]', '[size=][/size]'),
    'big'          : BBcoder.addtag.curry('big', '[size=++]', '[/size]', '[size=][/size]'),
    'preview'      : function(){ Jsx.open('/?/Yks/Wysiwyg/Preview', 'preview_box', this.area); } ,
  }
});

