<?php

namespace yks;

/**
 * Proxy to get_class_vars, this is needed in Struct to obtain a list of
 * _public_ properties. get_class_vars is scope-aware so calling it inside
 * a class would also list protected and private properties.
 *
 * @param string $class
 * return mixed[]
 */
function get_class_public_vars($class)
{
    return get_class_vars($class);
}

/** Ensure that a class can't have 'floating properties'. Any attemp to get or
 * set a property that was not defined in the class declaration will throw an
 * exception.
 *
 * This is to be used instead of 'anonymous' arrays, like you would use structs
 * in C.
 */
trait Struct
{
    /**
     * @param mixed[] $props properties to set, name => value.
     */
    public function __construct($props = [])
    {
        $publicProps = array_keys(get_class_public_vars(__CLASS__));

        foreach ($props as $k => $v) {
            if(in_array($k, $publicProps, true))
                $this->$k = $v;
            else
                $this->__set($k, $v);
        }
    }

    /// Unreachable if you do your job well.
    public function __set($name, $value)
    {
        throw new \InvalidArgumentException("Attempted to set unkown property `$name`.");
    }

    /// Unreachable if you do your job well.
    public function __get($name)
    {
        throw new \InvalidArgumentException("Attempted to get unkown property `$name`.");
    }

    /// Unreachable if you do your job well.
    public function __unset($name)
    {
        throw new \InvalidArgumentException("Attempted to unset unkown property `$name`.");
    }
}
