<?php

include(__DIR__ . '/../init.inc.php');
Pix_Table::$_save_memory = true;

while (true) {
    foreach (NameList::search("count_at < updated_at") as $name_list) {
        error_log("Couting {$name_list->name}");
        $name_list->countWord();
    }
    sleep(1);
}
