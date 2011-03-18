<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

namespace phptojs;

class JsObject {
    protected $_name;
    protected $_data = array();
    public function __construct($name = null, $data = array()) {
        $this->_name = $name;
        if (is_array($data)) {
            $this->_data = $data;
        }
    }

    public function getName() {
        return $this->_name;
    }

    public function setName($name) {
        $this->_name = (string)$name;
        return $this;
    }

    public function __set($property, $value) {
        $this->_data[$property] = $value;
    }
    public function __get($property) {
        if (array_key_exists($property, $this->_data)) {
            return $this->_data[$property];
        }
        return null;
    }

    public function __unset($property) {
        unset($this->_data[$property]);
    }
    public function __isset($property) {
        return __isset($this->_data[$property]);
    }

    public function __toString() {
        if ($this->_name) {
            $prefix = "var " . $this->_name . " = ";
        } else {
            $prefix = "";
        }
        return $prefix . " " . json_encode($this->_data, JSON_FORCE_OBJECT) . ";" . PHP_EOL;
    }

}
