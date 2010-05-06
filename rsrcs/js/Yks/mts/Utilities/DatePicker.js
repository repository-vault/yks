
var DatePicker = new Class({
  Occlude : 'DatePicker',
  Binds : ['create', 'destroy', 'close', 'outClick'],

  options:{
    months:['January', 'February','March','April','May','June','July','August','September','October','November','December'],
    days:['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],
    date_format:'dd/mm/yyyy',
    year_range:10,
    start_day:1
  },
  
  container:false,
  calendar:false,
  display_mode:'date', //datetime
  active:false,
  old_value:null,

  initialize: function(anchor){
    if(this.occlude(anchor)) return;

    this.anchor = anchor;
    if(this.anchor.hasClass('input_time')) {
        this.options.date_format = 'dd/mm/yyyy hh:ii';
        this.display_mode = 'datetime';
    }
    this.today = this.day(new Date());

    anchor.setProperties({'id':anchor.getProperty('name')});
    anchor.addEvent('click',this.create);
    anchor.addEvent('focus',this.create);
  },

  create: function(){
    if(this.calendar) return false;
    
    if(this.old_value!=this.anchor.value){
        this.old_value=this.anchor.value;    
        this.user_date=this.format_in();
        this.user_day=this.day(this.user_date);
    };

    $(this.anchor.getBox()).addEvent('unload', this.destroy);
    var tmp = this.anchor.getCoordinates();
    tmp = {'left':tmp.left+'px', 'top':tmp.top+'px'};
    this.container = $n('div',{'class':'dp_container', styles:tmp}).inject($('container'));

    this.calendar = $n('table',{'class':'dp_cal'}).inject(this.container);
    var head = $n('th', {'colspan':'7'}
        ).inject($n('tr'
            ).inject($n('thead'
                ).inject(this.calendar)));

    var current_m = this.user_date.getMonth(),
        current_y = this.user_date.getFullYear();

    $n('div', {'class':'dp_nav dp_left'}).inject(head).addEvent('click', function(){
        this.user_date.setMonth(current_m - 1);
        this.hop();
    }.bind(this));

    var months = $n('select').inject(head);
    for (var m = 0; m < 12; m++)
        $n('option',{'value':m,'selected':current_m==m}
                ).appendText(this.options.months[m]
                    ).inject(months);

    $n('div', {'class':'dp_nav dp_right'}).inject(head).addEvent('click', function(){
        this.user_date.setMonth(current_m + 1);
        this.hop();
    }.bind(this));

    $n('div', {'class':'dp_nav dp_left'}).inject(head).addEvent('click', function(){
        this.user_date.setFullYear(current_y - 1);
        this.hop();
    }.bind(this));

    $n('span', {'class':'years_sp'}).inject(head).set('text', current_y);

    document.addEvent('mousedown', this.outClick);

    $n('div', {'class':'dp_nav dp_right'}).inject(head).addEvent('click', function(){
        this.user_date.setFullYear(current_y + 1);
        this.hop();
    }.bind(this));

    var month_days, month_start, month_first;
    (tmp = new Date(this.user_date)).setDate(31);
    month_days = {31:31,1:30,2:29,3:28}[tmp.getDate()];
    tmp = new Date(this.user_date); tmp.setDate(1);
    month_start = (tmp.getDay()-this.options.start_day+7)%7;
    month_first = this.day(tmp)-1;

    var tbody = $n('tbody').inject(this.calendar);
    var line = $n('tr').inject(tbody);
    for(var d=0;d<7;d++) $n('th').appendText(this.options.days[d].substr(0,1)).inject(line);

    for(var d = -month_start; d<month_days; d++){
        if(!((d+month_start)%7))line=$n('tr').inject(tbody); tmp=d>=0?d+1:0;
        var _class=tmp?'day':'';
        if(tmp && month_first+tmp==this.today)_class+=' today';
        if(tmp && month_first+tmp==this.user_day)_class+=' current';
        var td = $n('td',{title:tmp,'class':_class}).inject(line);
        if(tmp) td.appendText(''+tmp);
    } for(var d=0;d<(7-(month_days+month_start)%7)%7;d++)line.adopt($n('td'));

    if(this.display_mode=="datetime") {
        var bottom = $n('td',{'colspan':'7'}).inject($n('tr').inject(this.calendar));
        var udate = this.user_date;

        $n('input',{'class':'dp_t',value:this.zeropad(udate.getHours())}
          ).inject(bottom).addEvent('change', function(){
            udate.setHours(this.value);
        });

        $n('span',{ text:':'}).inject(bottom);
        $n('input',{'class':'dp_t',value:this.zeropad(udate.getMinutes())}
          ).inject(bottom).addEvent('change', function(){
            udate.setMinutes(this.value);
        });
    }

    //show
    if(!this.active) this.open();

    months.addEvent('change',function(){
        this.user_date.setMonth(months.value);
        this.hop();
    }.bind(this));
    
/*
    var years = $n('select', {'class':'years'}).inject(head);
    var range = Math.floor(this.options.year_range/2)
    for (var y = current_y-range; y <= current_y+range; y++)
        $n('option',{'value':y,'selected':y==current_y}
                ).appendText(y).inject(years)

    years.addEvent('change', function(){
        this.user_date.setFullYear(years.value);
        this.hop();
    }.bind(this));
*/

    this.calendar.getElements('td.day').each(function(el){
        el.addEvent('click',function(){
            this.user_date.setDate(el.title);
            this.close(null, true);
        }.bind(this));
    },this);

  },


  day:function(tmp){ return tmp.getFullYear()*365+tmp.getMonth()*31+tmp.getDate();},
    
  format_in: function(){
    if(!this.anchor.value) return new Date();

    var vals=[], keys=['full'];
    var mask_keys = new RegExp("([a-z]+)",'g');
    while(out=mask_keys.exec(this.options.date_format)) keys.push(out[0]);
    var mask_vals=new RegExp(this.options.date_format.replace(mask_keys,"([0-9]+)"));
    vals = this.anchor.value.match(mask_vals);
    if(!vals) return new Date();
    var e = vals.associate(keys);
    this.display_mode = $chk(e.hh)?'datetime':'date';

    return new Date(e.yyyy, e.mm-1, e.dd, e.hh||0, e.ii||0);
  },

  format_out: function(){
    return this.options.date_format.areplace({
        dd:this.zeropad(this.user_date.getDate()),
        mm:this.zeropad(this.user_date.getMonth()+1),
        yyyy:this.user_date.getFullYear(),
        hh:this.zeropad(this.user_date.getHours()),
        ii:this.zeropad(this.user_date.getMinutes())
    });
  },

  zeropad:function(str){ return ('0'+str).slice(-2); },

  hop_fast:false,

  hop:function(){
    this.hop_fast = true;
    this.destroy();
    this.create();
    this.hop_fast = false;
  },

  destroy: function(){
    this.active = false;
    if (this.container) this.container.dispose(); 
    this.calendar = false;
    this.container = false;
  },

  open:function(){
    this.active = true;
    this.container.effect('height',{duration:(this.hop_fast?0:190)}
        ).start(0,this.calendar.offsetHeight);
  },

  outClick:function(e){
    var clickOutside = e && e.target != this.container
                 && !this.container.hasChild(e.target);
    if(!clickOutside) return
    this.close(null, false);
  },

  write:function(){
    this.anchor.value=this.format_out();
    this.anchor.fireEvent('change');
  },

  close:function(e, write){

    document.removeEvent('mousedown', this.outClick);

    if(this.anchor.value == this.old_value || write) 
        this.write();
  
    if(!this.container)
        return this.remove();
    this.container.effect('height',{duration:(this.hop_fast?0:190)}
        ).start(this.calendar.offsetHeight,0
            ).chain(this.destroy);
  }

});


