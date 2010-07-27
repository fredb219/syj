<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Controller_Action_Helper_SyjGeoip extends Zend_Controller_Action_Helper_Abstract
{

    public function direct ($ip) {
        $db = Zend_Db_Table_Abstract::getDefaultAdapter();
        $select = $db->select(null)->from('geoip', null)->where('geoip.begin_ip <= inet_to_bigint(?)', (string)$ip)
                                  ->where('geoip.end_ip >= inet_to_bigint(?)', (string)$ip)
                                  ->limit(1)
                                  ->join('geonames', 'geonames.country = geoip.country', array(
                                  'geonames.minlon', 'geonames.minlat', 'geonames.maxlon', 'geonames.maxlat'
                                  ));
        try {
            $stmt = $db->query($select);
            $row = $db->query($select)->fetch();
        } catch (Exception $e) {
            $row = null;
        }
        if (!$row) {
            $row = Zend_Controller_Front::getInstance()->getParam('defaultloc');
        }
        return array_map(function($elem) { return (float)$elem;},$row);
    }
}
