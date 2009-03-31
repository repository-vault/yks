<?

class procedure extends procedure_base {
  static $ready = false;
  static function init(){
    myks_gen::$type_resolver->register("in", array(
      'boolean'=>'bool',
      '"trigger"'=>'trigger', //vraiment ridicule postgres :/
      'integer'=>'int',
      'character varying'=>'string',
      'timestamptz'=>'timestamptz',
      'timestamp without time zone'=>'timestamp', //information_schema.routines.data_type
      'bigint'=>'bigint',
      'record'=>'record',
    ));
    myks_gen::$type_resolver->register("out", array(
      'bool'=>'boolean',
      'trigger'=>'trigger',
      'string'=>'varchar',
      'int'=>'integer',
      'bigint'=>'bigint',
      'timestamptz'=>'timestamptz',
      'timestamp'=>'timestamp',
      'record'=>'record',
    ));
    self::$ready = true;
  }

  function __construct($proc_xml){
    if(!self::$ready) self::init();
    parent::__construct($proc_xml);
  }

}
