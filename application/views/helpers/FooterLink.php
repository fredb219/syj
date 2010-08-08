<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_View_Helper_FooterLink extends Zend_View_Helper_Abstract
{
    public function FooterLink($routeoptions, $text, $redirect=true, $extraclass=null) {
        $page = new Zend_Navigation_Page_Mvc($routeoptions);
        if ($page->isActive()) {
            $link = $this->view->escape($text);
        } else {
            $href = $page->getHRef();
            if ($redirect) {
                $currentUri = $this->view->url();
                $href = $this->view->addParamToUrl($href, 'redirect', $currentUri, true);
            }
            $link = $this->view->anchor($href, $text, array('class' => 'footer-anchor'));
        }
        $class = "footer-link";
        if (isset($extraclass)) {
            $class = "$class $extraclass";
        }
        return '<div class="' . $class . '">' . $link . '</div>' . PHP_EOL;
    }
}

?>
