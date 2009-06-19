<?

class procedure extends procedure_base {
  function __construct($proc_xml){
    static $ready = false;
    if(!$ready) {
        include "register_exts.php";
        $ready = true;
    }

    parent::__construct($proc_xml);
  }

}
