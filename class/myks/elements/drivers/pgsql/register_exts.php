<?php

 //manual declaration of types, dont bother cleaning this

    myks_gen::$type_resolver->register("in", array(
      'boolean'=>'bool',
      '"trigger"'=>'trigger', //vraiment ridicule postgres :/
      'integer'=>'int',
      'character varying'=>'string',
      'timestamp with time zone'=>'timestamptz',
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


return true;