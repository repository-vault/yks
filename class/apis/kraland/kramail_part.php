<?

class kramail_part {
  private $contents;
  private $mail;
  function __construct($kramail, $part_infos){
    $this->mail = $kramail;
    $this->contents = $part_infos['part_contents'];
  }

  function encode(){
    $this->apply_context();
    $quote = "[\"']";
    $table_conversion = array(
        "#<b>(.*?)</b>#" => "[b]$1[/b]",
        "#<(center|i|u)>(.*?)</\\1>#" => "[$1]$2[/$1]",
        "#<br/>#"        => "\n",
        "#<a[^>]+href=({$quote})(.*?)\\1[^>]*>(.*?)</a>#" => "[url=$2]$3[/url]",
        "#<img[^>]+src=({$quote})(.*?)\\1[^>]*>#" => "[img]$2[/img]",
    );
    $str= preg_areplace($table_conversion, $this->contents);
    $str= preg_replace("#\{([a-z0-9_-]+)\}#i", "&$1;", $str);
    $str= jsx::translate($str);
    return $str;
  }

  function apply_context(){
    $context = (array) $this->mail->vars_list; extract($context);
    $this->contents = preg_replace(VAR_MASK,VAR_REPL,$this->contents);
  }

}

