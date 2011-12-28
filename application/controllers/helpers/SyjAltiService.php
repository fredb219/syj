<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */


class Syj_Controller_Action_Helper_SyjAltiService extends Zend_Controller_Action_Helper_Abstract
{
    protected static $_service = null;

    public function service() {
        if (is_null (self::$_service)) {
            $params = Zend_Controller_Front::getInstance()->getParam('altiphp');
            if ($params['source'] == 'srtmtiles' and isset($params['cache'])) {
                $cachedir = $params['cache'];
                if (!is_dir($cachedir)) {
                    if (@mkdir($cachedir, 0755, true) === false) {
                        throw new Zend_Exception();
                    }
                }
            }
            self::$_service = new alti\Alti($params);
        }
        return self::$_service;
    }
}
