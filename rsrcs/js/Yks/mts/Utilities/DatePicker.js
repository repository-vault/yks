var DatePicker = new Class({
  Declare : ['DatePicker'],

  options:{
    months:['January', 'February','March','April','May','June','July','August','September','October','November','December'],
    days:['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'],
    date_format:'dd/mm/yyyy',
    year_range:10,
    start_day:1
  },
  
  container:false,
  calendar:false,
  interval:null,
  active:false,
  old_value:null,

  initialize: function(anchor){ if(DatePicker.extended(anchor)) return;
    this.anchor = anchor;
    this.today = this.day(new Date());
    anchor.setProperties({'id':anchor.getProperty('name')});
    anchor.addEvent('click',this.create.bind(this));
    anchor.addEvent('focus',this.create.bind(this));
  },

  create: function(){
    if(this.calendar) return false;
    
    if(this.old_value!=this.anchor.value){
        this.old_value=this.anchor.value;    
        this.user_date=this.format_in();
        this.user_day=this.day(this.user_date);
    };

    var tmp = this.anchor.getPosition(); tmp = {left:tmp.x+'px',top:tmp.y+'px'};

    this.container = $n('div',{'class':'dp_container',styles:tmp}
        ).inject(this.anchor,'before');
    this.calendar = $n('table',{'class':'dp_cal'}).inject(this.container);
    var head = $n('th', {'colspan':'7'}
        ).inject($n('tr'
            ).inject($n('thead'
                ).inject(this.calendar)));
    $n('div',{'class':'close'}).addEvent('click',this.close.bind(this)).inject(head);


    var months = $n('select').inject(head),
        current = this.user_date.getMonth();
    for (var m = 0; m < 12; m++)
        $n('option',{'value':m,'selected':current==m}
                ).appendText(this.options.months[m]
                    ).inject(months);

    var years = $n('select', {'class':'years'}).inject(head),
        current = this.user_date.getFullYear(),
        range = Math.floor(this.options.year_range/2);

    for (var y = current-range; y <= current+range; y++)
        $n('option',{'value':y,'selected':y==current}
                ).appendText(y).inject(years)

    var month_days, month_start, month_first;
    tmp = new Date(this.user_date);tmp.setDate(31);
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

    //show
    if(!this.active) this.open();

    //events
    tmp = function(){$clear(this.interval)}.bind(this);
    this.container.addEvent('mouseover',tmp);
    this.anchor.addEvent('mouseover',tmp);
    
    var close = function(){
        this.interval = setInterval(function(){
            if (!this.active)this.close();
        }.bind(this), 500);
    }.bind(this);

    this.container.addEvent('mouseout',close);
    this.anchor.addEvent('mouseout',close);

    months.addEvent('focus',this.touch.bind(this));
    months.addEvent('change',function(){
        this.user_date.setMonth(months.value);
        this.remove();
        this.create();
    }.bind(this));
    
    years.addEvent('focus',this.touch.bind(this));
    years.addEvent('change', function(){
        this.user_date.setFullYear(years.value);
        this.remove();
        this.create();
    }.bind(this));

    this.calendar.getElements('td.day').each(function(el){
        el.addEvent('click',function(){
            this.user_date.setDate(el.title);
            this.anchor.value=this.format_out();
            this.close();
        }.bind(this));
    },this);

  },

  touch:function(){this.active=true; },
  day:function(tmp){ return tmp.getFullYear()*365+tmp.getMonth()*31+tmp.getDate();},
    
  format_in: function(){
    if(!this.anchor.value) return new Date();

    var vals=[], keys=['full'];
    var mask_keys = new RegExp("([a-z]+)",'g');
    while(out=mask_keys.exec(this.options.date_format)) keys.push(out[0]);
    var mask_vals=new RegExp(this.options.date_format.replace(mask_keys,"([0-9]+)"));
    vals=this.anchor.value.match(mask_vals);if(!vals)return new Date(); var e=vals.associate(keys);
       
    if(!(e['mm'].toInt().between(1,12)) || !(e['dd'].toInt().between(1,31)) || !e['yyyy'] ) return new Date();
    return new Date(e['mm']+'/'+e['dd']+'/'+e['yyyy']);
  },

  format_out: function(){
    return this.options.date_format.areplace({
        dd:('0'+this.user_date.getDate()).slice(-2),
        mm:('0'+(this.user_date.getMonth()+1)).slice(-2),
        yyyy:this.user_date.getFullYear()
    });
  },

  remove: function(){
    $clear(this.interval);
    this.active = false;
    if (this.container) this.container.dispose(); 
    this.calendar = false;
    this.container = false;
  },

  open:function(){
    this.active = true;
    this.container.effect('height',{duration:190}).start(0,this.calendar.offsetHeight);
  },

  close:function(){
    if(!this.container) return this.remove();
    this.container.effect('height',{duration:190}
        ).start(this.calendar.offsetHeight,0
            ).chain(this.remove.bind(this));
  }

});

