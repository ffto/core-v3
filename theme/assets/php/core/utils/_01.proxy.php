<?php 
/**
 * @package Utils\Proxy
 */


/**
 * Class for wrapping an object and allow dynamic properties, since they are deprecated in the latest PHP version.
 */
#[\AllowDynamicProperties]
class FFTO_Proxy {
    public $object;

    public function __construct($object) {
        $this->object = $object;
    }

    public function __get ($name){
        if (property_exists($this->object, $name))  return $this->object->$name;
        else if (property_exists($this, $name))     return $this->$name;
        else                                        return null;
    }

    public function __set ($name, $value){
        if (property_exists($this->object, $name))  $this->object->$name = $value;
        else                                        $this->$name = $value;
    }

    public function __call ($name, $arguments){
        if (method_exists($this->object, $name))  return call_user_func_array([$this->object, $name], $arguments);
        else                                      throw new Exception("Method $name not found");
    }

    public function __isset ($name){
        if (isset($this->object->$name))  return true;
        else if (isset($this->$name))     return true;
        else                              return false;
    }

    public function __unset ($name){
        if (isset($this->$name)){
            unset($this->$name);
        }
    }

    public function __clone (){
        $this->object = clone($this->object);
    }

    // TODO
    // __callStatic, __sleep(), __wakeup(), __serialize(), __unserialize(), __toString(), __invoke(), __set_state(), and __debugInfo().

    // public function __toString() {
    //     return (string)$this->object;
    // }

}

/**
 * If the value is an object, it will be converted to a proxy.
 *
 * @param mixed $v 
 * @return mixed
 */
function ffto_to_proxy ($v){
    if (!$v || !is_object($v)) return $v;
    if (is_a($v, 'FFTO_Proxy')){
        return $v;
    }else{
        return new FFTO_Proxy($v);
    }
}

/**
 * If it's a FFTO_Proxy, the object will be returned.
 *
 * @param mixed $v 
 * @return mixed
 */
function ffto_to_object ($v){
    if (!$v || !is_object($v)) return $v;
    if (is_a($v, 'FFTO_Proxy')){
        return $v->object;
    }else{
        return $v;
    }
}

/**
 * Check if the "object" is a specific class.
 *
 * @param mixed $v 
 * @param string $class 
 * @return boolean
 */
function ffto_is_class ($v, $class){
    if (is_a($v, 'FFTO_Proxy')){
        $v = $v->object;
    }
    return is_object($v) ? is_a($v, $class) : false;
}
