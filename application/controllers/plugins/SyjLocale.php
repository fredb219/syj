<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Controllers_Plugins_SyjLocale extends Zend_Controller_Plugin_Abstract
{

    public function preDispatch(Zend_Controller_Request_Abstract $request) {
        $lang = $request->getQuery('lang');

        if ($lang) {
            setcookie("syj_lang", $lang, 0, "", "", false, true);
        } else {
            $lang = $request->getCookie('syj_lang');
        }

        if (!$lang) {
            return;
        }

        $translator = Zend_Registry::get('Zend_Translate');
        $adapter = $translator->getAdapter();
        if (!$adapter->isAvailable($lang)) {
            return;
        }

        $adapter->setLocale($lang);
    }

}
