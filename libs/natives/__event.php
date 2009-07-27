<?

abstract class __event {

  private $__events;
  function fireEvent($event, $args){
    $chain = (array) $this->__events[$event];

    foreach($chain as $callback)
        call_user_func_array($callback, $args);
  }

  function addEvent($event, $callback){
    $this->__events[$event][] = $callback;
  }

}
