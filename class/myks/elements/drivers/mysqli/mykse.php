<?


  /**	Myks_gen by 131 for Exyks distributed under GPL V2
	this class export the basic field SQL definition from a myks_xml structure
  */

class mykse extends mykse_base {

  static $cols=array('Field','Type','Extra','Null','Default','Extra');
  static $cols_linearize=array('Type','Extra','Null','Default','Extra');


  function default_value(){
    if(is_null($this->field_def['Default']) && !($this->field_def['Null']) ) {
        if($this->field_def['Extra']=='auto_increment') $val=null;
        elseif(isset($this->mykse_xml['default'])){
            $this->field_def['Default']=(string)$this->mykse_xml['default'];
        }
        elseif($this->base_type=="int") $val = 0;
        elseif($this->base_type=="string") $val = "''";
        elseif($this->base_type=="enum") $val = null;
        elseif($this->base_type=="text") $val = "''";
        else return; //no default value for type : '$this->base_type'
        if(!is_null($val))$this->field_def['Default'] = $val;
    }

    if($data['Default']==='')$data['Default']="''";
    if($this->field_def['Default']==="unix_timestamp()")
        $this->field_def['Default']=0;
   }

  function int_node(){
	$sizes=array(
		'mini'=>'tinyint(3)',
		'small'=>'smallint(5)',
		'int'=>'mediumint(8)',
		'big'=>'int(10)',
		'giga'=>'bigint(20)',
		'float'=>'float',
		'decimal'=>'float(10,5)',
	);$type=$sizes[(string)$this->mykse_xml['size']];
	$signed=(((string)$this->mykse_xml['signed'])=='signed')?'':' unsigned';
	if($this->birth){
           $this->field_def['Default'] = null;
           $this->field_def["Extra"]="auto_increment";
           $this->field_def["Null"]=false;
        }
	$this->field_def["Type"]=$type.$signed;
  }



  function enum_node(){
	$type=((string)$this->mykse_xml['set']=='set')?'set':'enum';
	$vals=array(); foreach($this->mykse_xml->val as $val)$vals[]=(string)$val;
	$this->field_def["Type"]="$type('".join("','",$vals)."')";
  }



  static function linearize($field_xml){
	$str="`{$field_xml['Field']}` ";
        if(is_null($field_xml['Default']) && $field_xml['Null'])
            $field_xml['Default']="NULL";
        $field_xml['Null'] = $field_xml['Null']?'NULL':'NOT NULL';
        if($field_xml['Default']=="unix_timestamp()" ) $field_xml['Default']=0;
        if(!is_null($field_xml['Default']))
            $field_xml['Default']="DEFAULT {$field_xml['Default']}";
	return $str.=join(' ',array_sort($field_xml,mykse::$cols_linearize));
  }

}
