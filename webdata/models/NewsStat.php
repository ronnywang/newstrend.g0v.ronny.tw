<?php

class NewsStat extends Pix_Table
{
    public function init()
    {
        $this->_name = 'news_stat';
        $this->_primary = array('date', 'source');

        $this->_columns['date'] = array('type' => 'int');
        $this->_columns['source'] = array('type' => 'int');
        $this->_columns['count'] = array('type' => 'int');
    }
}
