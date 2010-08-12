<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Model_UserMapper
{
    protected $_dbTable;
    protected $_tableInfos = array('name' => 'users');

    public function getDbTable() {
        if (null === $this->_dbTable) {
            $this->_dbTable = new Zend_Db_Table($this->_tableInfos);
        }
        return $this->_dbTable;
    }

    public function find($id, Syj_Model_User $user) {
        $result = $this->getDbTable()->find((int)$id);
        if (0 == count($result)) {
            return false;
        }
        $row = $result->current();
        $this->_itemFromRow($user, $row);
        return true;
    }

    public function findByPseudo($pseudo, Syj_Model_User $user) {
        $table = $this->getDbTable();
        $select = $table->select()->where('pseudo = ?', (string)$pseudo);
        $row = $table->fetchRow($select);
        if (!$row) {
            return false;
        }
        $this->_itemFromRow($user, $row);
        return true;
    }

    public function findByEmail($email, Syj_Model_User $user) {
        $table = $this->getDbTable();
        $select = $table->select()->where('email = ?', (string)$email);
        $row = $table->fetchRow($select);
        if (!$row) {
            return false;
        }
        $this->_itemFromRow($user, $row);
        return true;
    }

    public function fetchAll() {
        $resultSet = $this->getDbTable()->fetchAll();
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Syj_Model_User();
            $this->_itemFromRow($entry, $row);
            $entries[] = $entry;
        }
        return $entries;
    }

    protected function _itemFromRow(Syj_Model_User $item, Zend_Db_Table_Row $row) {
        $item->setId($row->id)
            ->setPassword($row->password)
            ->setPseudo($row->pseudo)
            ->setEmail($row->email)
            ->setLang($row->lang);
    }

    public function save (Syj_Model_User $user) {
        $data = array(
            'pseudo'=> (string) $user->pseudo,
            'password'=> (string)$user->password,
            'email'=> (string) $user->email,
            'lang'=> (string) $user->lang
        );
        if (null === ($id = $user->getId())) {
            $user->id = $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
        $user->notifyPendings();
    }

    public function delete (Syj_Model_User $pending) {
         if (null !== ($id = $pending->getId())) {
            $this->getDbTable()->delete(array('id = ?' => $id));
         }
    }
}

