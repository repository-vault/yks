
function getmicrotime(){ var tmp=new Date(); return tmp.getTime(); }

function print_r(obj,depth){
	var accepted=(/^(event|object|textnode|element|array)$/),type=$type(obj);
	if(!accepted.test(type)) return type=="function"?"function":obj;

	var str='Object {', v='', depth=depth||0;
	if(depth>1)return "to deep";
	if($type(obj)=="function") return "function";
	if($type(obj)=="element") return "<"+(obj.tagName)+(obj.id?"#"+obj.id:'')+"/>";

	var base='';for(var a=0;a<depth;a++)base+='	';
	for(var k in obj){v=obj[k];
		str+="\n"+base+'\t['+k+'] => '+print_r(v,depth+1)+',';
	};str+="\n"+base+"}";return str;
} function trace(a){alert(print_r(a))};



