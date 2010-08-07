<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
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
            return "journey number " . (string)$this->_id;
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

}
