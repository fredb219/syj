<?php
/*  This file is part of Syj, Copyright (c) 2010 Arnaud Renevier,
    and is published under the AGPL license. */

class Syj_Model_PathMapper
{
    protected $_dbTable;
    protected $_tableInfos = array('name' => 'paths');

    public function getDbTable() {
        if (null === $this->_dbTable) {
            $this->_dbTable = new Zend_Db_Table($this->_tableInfos);
        }
        return $this->_dbTable;
    }

    /*
     * return true if a path with id $id has been created one day, even if the
     * path has been deleted since. Returns false if path has never existed.
     */
    public function hasexisted($id) {
        if (!is_numeric($id)) {
            return false;
        }
        $db = $this->getDbTable()->getAdapter();
        $expr = $db->quoteInto('seq_attained_value(?)', array('paths_id_seq', (int)$id));
        $select = $db->select()->from(new Zend_Db_Expr($expr));
        print $select->assemble();
        $row = $db->fetchRow($select);
        return $row['t'];
    }

    public function find($id, Syj_Model_Path $path) {
        $select = $this->_select();
        $select->where('id = ?', (int)$id);
        return $this->_fetchItem($select, $path);
    }

    public function findByUrl($url, Syj_Model_Path $path) {
        $select = $this->_select();
        $select->where('id = ?', (int)$url)->orWhere('urlcomp = ?', (string)$url);
        return $this->_fetchItem($select, $path);
    }

    public function fetchAll() {
        $select = $this->_select();

        $resultSet = $table->fetchAll($select);

        $entries   = array();
        foreach ($resultSet as $row) {
            $entry = new Syj_Model_Path();
            $entries[] = $this->_itemFromRow($entry, $row);
        }
        return $entries;
    }

    public function save (Syj_Model_Path $path) {
        $data = array(
            'geom'=> (string)$path->geom,
            'owner'=> $path->owner->id,
            'title'=> $path->title
        );
        if (null === ($id = $path->getId())) {
            $path->id = $this->getDbTable()->insert($data);
        } else {
            $this->getDbTable()->update($data, array('id = ?' => $id));
        }
    }

    protected function _itemFromRow(Syj_Model_Path $item, Zend_Db_Table_Row $row) {
        $decoder = new gisconverter\WKT();
        $geom = $decoder->geomFromText($row->wkt);

        $item->setId($row->id)->
            setGeom($geom)->
            setTitle($row->title)->
            setUrlComp($row->urlcomp);

        if (!$item->getOwner()) {
            $user = new Syj_Model_User();
            $userMapper = new Syj_Model_UserMapper();
            if ($userMapper->find($row->owner, $user)) {
                $item->setOwner($user);
            }
        }
        return $item;
    }

    protected function _fetchItem(Zend_Db_Select $select, Syj_Model_Path $path) {
        $row = $select->getTable()->fetchRow($select);
        if ($row) {
            $this->_itemFromRow($path, $row);
            return true;
        }
        return false;
    }

    protected function _select() {
        $table = $this->getDbTable();
        $select = $table->select();
        $select->from($table, array('id', 'ST_AsText(geom) AS wkt', 'owner', 'title', 'urlcomp'));
        return $select;
    }

}

