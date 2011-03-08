var BBcoder = new Class({

  Declare : 'BBcoder',
  focus:function(){this.area.focus(); },

  initialize:function(area, options){
    if(area.bbcoder) return false;
    this.options = options || {};

    this.area = $(area);
    this.area.bbcoder = this;

    var box_size = this.area.getSize();
    var container_size = {'width':box_size.x,'height':box_size.y-30};

    this.container = new Element('div', {'class':'rte_container', styles:container_size}
        ).inject(this.area,'before');
    this.area.inject(this.container);

    if(this.options.toolbar)
        this.toolbar = this.options.toolbar;
    else this.toolbar = $n('div',{'class':'rte_toolbar topbar'}).inject(this.container,'top');


    $H(this.actions).each(function(action, key){

        var div = new Element('div', {'class':key+' rte_button', 'unselectable':'on'});
        div.inject(this.toolbar);
        div.addEvent('mousedown', this.store.bind(this));
        div.addEvent('mouseup', this.addtag.pass([key, action], this));
   }.bind(this));


  },

  store:function(){
    this.pos = this.area.getSelectedRange();
    this.txt = this.area.getSelectedText();
  },

  addtag:function(type, action){
    var pos = this.pos, selectedTxt = this.txt, txt = this.area.value ;

    if(type=='hyperlink') {
        if(selectedTxt.substring(0,7)=="http://")
            action = ["[url="+selectedTxt+"]", action[1], action[2]];
    }
    this.area.value = txt.substring(0, pos.start) + action[0] + txt.substring(pos.start, pos.end) + action[1] + txt.substring(pos.end);
    if(selectedTxt) 
        this.area.selectRange(pos.start + (action[3]||0), pos.end + action[1].length + action[0].length + (action[4]||0));
    else this.area.setCaretPosition(pos.start + action[2].length - action[1].length + (action[5] ||0));
  },


  actions:{
    'picture'      :['[img]', '[/img]', '[img][/img]'],
    'float_left'   :['[float=left]', '[/float]', '[float=left][/float]'],
    'float_right'  :['[float=right]', '[/float]', '[float=right][/float]'],
    'hyperlink'    :['[url=http://]', '[/url]', '[url][/url]', 0, 0, 7],
    'underline'    :['[u]', '[/u]', '[u][/u]'],
    'bold'         :['[b]', '[/b]', '[b][/b]'],
    'italic'       :['[i]', '[/i]', '[i][/i]'],
    'strike'       :['[strike]', '[/strike]', '[strike][/strike]'],
    'left'         :['[left]', '[/left]', '[left][/left]'],
    'right'        :['[right]', '[/right]', '[right][/right]'],
    'center'       :['[center]', '[/center]', '[center][/center]'],
    'justified'    :['[justify]', '[/justify]', '[justify][/justify]'],
    'color'        :['[color=%s]', '[/color]', '[color=#][/color]'],
    'hr'           :['', '[hr/]', '[hr/]', 0, 0, 5],
    'small'        :['[size=+1]', '[/size]', '[size=][/size]'],
    'big'          :['[size=-1]', '[/size]', '[size=][/size]']
  }


});