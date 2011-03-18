<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_View_Helper_LocaleShort extends Zend_View_Helper_Abstract
{
    public function localeShort() {
        $translator = $this->view->getHelper('translate')->getTranslator();
        $locale = $translator->getLocale();
        if (!($translator->isAvailable($locale))) {
            $defaults = Zend_Locale::getDefault();
            arsort($defaults);
            $locale = key($defaults);
        }
        $locale = new Zend_Locale($locale);
        return $locale->getLanguage();
    }
}
