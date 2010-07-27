<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Model_PendingMapper
{
    protected $_dbTable;
    protected $_tableInfos = array('name' => 'pending_actions');

    public function getDbTable() {
        if (null === $this->_dbTable) {
            $this->_dbTable = new Zend_Db_Table($this->_tableInfos);
        }
        return $this->_dbTable;
    }

    public function fetchByHash($hash) {
        $table = $this->getDbTable();
        $result = $table->fetchAll(array('hash = ?' => (string)$hash));

        if (1 !== count($result)) {
            return null;
        }
        $row = $result->current();

        $entry = $this->pendingFactory($row->action);
        if (!isset($entry)) {
            continue;
        }
        $this->_itemFromRow($entry, $row);
        return $entry;
    }

    public function find($id, Syj_Model_Pending $pending) {
        $result = $this->getDbTable()->find((int)$id);
        if (0 == count($result)) {
            return false;
        }
        $row = $result->current();
        $this->_itemFromRow($pending,$row);
        return true;
    }


    public function fetchForUser(Syj_Model_User $user) {
        $table = $this->getDbTable();
        $select = $table->select();
        $select->union(array_map(function($select) use (&$user) {
            return $select->where('userid = ?', $user->id);
        }, $this->_selects()));

        $resultSet = $table->fetchAll($select);
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = $this->pendingFactory($row->action);
            if (!isset($entry)) {
                continue;
            }
            $entry->setUser($user);
            $entries[] = $this->_itemFromRow($entry, $row);
        }
        return $entries;
    }

    public function fetchAll() {
        $table = $this->getDbTable();
        $select = $table->select();
        $select->union($this->_selects());

        $resultSet = $table->fetchAll($select);
        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = $this->pendingFactory($row->action);
            if (!isset($entry)) {
                continue;
            }
            $entries[] = $this->_itemFromRow($entry, $row);
        }
        return $entries;
    }

    protected function pendingFactory($action) {
        switch ($action) {
            case 'validate_creation':
                return new Syj_Model_Pending_ValidateCreation();
            break;
            case 'reset_password':
                return new Syj_Model_Pending_ResetPassword();
            break;
            default:
                return null;
            break;
        }
    }

    protected function _selects() {
        $res = array();
        $table = $this->getDbTable();

        foreach (Syj_Model_Pending::$rules as $action_name => $action) {
            foreach ($action as $number => $item) {
                $select = $table->select();
                $select->where('action = ?', $action_name);
                if (isset($item)) {
                    $select->where('creation_time < (NOW() - interval ?)', $item);
                }
                if ($item === end($action)) {
                    $select->where('notifications_number >= ?', $number);
                } else {
                    $select->where('notifications_number = ?', $number);
                }
                $res[] = $select;
            }
        }

        return $res;
    }

    protected function _itemFromRow(Syj_Model_Pending $item, Zend_Db_Table_Row $row) {
        $item->setId($row->id)
             ->setHash($row->hash)
             ->setNotificationsNumber($row->notifications_number)
             ->setCreationTime($row->creation_time);

        if (!$item->getUser()) {
            $user = new Syj_Model_User();
            $userMapper = new Syj_Model_UserMapper();
            if ($userMapper->find($row->userid, $user)) {
                $item->setUser($user);
            }
        }

        return $item;
    }

    public function save (Syj_Model_Pending $pending) {
        $data = array(
            'userid'=> (string) $pending->user->id,
            'action'=> (string)$pending->action,
        );
        if (isset($pending->hash)) {
            $data['hash'] = (string) $pending->hash;
        }
        if (isset($pending->notificationsNumber)) {
            $data['notifications_number'] = (string) $pending->notificationsNumber;
        }
         if (null === ($id = $pending->getId())) {
            $id = $this->getDbTable()->insert($data);
            $this->find($id, $pending);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
            $this->find($id, $pending);
        }
    }

    public function delete (Syj_Model_Pending $pending) {
         if (null !== ($id = $pending->getId())) {
            $this->getDbTable()->delete(array('id = ?' => $id));
         }
    }
}
