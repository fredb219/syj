<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Model_User extends Syj_Model_Generic
{
    protected $_id;
    protected $_pseudo;
    protected $_password;
    protected $_email;
    protected $_lang;
    protected $_creation_addr;

    public function setId($id) {
        $this->_id = (int) $id;
        return $this;
    }

    public function getId() {
        return $this->_id;
    }

    public function setPassword($password) {
        $this->_password = (string) $password;
        return $this;
    }

    public function getPassword() {
        return $this->_password;
    }

    public function setPseudo($pseudo) {
        $this->_pseudo = (string) $pseudo;
        return $this;
    }

    public function getPseudo() {
        return $this->_pseudo;
    }

    public function setEmail($email) {
        $this->_email = (string) $email;
        return $this;
    }

    public function getEmail() {
        return $this->_email;
    }

    public function setLang($lang) {
        $this->_lang = (string) $lang;
        return $this;
    }

    public function getLang() {
        return $this->_lang;
    }

    public function setCreationAddr($creation_addr) {
        $this->_creation_addr = (string) $creation_addr;
        return $this;
    }

    public function getCreationAddr() {
        return $this->_creation_addr;
    }

    public function notifyPendings() {
        $pendingMapper = new Syj_Model_PendingMapper();
        $pendings = $pendingMapper->fetchForUser($this);
        foreach ($pendings as $pending) {
            $pending->notify();
        }
    }

}

