<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Controller_Action_Helper_SyjMedias extends Zend_Controller_Action_Helper_Abstract
{
    protected $_config;

    public function init() {
        $this->_config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/medias.ini', APPLICATION_ENV);
    }

    public function addScripts($action) {
        $view = $this->getActionController()->view;
        if (APPLICATION_ENV == "production") {
            $view->headScript()->appendFile('js/' . $action . '.js');
            return;
        }
        $scripts = explode(',', $this->_config->get('scripts')->get($action));
        foreach ($scripts as $name) {
            $view->headScript()->appendFile('js/' . trim($name) . '.js');
        }
    }
}
