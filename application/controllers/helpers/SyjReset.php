<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Controller_Action_Helper_SyjReset extends Zend_Controller_Action_Helper_Abstract
{
    public function resetPlaceHolders() {
        $controller = $this->getActionController();
        $controller->view->jslocales = null;
        $controller->view->headScript()->exchangeArray(array());
        $controller->view->headLink()->exchangeArray(array());
        $controller->view->headTitle()->exchangeArray(array());
        $controller->view->headStyle()->exchangeArray(array());
    }
}
