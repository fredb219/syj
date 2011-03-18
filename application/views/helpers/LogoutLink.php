<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_View_Helper_LogoutLink extends Zend_View_Helper_Abstract
{
    public function logoutLink() {
        $encodeduri = $this->view->UriPath(true);
        $translatedString = $this->view->translate('logout');
        $href = $this->view->addParamToUrl('logout', 'redirect', $encodeduri, true);
        return $this->view->anchor($href, $translatedString, array('id' => 'logout', 'class' => 'menu-item'));
    }
}
