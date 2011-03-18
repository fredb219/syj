<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_View_Helper_AddParamToUrl extends Zend_View_Helper_Abstract
{
    public function addParamToUrl($url, $param, $value, $replace = false) {
        if (strpos ($url, '?') === false) {
            return $url . '?' . $param . '=' . $this->view->escape($value);
        }

        $parts = explode('&', substr(strstr($url, '?'), 1));

        foreach (explode('&', substr(strstr($url, '?'), 1)) as $key => $part) {
            if (substr($part, 0, strlen($param . '=')) === ($param . '=')) {
                if ($replace) {
                    unset($parts[$key]);
                } else {
                    return $url;
                }
            }
        }

        $parts[] = $param . '=' . $this->view->escape($value);

        return strstr($url, '?', true) . '?' . implode('&', $parts);
    }
}
