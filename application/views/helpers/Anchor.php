<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_View_Helper_Anchor extends Zend_View_Helper_HtmlElement
{
    public function Anchor($href, $text = null, array $attribs=array(), $escape = true) {
        $attribs = array_merge(array('href' => $href), $attribs);
        if (!isset($text)) {
            $text = $href;
        }
        $content = $escape ? $this->view->escape($text) : $text;
        $html = '<a' . $this->_htmlAttribs($attribs) . '>' . $content . '</a>';
        return $html;
    }
}

