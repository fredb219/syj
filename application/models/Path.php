<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Model_Path extends Syj_Model_Generic
{
    protected $_id;
    protected $_geom;
    protected $_creator;
    protected $_title;
    protected $_urlcomp;
    protected $_creator_ip;

    public function setId($id) {
        $this->_id = (int) $id;
        return $this;
    }

    public function getId() {
        return $this->_id;
    }

    public function setGeom(gisconverter\Geometry $geom) {
        $this->_geom = $geom;
        return $this;
    }

    public function getGeom() {
        return $this->_geom;
    }

    public function setCreator(Syj_Model_User $creator = null) {
        $this->_creator = $creator;
        return $this;
    }

    public function getCreator() {
        return $this->_creator;
    }

    public function isCreator(Syj_Model_User $creator = null) {
        if (!$creator or !$this->creator) {
            return false;
        }
        return ($creator->id == $this->creator->id);
    }

    public function setTitle($title) {
        $this->_title = (string) $title;
        return $this;
    }

    public function getTitle() {
        return $this->_title;
    }

    public function getDisplayTitle() {
        if ($this->_title) {
            return $this->_title;
        } else if ($this->_id) {
            $title = $this->getTranslator()->translate("route number %d");
            return str_replace('%d', (string)$this->id, $title);
        } else {
            return "";
        }
    }

    public function setUrlComp($_urlcomp) {
        $this->_urlcomp = (string) $_urlcomp;
        return $this;
    }

    public function getUrlComp() {
        return $this->_urlcomp;
    }

    public function setCreatorIp($_creator_ip) {
        $this->_creator_ip = (string) $_creator_ip;
        return $this;
    }

    public function getCreatorIp() {
        return $this->_creator_ip;
    }

    /*
     * returns an array containing two arrays. First one is list of distance
     * from start. Second on is altitude at that point.
     * @error: ratio of void we accept
     */
    public function getAltiProfile($altiService, $error = 0) {
        $points = $altiService->interpolate($this->_geom);
        $points = array_map(function($point) {
            if ($point instanceof \gisconverter\Geometry) {
                return array($point->lon, $point->lat);
            }
            return $point;
        }, $points);
        $altitudes = $altiService->altitude($points);

        if (is_null($altitudes)) {
            throw new Syj_Exception_NotImplemented("could not compute altitude profile");
        }

        $res = array(array(0, $altitudes[0]));
        $prevpoint  = $points[0];
        $prevdist  = 0;

        $alticount = count($altitudes);
        $altinull = 0;
        foreach (range(1, count($altitudes) - 1) as $idx) {
            $point = $points[$idx];
            $delta = $altiService->vincentyDistance($prevpoint[0], $prevpoint[1], $point[0], $point[1]);
            $dist = $prevdist + $delta;
            if ($delta == 0) { // we have two similar points side to side
                continue;
            }
            if (is_null($altitudes[$idx])) {
                $altinull++;
                continue;
            }
            $prevpoint = $point;
            $prevdist = $dist;
            $res[] = array($dist, $altitudes[$idx]);
        }

        if ($altinull / $alticount > $error) {
            throw new Syj_Exception_NotImplemented("too many void in altitude profile");
        }
        return $res;
    }

    public function getProfileCache($size) {
        $cacheDir = Zend_Controller_Front::getInstance()->getParam('profileCache');
        if (is_file($cacheDir) and !is_dir($cacheDir)) {
            throw new Zend_Exception();
        }
        if (!is_dir($cacheDir)) {
            if (@mkdir($cacheDir, 0755, true) === false) {
                throw new Zend_Exception();
            }
        }

        return sprintf("%s/%s-%s.png", $cacheDir, (string)$this->_id, $size);
    }

    public function invalidateCache() {
        @unlink($this->getProfileCache('small'));
        @unlink($this->getProfileCache('big'));
    }

}
