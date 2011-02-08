<?php

class Crypt_RSA_Key 
{
    /**
     * Reference to math wrapper object, which is used to
     * manipulate large integers in RSA algorithm.
     *
     * @var object of Crypt_RSA_Math_* class
     * @access private
     */
    var $_math_obj;

    /**
     * shared modulus
     *
     * @var string
     * @access private
     */
    var $_modulus;

    /**
     * exponent
     *
     * @var string
     * @access private
     */
    var $_exp;

    /**
     * key type (private or public)
     *
     * @var string
     * @access private
     */
    var $_key_type;

    /**
     * key length in bits
     *
     * @var int
     * @access private
     */
    var $_key_len;

    /**
     * Crypt_RSA_Key constructor.
     *
     * You should pass in the name of math wrapper, which will be used to
     *        perform different operations with big integers.
     *        See contents of Crypt/RSA/Math folder for examples of wrappers.
     *        Read docs/Crypt_RSA/docs/math_wrappers.txt for details.
     *
     * @param string $modulus       key modulus
     * @param string $exp           key exponent
     * @param string $key_type      type of the key (public or private)
     * @param string $wrapper_name  wrapper to use
     * @param string $error_handler name of error handler function
     *
     * @access public
     */
    function Crypt_RSA_Key($modulus, $exp, $key_type, $wrapper_name = 'default', $error_handler = '')
    {
        // set error handler
        //$this->setErrorHandler($error_handler);
        // try to load math wrapper $wrapper_name
        $obj = &Crypt_RSA_MathLoader::loadWrapper($wrapper_name);

        if (false) {
            // error during loading of math wrapper
            $this->pushError($obj); // push error object into error list
            return;
        }
        $this->_math_obj = &$obj;

        $this->_modulus = $modulus;
        $this->_exp = $exp;

        if (!in_array($key_type, array('private', 'public'))) {
            $this->pushError('invalid key type. It must be private or public', CRYPT_RSA_ERROR_WRONG_KEY_TYPE);
            return;
        }
        $this->_key_type = $key_type;

        /* check length of modulus & exponent ( abs(modulus) > abs(exp) ) */
        $mod_num = $this->_math_obj->bin2int($this->_modulus);
        $exp_num = $this->_math_obj->bin2int($this->_exp);

        if ($this->_math_obj->cmpAbs($mod_num, $exp_num) <= 0) {
            $this->pushError('modulus must be greater than exponent', CRYPT_RSA_ERROR_EXP_GE_MOD);
            return;
        }
        // determine key length
        $this->_key_len = $this->_math_obj->bitLen($mod_num);
    }

    /**
     * Crypt_RSA_Key factory.
     *
     * @param string $modulus       key modulus
     * @param string $exp           key exponent
     * @param string $key_type      type of the key (public or private)
     * @param string $wrapper_name  wrapper to use
     * @param string $error_handler name of error handler function
     *
     * @return object   new Crypt_RSA_Key object on success or PEAR_Error object on failure
     * @access public
     */
    function factory($modulus, $exp, $key_type, $wrapper_name = 'default', $error_handler = '')
    {
        $obj = new Crypt_RSA_Key($modulus, $exp, $key_type, $wrapper_name, $error_handler);
        if ($obj->isError()) {
            // error during creating a new object. Retrurn PEAR_Error object
            return $obj->getLastError();
        }
        // object created successfully. Return it
        return $obj;
    }

    /**
     * Calculates bit length of the key
     *
     * @return int    bit length of key
     * @access public
     */
    function getKeyLength()
    {
        return $this->_key_len;
    }

    /**
     * Returns modulus part of the key as binary string,
     * which can be used to construct new Crypt_RSA_Key object.
     *
     * @return string  modulus as binary string
     * @access public
     */
    function getModulus()
    {
        return $this->_modulus;
    }

    /**
     * Returns exponent part of the key as binary string,
     * which can be used to construct new Crypt_RSA_Key object.
     *
     * @return string  exponent as binary string
     * @access public
     */
    function getExponent()
    {
        return $this->_exp;
    }

    /**
     * Returns key type (public, private)
     *
     * @return string  key type (public, private)
     * @access public
     */
    function getKeyType()
    {
        return $this->_key_type;
    }

    /**
     * Returns string representation of key
     *
     * @return string  key, serialized to string
     * @access public
     */
    function toString()
    {
        return base64_encode(
            serialize(
                array($this->_modulus, $this->_exp, $this->_key_type)
            )
        );
    }

    /**
     * Returns Crypt_RSA_Key object, unserialized from
     * string representation of key.
     *
     * optional parameter $wrapper_name - is the name of math wrapper,
     * which will be used during unserialization of this object.
     *
     * This function can be called statically:
     *     $key = Crypt_RSA_Key::fromString($key_in_string, 'BigInt');
     *
     * @param string $key_str      RSA key, serialized into string
     * @param string $wrapper_name optional math wrapper name
     *
     * @return object        key as Crypt_RSA_Key object
     * @access public
     * @static
     */
    function fromString($key_str, $wrapper_name = 'default')
    {
        list($modulus, $exponent, $key_type) = unserialize(base64_decode($key_str));
        $obj = new Crypt_RSA_Key($modulus, $exponent, $key_type, $wrapper_name);
        return $obj;
    }

    /**
     * Validates key
     * This function can be called statically:
     *    $is_valid = Crypt_RSA_Key::isValid($key)
     *
     * Returns true, if $key is valid Crypt_RSA key, else returns false
     *
     * @param object $key Crypt_RSA_Key object for validating
     *
     * @return bool        true if $key is valid, else false
     * @access public
     */
    function isValid($key)
    {
        return (is_object($key) && strtolower(get_class($key)) === strtolower(__CLASS__));
    }
}

