<?



class Selectors {
  static $RegExps = array(
    'id'=> '/#([\w-]+)/',
    'tag'=> '/^(\w+|\*)/',
    'quick'=> '/^(\w+|\*)$/',
    'splitter'=> '/\s*([+>~\s])\s*(?=[a-zA-Z#.*:\[])/',
    'combined'=> '/\.([\w-]+)|\[(\w+)(?:([!*^$~|]?=)(["\']?)([^\4]*?)\4)?\]|:([\w-]+)(?:\(["\']?(.*?)?["\']?\)|$)/',
  );

  static $splitters = array(
    ' '=>'descendant',
    '>'=>'child',
    '+'=>'direct_sibling',
    '~'=>'sibling',
  );
}

include "Selectors_Utils.php";
include "Selectors_Getters.php";
include "Selectors_Filters.php";
include "Selectors_Pseudo.php";
include "Forms.php";


Element::__register("Forms");

include "functions.php";


