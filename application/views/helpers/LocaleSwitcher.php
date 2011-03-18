<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_View_Helper_LocaleSwitcher extends Zend_View_Helper_Abstract
{
    public function localeSwitcher() {
        $translator = $this->view->getHelper('translate')->getTranslator();
        $availables = $translator->getList();
        $current = $translator->getLocale();

        $requestUri = $this->view->UriPath();

        $links = array();
        foreach ($availables as $lang) {

            $langname = $translator->translate('english', $lang);
            if ($lang == $current) {
                array_push ($links, "<a class=\"other-language-anchor\">$langname</a>");
            } else {
                $href = $this->view->addParamToUrl($requestUri, 'lang', $lang, true);
                array_push ($links, $this->view->anchor($href, $langname,
                                          array('class' => 'other-language-anchor',
                                                'title' => $langname, 'lang' => $lang, 'hreflang' => $lang)));
            }
        }
        return "<div id=\"other-language\">" . join("", $links) . "</div>\n";
    }
}
