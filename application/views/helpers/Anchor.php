<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_View_Helper_Anchor extends Zend_View_Helper_HtmlElement
{
    public function Anchor($href, $text = null, array $attribs=array()) {
        $lang = Zend_Controller_Front::getInstance()->getRequest()->getQuery('lang');
        if ($lang) {
            $translator = Zend_Registry::get('Zend_Translate');
            $adapter = $translator->getAdapter();
            if ($adapter->isAvailable($lang)) {
                $href = $this->view->addParamToUrl($href, 'lang', $lang);
            }
        }

        $attribs = array_merge(array('href' => $href), $attribs);
        if (!isset($text)) {
            $text = $href;
        }
        $html = '<a' . $this->_htmlAttribs($attribs) . '>' . $this->view->escape($text) . '</a>';
        return $html;
    }
}

