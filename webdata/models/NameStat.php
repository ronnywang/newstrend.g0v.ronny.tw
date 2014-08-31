<?php

class NameStat extends Pix_Table
{
    public function init()
    {
        $this->_name = 'name_stat';
        $this->_primary = array('date', 'name_id');

        $this->_columns['date'] = array('type' => 'int');
        $this->_columns['name_id'] = array('type' => 'int');
        $this->_columns['count'] = array('type' => 'int');
    }
}
