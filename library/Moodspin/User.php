<?php

class Moodspin_User {

    private $properties = array();

    private $_screenName;
    private $_id;
    private $_userId;
    private $_imageUrl;
    private $_currentMood;

    private $_userSettings;
    private $_networkSettings;

    public function __construct($data = array()) {
        foreach($data as $key=>$value) {
            $this->{$key} = $value;
        }
    }

    public function __get($name) {
        $name[0] = strtolower($name[0]);
        if(property_exists($this, '_'.$name)) {
            return $this->{'_'.$name};
        } else {
            return  isset($this->properties[$name]) ? $this->properties[$name] : NULL;
        }
    }

    public function __set($name, $value) {
        $name[0] = strtolower($name[0]);

        if(property_exists($this, '_'.$name)) {
            $this->{'_'.$name} = $value;
        } else {
            $this->properties[$name] = $value;
        }
    }

    public function __call($name, $arguments) {
        if(strpos($name, 'set') === 0) {
            $this->{str_replace('set', '', $name)} = $arguments[0];
        } elseif(strpos($name, 'get') === 0) {
            return $this->{str_replace('get', '', $name)};
        } else {
            throw new Exception('Unknown method '.$name.' in class Moodspin_User. Arguments: '.var_export($arguments, true));
        }
    }

}
