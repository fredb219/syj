<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_View_Helper_UriPath extends Zend_View_Helper_Abstract
{
    public function uriPath($encoded = false) {
        $uri = Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }
        if ($encoded) {
            $uri = implode('/', array_map('urlencode', explode('/', $uri)));
        }
        return $uri;
    }
}
