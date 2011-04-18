<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Controllers_Plugins_SyjContentType extends Zend_Controller_Plugin_Abstract
{
    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        // set default content-type
        $response = Zend_Controller_Front::getInstance()->getResponse();
        $response->setHeader('Content-Type', 'text/html; charset=utf-8', true);
    }
}
