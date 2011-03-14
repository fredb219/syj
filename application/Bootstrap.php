<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap {
    protected static $libns = array('gisconverter', 'phptojs', 'pwdgen');

    public function _bootstrap($resource = null) {
        $autoloader = Zend_Loader_Autoloader::getInstance();
        foreach (self::$libns as $namespace) {
            $autoloader->pushAutoloader(array($this, 'syj_autoload'), array($namespace, 'Syj_'));
        }

        parent::_bootstrap($resource);
    }

    public function run()
    {
        $sessionConfig = new Zend_Config_Ini(APPLICATION_PATH . '/configs/session.ini', APPLICATION_ENV);
        Zend_Session::setOptions($sessionConfig->toArray());
        Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer')->initView(APPLICATION_PATH . '/views/', 'Syj_View');

        parent::run();
    }

    static public function syj_autoload($class) {
        foreach (self::$libns as $namespace) {
            if (strpos ($class, $namespace) === 0) {
                include_once ($namespace . ".php");
                return;
            }
        }

        if (strpos ($class, "Syj_") === 0) {
            $segments = explode ('_', $class);

            if (count($segments) < 3) {
                return;
            }

            $dirpath = implode('/', array_map('strtolower', array_slice($segments, 1, -1)));
            $filename = APPLICATION_PATH . '/' . ($dirpath ? $dirpath . '/' : '') . end($segments) . '.php';
            if (Zend_Loader::isReadable($filename)) {
                include_once $filename;
            }
        }
    }

}
