<?php
/*  This file is part of Syj, Copyright (c) 2010-2011 Arnaud Renevier,
    and is published under the AGPL license. */

abstract class Syj_Model_Pending extends Syj_Model_Generic
{
    protected $_id;
    protected $_user;
    protected $_action;
    protected $_hash;
    protected $_notifications_number;
    protected $_creation_time;

    protected $_baseUrl;

    public static $rules = array('validate_creation' => array(null, '6d', '7d'),
                            'reset_password' => array(null, '2d'));

    public function setId($id) {
        $this->_id = (int) $id;
        return $this;
    }

    public function getId() {
        return $this->_id;
    }

    public function setUser(Syj_Model_User $user) {
        $this->_user = $user;
        return $this;
    }

    public function getUser() {
        return $this->_user;
    }

    public function setAction($action) {
        $possibleactions = array("validate_creation", "reset_password");
        if (!in_array($action, $possibleactions)) {
            throw new Zend_Exception((string)$action . ': Invalid value for Pending::action');
        }
        $this->_action = (string)$action;
        return $this;
    }

    public function getAction() {
        return $this->_action;
    }

    public function setHash($hash) {
        $this->_hash = (string)$hash;
        return $this;
    }

    public function getHash() {
        return $this->_hash;
    }

    public function setNotificationsNumber($number) {
        $this->_notifications_number = (int)$number;
        return $this;
    }

    public function getNotificationsNumber() {
        return $this->_notifications_number;
    }

    public function setCreationTime($timestamp) {
        $this->_creation_time = date_create($timestamp);
        return $this;
    }

    public function getCreationTime() {
        return $this->_creation_time;
    }

    protected function getHashUrl() {
        $rooturl = Zend_Controller_Front::getInstance()->getParam('rooturl');
        return $rooturl . "pending/" . $this->_hash;
    }

    protected function getContactUrl() {
        $rooturl = Zend_Controller_Front::getInstance()->getParam('rooturl');
        return $rooturl . "contact";
    }

    protected function _sendMail($subject, $text) {
        $mail = new Zend_Mail('utf-8');
        $mail->addTo($this->_user->email)
             ->setSubject($subject)
             ->setBodyText($text);

        try {
            $mail->send();
        } catch(Exception $e) {
            return false;
        }
        return true;
    }

    public function run() {
        if ($this->_run() === false) {
            return false;
        }
        $mapper = new Syj_Model_PendingMapper();
        $mapper->delete($this);
        return true;
    }
    abstract protected function _run();

    public function notify() {
        $mapper = new Syj_Model_PendingMapper();
        if (!$this->_hash) {
            $mapper->save($this);
        }

        if ($this->_notify() === false) {
            return false;
        }

        $this->_notifications_number++;

        if ($this->_notifications_number >= count(self::$rules[$this->_action])) {
            $this->cancel();
        } else {
            $mapper->save($this);
        }
        return true;
    }
    abstract protected function _notify();

    public function cancel() {
        if ($this->_cancel() === false) {
            return false;
        }
        $mapper = new Syj_Model_PendingMapper();
        $mapper->delete($this);
        return true;
    }
    abstract protected function _cancel();

}
