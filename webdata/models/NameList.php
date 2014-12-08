<?php

class NameListRow extends Pix_Table_Row
{
    public function countWord()
    {
        setlocale(LC_ALL, 'en_US.UTF-8');
        foreach (glob(getenv('DATA_PATH') . '/*.gz') as $gz_file) {
            $cmd = 'zgrep -B 2 ' . escapeshellarg($this->name) . ' ' . escapeshellarg($gz_file) . ' | grep --only-match \'"source":"[0-9]*"\' | sort | uniq -c';

            if (!preg_match('#([0-9]*)\.txt\.gz#', $gz_file, $matches)) {
                continue;
            }
            $date = $matches[1];
            if (NameStat::search(array('date' => $date, 'name_id' => $this->id, 'source' => 0))->count()) {
                continue;
            }
            $lines = explode("\n", trim(`$cmd`));
            $total = 0;
            foreach ($lines as $line) {
                if (!preg_match('#([0-9]*) "source":"([0-9]*)"#', $line, $matches)) {
                    continue;
                }

                NameStat::insert(array(
                    'date' => $date,
                    'name_id' => $this->id,
                    'source' => $matches[2],
                    'count' => $matches[1],
                ));
                $total += $matches[1];
            }
            if ($total) {
                NameStat::insert(array(
                    'date' => $date,
                    'name_id' => $this->id,
                    'source' => 0,
                    'count' => $total,
                ));
                error_log("date={$date}, source=0, count={$total}");
            }
        }
        $this->update(array('count_at' => time()));
    }
}

class NameList extends Pix_Table
{
    public function init()
    {
        $this->_name = 'name_list';
        $this->_primary = 'id';
        $this->_rowClass = 'NameListRow';

        $this->_columns['id'] = array('type' => 'int', 'auto_increment' => true);
        $this->_columns['name'] = array('type' => 'varchar', 'size' => 16);
        $this->_columns['created_at'] = array('type' => 'int');
        $this->_columns['count_at'] = array('type' => 'int');
        $this->_columns['updated_at'] = array('type' => 'int');

        $this->addIndex('name', array('name'), 'unique');
    }
}
