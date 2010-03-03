<?

$sql_drivers = array(
    'mysqli'=>'MySQLi',
    'pgsql'=>'PostgreSQL',
);


class  {
  function retrieve(){
    //criteria
    $lines = array();
    foreach($config->sql->links->children() as $key=>$link_infos)
        $lines[$key]= array_merge(
            compact('key'),
            attribute_to_assoc($link_infos)
        );
    return $lines;
  }
  function fields(){
    return array(
            'key'=>'string',
            'host'=>'string',
            'user'=>'string',
            'pass'=>'string',
            'db'=>'string'
    );
  }

  function update($key, $data=array()){
    $base = $config->sql->links->$key;
    foreach($data as $key=>$value) $base[$key] = $value;
  }
  function delete($key){
    $config->sql->links->$key = null;
  }
  function insert($data, $key=false){
    $this->delete($key);
    $this->update($key, $data);
  }

  private function save(){
    file_put_contents(FILE_CONFIG, $config->asXML());
  }
}