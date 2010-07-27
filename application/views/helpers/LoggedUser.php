<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_View_Helper_LoggedUser extends Zend_View_Helper_Abstract
{
    public function LoggedUser() {
        try {
            $sessionStorage = Zend_Auth::getInstance()->getStorage();
        } catch(Exception $e) {
            return null;
        }
        $sessionData = $sessionStorage->read();
        if ($sessionStorage->isEmpty()) {
            return null;
        }

        $userMapper = new Syj_Model_UserMapper();
        $user = new Syj_Model_User();
        if ($userMapper->find($sessionData['user'], $user)) {
            return $user;
        } else {
            return null;
        }
    }
}

?>
