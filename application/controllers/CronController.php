<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class CronController extends Zend_Controller_Action
{
    public function init() {
        $ip = $this->getRequest()->getClientIp(true);
        if ($ip !== '127.0.0.1' and $ip !== '::1') {
            throw new Syj_Exception_Forbidden();
        }
    }
    public function indexAction() {
        $mapper = new Syj_Model_PendingMapper();
        foreach ($mapper->fetchAll() as $pending) {
            $pending->notify();
        }
        $this->_helper->SyjApi->setCode(200);
    }
}
