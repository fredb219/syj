<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

abstract class Syj_Model_Generic
{
    protected $_translator = null;

    public function __construct(array $options = null) {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    public function __set($name, $value) {
        $method = 'set' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Zend_Exception($name . ': Invalid property for ' . get_class($this));
        }
        $this->$method($value);
    }

    public function __get($name) {
        $method = 'get' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Zend_Exception($name . ': Invalid property for ' . get_class($this));
        }
        return $this->$method();
    }

    public function __isset($name) {
        $method = 'get' . $name;
        if (('mapper' == $name) || !method_exists($this, $method)) {
            throw new Zend_Exception($name . ': Invalid property for ' . get_class($this));
        }
        $result = $this->$method();
        return isset($result);
    }

    public function setOptions(array $options) {
        $methods = get_class_methods($this);
        foreach ($options as $key => $value) {
            $method = 'set' . ucfirst($key);
            if (in_array($method, $methods)) {
                $this->$method($value);
            }
        }
        return $this;
    }

    protected function getTranslator() {
        if ($this->_translator) {
            return $this->_translator;
        }
        if (Zend_Registry::isRegistered('Zend_Translate')) {
            $translator = Zend_Registry::get('Zend_Translate');
            $this->_translator = $translator->getAdapter();
        }
        return $this->_translator;
    }

    protected function translate($messageId, $locale = null) {
        $translator = $this->getTranslator();
        if ($translator) {
            return $translator->translate($messageId, $locale);
        }
        return $messageId;
    }

}
