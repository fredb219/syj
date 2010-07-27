<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_View_Helper_Returnto extends Zend_View_Helper_HtmlElement
{

    protected function getHref() {
        $param = Zend_Controller_Front::getInstance()->getRequest()->getQuery('redirect');
        if (!isset($param)) {
            return false;
        }
        $href = $this->view->escape(implode('/', array_map('urlencode', explode('/', $param ))));
        return $href;
    }

    public function returnto() {
        $returnto = $this->getHref();
        $baseurl = $this->view->baseUrl();

        if ($returnto === false) {
            return $this->view->translate('You can now') . ' '
                   . $this->view->anchor($baseurl, $this->view->translate('go to main page'));
        } else if ($returnto == $baseurl) {
            return $this->view->translate('You can now') . ' '
            . $this->view->anchor($returnto, $this->view->translate('go back to %s', $returnto));
        } else {
            return $this->view->translate('You can now') . ' ' .
                    $this->view->anchor($returnto, $this->view->translate('go back to %s', $returnto)) .
                    ' ' . $this->view->translate('or you can') . ' ' .
                    $this->view->anchor($baseurl, $this->view->translate('go to main page'));
        }

    }
}
