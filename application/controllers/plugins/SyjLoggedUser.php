<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Controllers_Plugins_SyjLoggedUser extends Zend_Controller_Plugin_Abstract
{
    public function postDispatch(Zend_Controller_Request_Abstract $request) {
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $view = $viewRenderer->view;
        $sessionHelper = Zend_Controller_Action_HelperBroker::getStaticHelper('SyjSession');
        $view->loggedUser = $sessionHelper->user();
    }
}
