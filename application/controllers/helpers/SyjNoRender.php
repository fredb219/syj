<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Controller_Action_Helper_SyjNoRender extends Zend_Controller_Action_Helper_Abstract
{

    public function disableRender() {
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $viewRenderer->setNoRender();
        $layout = Zend_Layout::getMvcInstance();
        if (null !== $layout) {
            $layout->disableLayout();
        }
    }

}
