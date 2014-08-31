<?php

class NameList extends Pix_Table
{
    public function init()
    {
        $this->_name = 'name_list';
        $this->_primary = 'id';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['name'] = array('type' => 'varchar', 'size' => 16);

        $this->addIndex('name', array('name'), 'unique');
    }
}
